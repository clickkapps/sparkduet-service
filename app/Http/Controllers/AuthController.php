<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use App\Models\EmailAuthorization;
use App\Models\Story;
use App\Models\User;
use App\Models\UserInfo;
use App\Notifications\EmailAuthCodeGenerated;
use App\Services\FirebaseService;
use App\Traits\UserTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JetBrains\PhpStorm\ArrayShape;
use Kreait\Firebase\Exception\AuthException;
use Kreait\Firebase\Exception\FirebaseException;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    use UserTrait;
    protected FirebaseService $firebaseService;
//
    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * @throws ValidationException
     * @throws \Exception
     */
    /**
     * @throws AuthException
     * @throws FirebaseException
     * @throws \Exception
     */
    public function adminLogin(Request $request): JsonResponse
    {

        $this->validate($request, [
           'email' => 'required',
           'password' => 'required',
           'security_code' => 'required'
        ]);

        $securityCode = ''; // This has to be changed frequently (or when foul play is detected)
        $adminSecurityCode = config('custom.admin_security_code');
        if($securityCode  != $adminSecurityCode) {
            throw  new \Exception("Invalid request");
        }

        $email = $request->get('email');
        $password = $request->get('password');

        $user = User::with([])->where('email', $email)->first();
        if(blank($user)) {
            throw new \Exception("Invalid request");
        }

        if(!Hash::check($password, $user->{"password"})) {
            throw new \Exception('Invalid credentials');
        }

        $token = $user->createToken($email);

        $firebaseUser = $this->firebaseService->getFirebaseUser($email);
        $customToken = $this->firebaseService->createCustomToken($firebaseUser->uid)->toString();

        $user->update(['last_login_at' => now(),]);

        return response()->json(ApiResponse::successResponseWithData([
            'access_token' => $token->plainTextToken,
            'custom_token' => $customToken
        ]));

    }

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

        $code  = generateRandomNumber();

        EmailAuthorization::create([
            'email' => $email,
            'status' => 'opened',
            'code' => Hash::make($code),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $parts = explode('@', $email);
        $name = $parts[0];
//        $user = User::firstOrCreate([
//            'email' => $email
//        ], [
//            'name' => $name,
//        ]); // all emails whose verified at is NULL will be deleted from the system

        Log::info(sprintf(' email: %s, verification code: %s', $email, $code));

        Notification::route('mail', $email)->notify(new EmailAuthCodeGenerated($code,$name));

        return response()->json(ApiResponse::successResponse('Verification code sent to ' . $email));

    }

    /**
     * @throws FirebaseException
     * @throws AuthException
     */
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
        // Get the user's IP address
        $userIp = $request->ip();

        $record = EmailAuthorization::where([
            'email' => $email,
            'status' => 'opened'
        ])->first();

        if(blank($record)){
            return response()->json(ApiResponse::failedResponse('Verification code is invalid'));
        }

        $validCode = Hash::check($code, $record->code);

        if(!$validCode){
            return response()->json(ApiResponse::failedResponse('Verification code is invalid'));
        }

        $record->update([
            'status' => 'closed'
        ]);

        // valid code
        $tokens = $this->generateAccessTokenFromEmail(userIp: $userIp, email: $email);


        return response()->json(ApiResponse::successResponseWithData($tokens));

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

//            https://github.com/mikebronner/laravel-sign-in-with-apple for apple configuration
//            https://socialiteproviders.com/Apple/#installation-basic-usage
//            return Socialite::driver("apple")->redirect();
            return Socialite::driver('apple')->redirect();
        }

        return response()->json(ApiResponse::failedResponse('Invalid provider'));

    }

    /**
     * @throws FirebaseException
     * @throws AuthException
     */
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

            // Get the user's IP address
            $userIp = $request->ip();
            $provider = $request->get('provider');

            $email = null;

            if($provider == 'google') {
                $user = Socialite::driver('google')->stateless()->user();
                Log::info('returned google user: ' . json_encode($user));
                $email = $user->getEmail();

            }else if ($provider == 'apple') {
                // get abstract user object, not persisted
                $user = Socialite::driver("apple")
                    ->stateless()
                    ->user();
                Log::info('returned apple user: ' . json_encode($user));
                $email = $user->getEmail();
            }


            // once the email is not null continue

            if(blank($email)){
                abort(400);
            }


            $tokens = $this->generateAccessTokenFromEmail(userIp: $userIp, email: $email);
            $accessToken = $tokens['access_token'];
            $firebaseToken = $tokens['custom_token'];
            return redirect('/?access_token=' . $accessToken . '&custom_token=' . $firebaseToken);



        } catch (\Exception $e) {
            Log::info('exception: ' . $e->getMessage());
            return redirect('/?error='. $e->getMessage());
        }

    }

    /**
     * @throws AuthException
     * @throws FirebaseException
     */
    #[ArrayShape(['access_token' => "mixed", 'custom_token' => "\Lcobucci\JWT\UnencryptedToken"])]
    private function generateAccessTokenFromEmail(string $userIp, string $email): array
    {

        $parts = explode('@', $email);
        $name = $parts[0];

        // authenticate user with provider email and then redirect to url with the user token
        $now = now();

        $user = User::with([])->where('email', $email)->first();
        if(blank($user)) {
            // sign up --------
            $publicKey = Str::uuid();
            $user = User::with([])->create([
                'email' => $email,
                'name' => $name,
                'last_login_at' => $now,
                'email_verified_at' => $now,
                'first_login_at' => $now,
                'public_key' => $publicKey
            ]);

            $firebaseUser = $this->firebaseService->createFirebaseUser($email, $publicKey);
            $customToken = $this->firebaseService->createCustomToken($firebaseUser->uid)->toString();

        }else {

            $firebaseUser = $this->firebaseService->getFirebaseUser($email);
            $customToken = $this->firebaseService->createCustomToken($firebaseUser->uid)->toString();

            $user->update([
                'last_login_at' => $now,
//                'email_verified_at' => $now
            ]);
        }

        $userInfo = UserInfo::with([])->firstOrCreate(
            ['user_id' => $user->{'id'}],
            [
                'bio' => "Hey✋, I am on the lookout for a partner. Interested in exploring the journey of finding love together?"
            ]
        );

        // We need to this so we can fetch stories narrowed in the users country
        if(blank($userInfo->{"preferred_nationalities"})){
            $this->setLocationInfo(userInfo: $userInfo, userIp:  $userIp);
        }

        $token = $user->createToken($email);
        Log::info('token: ' . json_encode($token));
        Log::info('firebase token: ' . json_encode($customToken));


        return [
            'access_token' => $token->plainTextToken,
            'custom_token' => $customToken
        ];
    }

    public function updateAuthUserProfile(Request $request): JsonResponse {

        $user = $request->user();

        $basicInfoPayload = [];

        if($request->has("name")) {
            $basicInfoPayload["name"] = $request->get("name");
        }

        if($request->has("chat_id")) {
            $basicInfoPayload["chat_id"] = $request->get("chat_id");
        }

        if(!empty($basicInfoPayload)) {
            User::with([])->find($user->id)->update($basicInfoPayload);
        }

        // user filters / personal preferences
        $additionalInfoPayload = [];

        if($request->has("bio")) {
            $additionalInfoPayload["bio"] = $request->get("bio");
        }
        if($request->has("age")) {
            $additionalInfoPayload["age"] = $request->get("age");
            $additionalInfoPayload["age_at"] = today();
        }
        if($request->has("gender")) {
            $additionalInfoPayload["gender"] = $request->get("gender");
        }
        if($request->has("profilePhoto")) {
            $additionalInfoPayload["profile_pic_path"] = $request->get("profilePhoto");
        }
        if($request->has("race")) {
            $additionalInfoPayload["race"] = $request->get("race");
        }

        if($request->has("preferred_gender")){
            $additionalInfoPayload["preferred_gender"] = $request->get("preferred_gender");
        }
        if($request->has("preferred_min_age")){
            $additionalInfoPayload["preferred_min_age"] = $request->get("preferred_min_age");
        }
        if($request->has("preferred_max_age")){
            $additionalInfoPayload["preferred_max_age"] = $request->get("preferred_max_age");
        }
        if($request->has("preferred_races")){
            $additionalInfoPayload["preferred_races"] = $request->get("preferred_races");
        }
        if($request->has("preferred_nationalities")){
            $additionalInfoPayload["preferred_nationalities"] = $request->get("preferred_nationalities");
        }

        if(!empty($additionalInfoPayload)) {
            UserInfo::with([])->where(["user_id" => $user->id ])->update($additionalInfoPayload);
        }

        return $this->getUserProfile($request);

    }

    // We want to prompt users to update their basic info
    public function shouldPromptAuthUserToUpdateBasicInfo(Request $request): JsonResponse {

        $user = $request->user();

        $userInfo = UserInfo::with([])
            ->where(["user_id" => $user->id])
            ->first();

        // check if we have already requested user to
        if($userInfo->{"requested_profile_update"}) {
            return response()->json(ApiResponse::successResponseWithData(false));
        }

        // check if the user has introductory video and has not been prompted yet, then prompt
        $introductoryPost = Story::with([])->where([
            "user_id" => $user->{"id"},
            "purpose" => "introduction"
        ])->first();

        if(blank($introductoryPost)) {
            return response()->json(ApiResponse::successResponseWithData(false));
        }

        // okay prompt
        return response()->json(ApiResponse::successResponseWithData(true));

    }

    // once we prompt... we need to update the db that the user has been prompted. We perhaps we don't prompt again
    public function setPromptBasicInfoCompleted(Request $request): JsonResponse
    {

        $user = $request->user();
        UserInfo::with([])->where(["user_id" => $user->id])
            ->update(['requested_profile_update' => now()]);

        return response()->json(ApiResponse::successResponse());
    }

}
