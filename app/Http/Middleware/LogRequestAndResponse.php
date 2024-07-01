<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class LogRequestAndResponse
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return Response|RedirectResponse
     * @throws \Exception
     */
    public function handle(Request $request, Closure $next)
    {
        Log::info('incoming url: ' . json_encode($request->fullUrl()));
        Log::info('incoming request IP: ' . json_encode($request->ip()));
        Log::info('incoming request header: ' . json_encode($request->header()));
        Log::info('incoming request body: ' . json_encode($request->all()));

        $countryCode = ip_info("Visitor", "Country Code"); // IN
        if ( $countryCode == 'IND') {
            // allow only request from the countries in the condition above
            Log::info('IP rejected: :' . json_encode($request->ip()) . ' country code: ' . json_encode($countryCode));
            exit;
        }

        Log::info('IP accepted: ' . json_encode($request->ip()) . ' country code: ' . json_encode($countryCode));

        $response = $next($request);
        // Log the response
        Log::info('Outgoing Response', [
            'status' => $response->getStatusCode(),
//            'headers' => $response->headers->all(),
            'body' => $response->getContent(),
        ]);

        return $response;
    }
}
