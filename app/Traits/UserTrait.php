<?php

namespace App\Traits;

use App\Classes\ApiResponse;
use App\Models\Story;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

trait UserTrait
{
    public function getUserProfile(Request $request, $id = null): \Illuminate\Http\JsonResponse
    {
        // if userId is null get the authenticated user profile
        $userId = $request->user()->id;

        if(!blank($id)){
            $userId = $id;
        }

//        Log::info("userId: ${$userId}");

        $user = User::with(['info'])->find($userId);
        $introductoryStory = Story::with([])->where([
            "user_id" =>  $userId,
            "purpose" => "introduction"
        ])->first();


        $user->{"introductory_post"} = $introductoryStory;

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

//        return response()->json(ApiResponse::successResponseV2($user));
        return response()->json(ApiResponse::successResponseWithData($user));

    }
}
