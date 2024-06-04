<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use App\Models\UserInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PreferencesController extends Controller
{
    public function fetchSettings(Request $request): \Illuminate\Http\JsonResponse
    {
        $authUser = $request->user();
        $settings = DB::table('user_settings')->where('user_id', $authUser->{'id'})->first();
        if(blank($settings)) {
            $settingId = DB::table('user_settings')->insertGetId([
               'user_id' => $authUser->{'id'},
               'created_at' => now(),
               'updated_at' => now()
            ]);
            $settings = DB::table('user_settings')->find($settingId);
        }

        return response()->json(ApiResponse::successResponseWithData($settings));

    }

    public function updateSettings(Request $request): \Illuminate\Http\JsonResponse
    {
        $authUser = $request->user();

        $settingsPayload = [];

        if($request->has("enable_chat_notifications")) {
            $settingsPayload["enable_chat_notifications"] = $request->get("enable_chat_notifications");
        }

        if($request->has("enable_profile_views_notifications")) {
            $settingsPayload["enable_profile_views_notifications"] = $request->get("enable_profile_views_notifications");
        }

        if($request->has("enable_story_views_notifications")) {
            $settingsPayload["enable_story_views_notifications"] = $request->get("enable_story_views_notifications");
        }

        if($request->has("theme_appearance")) {
            $settingsPayload["theme_appearance"] = $request->get("theme_appearance");
        }

        if($request->has("font_family")) {
            $settingsPayload["font_family"] = $request->get("font_family");
        }

        if(!empty($settingsPayload)) {
            DB::table('user_settings')->where('user_id', $authUser->{'id'})->update($settingsPayload);
        }

        return $this->fetchSettings($request);

    }

    public function createFeedback(Request $request): \Illuminate\Http\JsonResponse
    {
        $authUser = $request->user();
        DB::table('user_feedbacks')->insert([
            'user_id' =>  $authUser->{'id'},
            'message' =>  $request->get('message'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        return response()->json(ApiResponse::successResponse("Feedback received"));
    }
}
