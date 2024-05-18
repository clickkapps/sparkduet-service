<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use App\Models\Story;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StoryController extends Controller
{

    //! Private functions
    private function toggleStoryTable(Builder $table, $storyId, User $user) {
        $exists = $table->where([
            'user_id' => $user->{'id'},
            'story_id' => $storyId
        ])->exists();

        if($exists) {

            $table->where([
                'user_id' => $user->{'id'},
                'story_id' => $storyId
            ])->delete();

        }else {

            $table->insert([
                'user_id' => $user->{'id'},
                'story_id' => $storyId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        }
    }

    //! Public functions
    public function fetchStoryFeeds(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $query = Story::with([])
            ->where("user_id", "!=", $user->id)
            ->withCount(['likes', 'views'])
            ->where('blocked_by_admin_at', '=', null);

        $stories = $query->simplePaginate(3)->through(function ($story, $key) use ($user){
            return $story;
        });

        $updatedItems = $stories->getCollection();

        if($updatedItems->isEmpty()) {

            /// This prompts the user to create post
            $merged = $updatedItems->concat([
                [ 'id' => -1 ], /// Introductory video
//                [ 'id' => -2 ], /// Expectations in your next relationship
//                [ 'id' => -3 ], /// Previous relationship
//                [ 'id' => -4 ], /// Talk about your career
//                [ 'id' => -5 ], /// Your take on stay-home spouse
            ]);

            $stories->setCollection($merged);

        }


        return response()->json(ApiResponse::successResponseWithData($stories));
    }

    public function fetchUserPosts($userId) : \Illuminate\Http\JsonResponse {

        $query = Story::with([])
            ->where(["user_id" => $userId])
            ->withCount(['likes', 'views'])
            ->where('blocked_by_admin_at', '=', null);
        $posts = $query->simplePaginate(3);

        return response()->json(ApiResponse::successResponseWithData($posts));
    }

    public function fetchUserBookmarkedPosts($userId) : \Illuminate\Http\JsonResponse {

        $query = Story::with([])
            ->where(["user_id" => $userId])
            ->withCount(['likes', 'views'])
            ->where('blocked_by_admin_at', '=', null);
        $posts = $query->simplePaginate(3);

        return response()->json(ApiResponse::successResponseWithData($posts));
    }

    public function createFeed(Request $request): \Illuminate\Http\JsonResponse
    {

        $user = $request->user();
        // if user is blocked, user cannot post a story

        $validator = Validator::make($request->all(), [
            'media_path' => 'required',
            'media_type' => 'required',
        ]);

        if($validator->fails()){
            return response()->json(ApiResponse::failedResponse($validator->errors()->first()));
        }

        $purpose = $request->get("purpose");
        $mediaPath= $request->get("media_path");
        $mediaType= $request->get("media_type");
        $assetId = $request->get("asset_id");
        $description = $request->get('description');
        $commentsDisabled = $request->get("comments_disabled");

        $story = Story::with(['user'])->create([
            'user_id' => $user->id,
            'description' => $description,
            'comments_disabled_at' => $commentsDisabled ? now() : null,
            'blocked_by_admin_at' => null,
            'media_path' => $mediaPath,
            'media_type' => $mediaType,
            'asset_id' => $assetId,
            'purpose' => $purpose ?: "notApplicable"
        ]);

        return response()->json(ApiResponse::successResponseWithData($story));

    }

    public function updateFeed($id, Request $request): \Illuminate\Http\JsonResponse {

        $purpose = $request->get("purpose");
        $mediaPath= $request->get("media_path");
        $mediaType= $request->get("media_type");
        $description = $request->get('description');
        $commentsDisabled = $request->get("comments_disabled");

        $story = Story::with([])->find($id)->update([
            'description' => $description,
            'comments_disabled_at' => $commentsDisabled ? now() : null,
            'media_path' => $mediaPath,
            'media_type' => $mediaType,
            'purpose' => $purpose
        ]);

        return response()->json(ApiResponse::successResponseWithData($story));
    }


    // this system allows multiple likes
    public function likeStory(Request $request, $storyId): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'action' => 'add'
        ]);

        if($validator->fails()) {
            return response()->json(ApiResponse::failedResponse($validator->errors()->first()));
        }

        $table = DB::table('story_likes');

        $this->toggleStoryTable($table, $storyId, $user);

        return response()->json(ApiResponse::successResponse());

    }


    public function bookmarkStory(Request $request, $storyId): \Illuminate\Http\JsonResponse
    {

        $user = $request->user();
        $table = DB::table('story_bookmarks');

        $this->toggleStoryTable($table, $storyId, $user);

        return response()->json(ApiResponse::successResponse());

    }

    public function viewStory(Request $request, $storyId): \Illuminate\Http\JsonResponse
    {

        $user = $request->user();

        $table = DB::table('story_views');

        $this->toggleStoryTable($table, $storyId, $user);

        return response()->json(ApiResponse::successResponse());

    }

    public function reportStory(Request $request, $storyId): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
           'reason' => 'required'
        ]);

        if($validator->fails()) {
            return response()->json(ApiResponse::failedResponse('state the reason for reporting'));
        }

        $reason = $request->get('reason');

        DB::table('story_reports')->insert([
            'user_id' => $user->id,
            'story_id' => $storyId,
            'reason' => $reason,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(ApiResponse::successResponse());

    }


}
