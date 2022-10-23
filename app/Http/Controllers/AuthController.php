<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use App\Models\EmailAuthorization;
use App\Models\User;
use App\Notifications\EmailAuthCodeGenerated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{

    public function sendAuthEmailVerificationCode(Request $request): \Illuminate\Http\JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if($validator->fails()) {
            return response()->json(ApiResponse::failedResponse($validator->errors()->first()));
        }

        $email = $request->get('email');

        // close any opened email authorizations
        EmailAuthorization::where('email','=',$email)->update([
            'status' => 'closed'
        ]);

        EmailAuthorization::create([
            'email' => $email,
            'status' => 'opened',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $code  = generateRandomNumber();

        $user = new User();
        $user->{'email'} = $email;

        $user->notify((new EmailAuthCodeGenerated($code)));

        return response()->json(ApiResponse::successResponse('Verification code sent to ' . $email));


    }


    public function verifyAuthEmail(Request $request): \Illuminate\Http\JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'email' => 'required|email'
        ]);

        if($validator->fails()) {
            return response()->json(ApiResponse::failedResponse($validator->errors()->first()));
        }

        $email = $request->get('email');
        $code = $request->get('code');

        $record = EmailAuthorization::where([
            'email' => $email,
            'code' => $code,
            'status' => 'opened'
        ])->first();

        if(blank($record)){
            return response()->json(ApiResponse::failedResponse('Verification code is invalid'));
        }

        // valid code
        $token = $this->generateAccessTokenFromEmail($email);

        return response()->json(ApiResponse::successResponseV2($token));

    }

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
        }else if ($provider == 'apple') {
            return Socialite::driver("sign-in-with-apple")
                ->scopes(["name", "email"])
                ->redirect();
        }

        return response()->json(ApiResponse::failedResponse('Invalid provider'));

    }

    public function oAuth2ProviderCallback(Request $request): \Illuminate\Routing\Redirector|\Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse
    {

        try {

            $validator = Validator::make($request->all(), [
                'provider' => 'required'
            ]);

            if($validator->fails()) {
                Log::info('social auth error: ' . $validator->errors()->first());
                abort(400);
            }

            $provider = $request->get('provider');

            $email = null;

            if($provider == 'google') {
                $user = Socialite::driver('google')->user();
                Log::info('returned google user: ' . json_encode($user));
                $email = $user->getEmail();

            }else if ($provider == 'apple') {
                // get abstract user object, not persisted
                $user = Socialite::driver("sign-in-with-apple")
                    ->user();
                Log::info('returned apple user: ' . json_encode($user));
                $email = $user->getEmail();
            }


            // once the email is not null continue

            if(blank($email)){
                abort(400);
            }


            $token = $this->generateAccessTokenFromEmail($email);

            return redirect('/?token=' . $token);



        } catch (\Exception $e) {
            Log::info('exception: ' . $e->getMessage());
            return redirect('/');
        }

    }

    private function generateAccessTokenFromEmail(string $email) : string {

        $parts = explode('@', $email);
        $name = $parts[0];

        // authenticate user with provider email and then redirect to url with the user token
        $user = User::firstOrCreate([
            'email' => $email
        ], [
            'name' => $name,
        ]);

        $user->update([
            'last_login_at' => now()
        ]);

        $token = $user->createToken($email);
        Log::info('token: ' . json_encode($token));

        return $token->plainTextToken;
    }
}
