<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class RestrictIP
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if (!isLocal()) {

            Log::info('incoming request header: ' . json_encode($request->header()));
            Log::info('incoming request body: ' . json_encode($request->all()));
            Log::info('incoming request IP: ' . json_encode($request->ip()));

            $countryCode = ip_info("Visitor", "Country Code"); // IN
            if ( $countryCode != null && $countryCode != 'NL' && $countryCode != 'GH' && $countryCode != 'CA') {
                // allow only request from the countries in the condition above
                Log::info('IP rejected: :' . json_encode($request->ip()) . ' country code: ' . json_encode($countryCode));
                exit;
            }

            Log::info('IP accepted: ' . json_encode($request->ip()) . ' country code: ' . json_encode($countryCode));
        }

        return $next($request);
    }
}
