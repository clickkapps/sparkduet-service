<?php

namespace App\Exceptions;

use App\Classes\ApiResponse;
use App\Classes\AppResponse;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

use Illuminate\Auth\AuthenticationException;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $exception) {
        });

        $this->renderable(function (\Exception $e, Request $request) {

            if ($request->is('api/*') || $request->is('*/api/*')  || $request->is('stripe/webhook')) {
                return response()->json(ApiResponse::failedResponse($e->getMessage()));
            }
        });
    }

    protected function unauthenticated($request, AuthenticationException $exception): \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response|\Illuminate\Http\RedirectResponse
    {
        return $request->expectsJson()
            ? response()->json(ApiResponse::failedResponse( $exception->getMessage()), 401)
            : redirect()->guest($exception->redirectTo() ?? route('login'));
    }
}
