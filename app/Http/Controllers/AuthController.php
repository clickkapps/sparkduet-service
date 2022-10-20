<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function redirectToOAuth2Provider(Request $request): \Symfony\Component\HttpFoundation\RedirectResponse|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $validator = Validator::make($request->all(), [
           'provider' => 'required'
        ]);

        if($validator->fails()) {
            return response()->json(ApiResponse::failedResponse('Provider not specified'));
        }

        $provider = $request->get('provider');
        if($provider == "google") {
            return Socialite::driver('google')->redirect();
        }

        return response()->json(ApiResponse::failedResponse('Invalid provider'));

    }

    public function oAuth2ProviderCallback(Request $request){

        try {

            $validator = Validator::make($request->all(), [
                'provider' => 'required'
            ]);

            if($validator->fails()) {
                Log::info('social auth error: ' . $validator->errors()->first());
                return;
            }

            $user = Socialite::driver('google')->user();
            Log::info('returned social user: ' . json_encode($user));

            // authenticate user with provider email and then redirect to a url with the user token

        } catch (\Exception $e) {
            Log::info('exception: ' . $e->getMessage());
            return;
        }

    }
}
