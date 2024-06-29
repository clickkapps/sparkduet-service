<?php

namespace App\Traits;

use App\Classes\ApiResponse;
use App\Models\Story;
use App\Models\User;
use App\Models\UserInfo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

trait UserTrait
{
    public function getUserProfile(Request $request, $id = null): \Illuminate\Http\JsonResponse
    {
        // if userId is null get the authenticated user profile
        $user = $request->user();
        $userId = $user->id;

        if(!blank($id)){
            $userId = $id;
        }else{
            // currently loggedIn user is the only one who can update user and userInfo
            $updateUserPayload = [];
            if(blank($user->{'public_key'})) {
                $updateUserPayload['public_key'] = Str::uuid();
            }

            if(!empty($updateUserPayload)) {
                $user->update($updateUserPayload);
            }

            //! All other user updates that weren't added during sign up
            $updateUserInfoPayload = [];
            $userInfo = UserInfo::with([])->where(['user_id' => $userId])->first();

            if(blank($userInfo->{'preferred_gender'})) {
                $updateUserInfoPayload["preferred_gender"] = json_encode(["any"]);
            }
            if(blank($userInfo->{'preferred_min_age'})) {
                $updateUserInfoPayload["preferred_min_age"] = "18";
            }
            if(blank($userInfo->{'preferred_max_age'})) {
                $updateUserInfoPayload["preferred_max_age"] = "70";
            }
            if(blank($userInfo->{'preferred_races'})) {
                $updateUserInfoPayload["preferred_races"] = json_encode(["any"]);
            }

            if(!empty($updateUserInfoPayload)) {
                $userInfo->update($updateUserInfoPayload);
            }

//            if(blank($userInfo->{"preferred_nationalities"})){
//                $userIp = $request->ip();
//                $this->setLocationInfo(userInfo: $userInfo, userIp:  $userIp);
//            }
        }


        $user = User::with(['info'])->find($userId);
        $introductoryStory = Story::with([])->where([
            "user_id" =>  $userId,
            "purpose" => "introduction",
            "deleted_at" => null,
            "disciplinary_action" => null
        ])->orderByDesc('created_at')->first();

        $user->{"introductory_post"} = $introductoryStory;

        $user = $this->attachUserComputedAge($user);

        // if its not from auth user
        if(!blank($id)) {
            // Hide fields you don't want to include in the JSON response
            $user->makeHidden(['first_login_at', 'public_key', 'last_login_at']);
        }
//        return response()->json(ApiResponse::successResponseV2($user));
        return response()->json(ApiResponse::successResponseWithData($user));

    }

    protected function attachUserComputedAge($user) {
        if($user->{"info"}) {

            //! Get user's age as at this year
            if($user->{"info"}->{"age"} && $user->{"info"}->{"age_at"}) {
                $yearOfAge = Carbon::parse($user->{"info"}->{"age_at"})->year;
                $currentYear = Carbon::now()->year;
                $yearDiff = $currentYear - $yearOfAge;
                $user->{"display_age"} = $yearDiff + $user->{"info"}->{"age"};
            }else{
                $user->{"display_age"} = $user->{"info"}->{"age"};
            }
        }
        return $user;
    }

    protected function setLocationInfo($userInfo, string $userIp): void
    {
        try {

            $appEnv = config("app.env");
            $userIp = $appEnv == "local" ? "41.155.61.195" : $userIp;
//            $userIp = $this->userIp == "127.0.0.1" ? "41.155.61.195" : $this->userIp;
//            $userIp = $this->userIp;
            $ipInfoApiKey = config('custom.ipinfo_api_key');
            $path = "https://ipinfo.io/$userIp?token=$ipInfoApiKey";
            Log::info("location IP: $userIp");
            Log::info("location path: $path");
            $response = Http::get($path);

            if($response->failed()){
                Log::info('request failed: ' . $response->reason());
                return;
            }

            $responseBodyAsString = $response->body(); // string
            $responseBody = $response->object(); // object
            Log::info("response: $responseBodyAsString");
            $city = $responseBody->{'city'}; // eg. "Accra"
            $region = $responseBody->{'region'}; // eg. "Greater Accra"
            $country = $responseBody->{'country'}; // eg. "GH"
            $loc = $responseBody->{'loc'}; // eg. "5.5560,-0.1969"
            $timezone = $responseBody->{'timezone'}; //eg. "Africa/Accra",

            $userInfo->update([
                'city' => $city,
                'country' => $country,
                'region' => $region,
                'loc' => $loc,
                'timezone' => $timezone,
            ]);

            if(blank($userInfo->{'preferred_nationalities'})) {

                $userInfo->update([
                    'preferred_nationalities' => json_encode([
                        "key" => "only", //except / only / all
                        "values" => [
                            $country
                        ] // country codes
                    ]),
                ]);

            }


        }catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }
}
