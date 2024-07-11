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


    protected function getUsersOnlineQuery($userId): \Illuminate\Database\Eloquent\Builder {

        $user = User::with(['info'])->find($userId);

        /// Preferences  --------------------

        // Get preferred gender
        $preferredGenderOutput = [];
        if(!blank($user->{'info'}->{'preferred_gender'})) {
            $preferredGender = json_decode($user->{'info'}->{'preferred_gender'});
            //eg.  [ any ] , ["women","men","transgenders","non_binary_or_non_conforming"]
            Log::info("preferred_genders: " . $user->{'info'}->{'preferred_gender'});
            foreach ($preferredGender as $gender) {

                if($gender == "any") {
//                    $preferredGenderOutput = ["female","male","transgender","non_binary_or_non_conforming"];
                }else {
                    if($gender == "women") {
                        $preferredGenderOutput[] = "female";
                    }
                    if($gender == "men") {
                        $preferredGenderOutput[] = "male";
                    }
                    if($gender == "transgenders") {
                        $preferredGenderOutput[] = "transgender";
                    }
                    if($gender == "non_binary_or_non_conforming") {
                        $preferredGenderOutput[] = "non_binary_or_non_conforming";
                    }
                }
            }
        }

        // Get preferred nationalities
        $includedNationalities = [];
        $excludedNationalities = [];
        if(!blank($user->{'info'}->{'preferred_nationalities'})) {
            Log::info('preferred_nationalities: ' . $user->{'info'}->{'preferred_nationalities'});
            $preferredNationalities = json_decode($user->{'info'}->{'preferred_nationalities'}, true);
            //eg. {"key":"only","values":["GH"]}
            $key = $preferredNationalities['key'];
            $values = $preferredNationalities['values'];
            if($key == 'only') {
                foreach ($values as $value) {
                    $includedNationalities[] = $value;
                }
            }
            if($key == 'except') {
                foreach ($values as $value) {
                    $excludedNationalities[] = $value;
                }
            }

        }

        /// -------------------------

        // Build the filtered query with joins and initial filters
        $query = User::with(['info'])
            ->leftJoin('user_onlines', 'users.id', '=', 'user_onlines.user_id')
            ->leftJoin('user_blocks as b1', function ($join) use ($userId) {
                $join->on('users.id', '=', 'b1.offender_id')
                    ->where('b1.initiator_id', '=', $userId);
            })
            ->leftJoin('user_blocks as b2', function ($join) use ($userId) {
                $join->on('users.id', '=', 'b2.initiator_id')
                    ->where('b2.offender_id', '=', $userId);
            })
            ->join('user_infos', 'users.id', '=', 'user_infos.user_id')
            ->whereNull('b1.id')
            ->whereNull('b2.id')
            ->where('users.id', '!=', $userId)
//            ->whereNull('users.banned_at') // Exclude banned users
            ->where('user_onlines.status', 'online')
        ; // Filter for online users

        if (!empty($preferredGenderOutput)) {
            $query->whereIn('user_infos.gender', $preferredGenderOutput);
        }

        // Apply nationality filters based on the presence of included or excluded nationalities
        if (!empty($includedNationalities)) {
            $query->whereIn('user_infos.country', $includedNationalities);
        } elseif (!empty($excludedNationalities)) {
            $query->whereNotIn('user_infos.country', $excludedNationalities);
        }

        return $query
            ->orderByDesc('user_onlines.updated_at')
            ->distinct('users.id')
            ->select('users.*');

//        // Select users and paginate
//        $users = $query->select('users.*')->simplePaginate($request->get('limit') ?: 10);
//
//        return $users;
    }
}
