<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use App\Models\User;
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

            abort(404);

            $validator = Validator::make($request->all(), [
                'provider' => 'required'
            ]);

            if($validator->fails()) {
                Log::info('social auth error: ' . $validator->errors()->first());
                abort(400);
            }

            $provider = $request->get('provider');
            if($provider == 'google') {
                $user = Socialite::driver('google')->user();
                Log::info('returned social user: ' . json_encode($user));

                $email = $user->getEmail();
                if(blank($email)){
                    abort(400);
                }

                $parts = explode('@', $email);
                $name = $parts[0];

                // authenticate user with provider email and then redirect to url with the user token
                $user = User::firstOrCreate([
                    'email' => $email
                ], [
                    'name' => $name,
                ]);

                $token = $user->createToken($email);
                Log::info('token: ' . json_encode($token));

                return redirect('/?token=' . $token->plainTextToken);

            }


            abort(400);


        } catch (\Exception $e) {
            Log::info('exception: ' . $e->getMessage());
            return redirect('/');
        }

    }
}
