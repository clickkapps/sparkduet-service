<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use App\Models\Story;
use App\Models\User;
use App\Traits\UserTrait;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StoryController extends Controller
{

    use UserTrait;

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

//    [ 'id' => -2 ], /// Expectations in your next relationship
////                [ 'id' => -3 ], /// Previous relationship
////                [ 'id' => -4 ], /// Talk about your career
////                [ 'id' => -5 ], /// Your take on stay-home spouse
    //! Public functions
    public function fetchStoryFeeds(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $query = Story::with(['user'])
            ->where("user_id", "!=", $user->id)
            ->where('media_path', '!=', "")
            ->withCount(['likes', 'views'])
            ->where('blocked_by_admin_at', '=', null);

        $stories = $query->simplePaginate(3)->through(function ($story, $key) use ($user){
            return $story;
        });

        $updatedItems = $stories->getCollection();
        $merged = $updatedItems;

        if($updatedItems->isEmpty()) {

            /// This prompts the user to create post
            $introductoryPost = Story::with([])->where(["user_id" =>  $user->id, "purpose" => "introduction"])->first();

            if(blank($introductoryPost)) {
                $merged = $updatedItems->concat([
                    [ 'id' => -1 ], /// Introductory video
                ]);
            }else {
                $expectationPost = Story::with([])->where(["user_id" =>  $user->id, "purpose" => "expectations"])->first();
                if(blank($expectationPost)) {
                    $merged = $updatedItems->concat([
                        [ 'id' => -2 ], /// Introductory video
                    ]);
                }else {
                    $previousRelationshipPost = Story::with([])->where(["user_id" =>  $user->id, "purpose" => "previousRelationship"])->first();
                        if(blank($previousRelationshipPost)) {
                            $merged = $updatedItems->concat([
                                [ 'id' => -3 ], /// Introductory video
                        ]);
                    }else {
                            $careerPost = Story::with([])->where(["user_id" =>  $user->id, "purpose" => "career"])->first();
                            if(blank($careerPost)) {
                                $merged = $updatedItems->concat([
                                    ['id' => -4], /// Introductory video
                                ]);
                            }else {
                                $otherPost = Story::with([])->where(["user_id" =>  $user->id, "purpose" => "career"])->first();
                                if(blank($otherPost)) {
                                    $merged = $updatedItems->concat([
                                        ['id' => -5], /// Introductory video
                                    ]);
                                }
                            }
                        }
                }
            }

        }else {



        }

        $stories->setCollection($merged);


        return response()->json(ApiResponse::successResponseWithData($stories));
    }

    public function fetchUserPosts($userId, Request $request) : \Illuminate\Http\JsonResponse {

        $query = Story::with(['user'])
            ->where(["user_id" => $userId])
            ->withCount(['likes', 'views'])
            ->orderByDesc("created_at")
            ->where('blocked_by_admin_at', '=', null);
        $posts = $query->simplePaginate($request->get("limit") ?: 9);

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

    //! Feed creation process:
    // 1. initiate feed to get recordId
    // 2. send media to media platform
    // 3. update feed with media link

//    public function initiateCreateFeed(Request $request){
//        $user = $request->user();
//    }

    public function createPost(Request $request): \Illuminate\Http\JsonResponse
    {

        $user = $request->user();
        // if user is blocked, user cannot post a story

        $purpose = $request->get("purpose");
        $mediaPath= $request->get("media_path") ?: "";
        $mediaType= $request->get("media_type") ?: "";
        $assetId = $request->get("asset_id") ?: "";
        $description = $request->get('description');
        $commentsDisabled = $request->get("comments_disabled");

        $post = Story::with(['user'])->create([
            'user_id' => $user->id,
            'description' => $description,
            'comments_disabled_at' => $commentsDisabled ? now() : null,
            'blocked_by_admin_at' => null,
            'media_path' => $mediaPath,
            'media_type' => $mediaType,
            'asset_id' => $assetId,
            'purpose' => $purpose ?: "notApplicable"
        ]);

        return response()->json(ApiResponse::successResponseWithData($post));

    }

    /**
     * @throws \Exception
     */
    public function attachMediaToPost($id, Request $request): \Illuminate\Http\JsonResponse
    {

        $post = Story::with(['user'])->find($id);
        if(blank($post)){
            throw new \Exception("Post not found");
        }

        $validator = Validator::make($request->all(), [
            'media_path' => 'required',
            'media_type' => 'required',
//            'asset_id' => 'required',
//            'aspect_ratio' => 'required'
        ]);

        if($validator->fails()){
            return response()->json(ApiResponse::failedResponse($validator->errors()->first()));
        }

        $post->update([
            'media_path' => $request->get("media_path"),
            'media_type' => $request->get("media_type"),
            'asset_id' => $request->get("asset_id"),
            'aspect_ratio' => $request->get("aspect_ratio"),
        ]);
        $post->refresh();

        return response()->json(ApiResponse::successResponseWithData($post));

    }

    public function updateFeed($id, Request $request): \Illuminate\Http\JsonResponse {

        $purpose = $request->get("purpose");
        $mediaPath= $request->get("media_path");
        $mediaType= $request->get("media_type");
        $description = $request->get('description');
        $commentsDisabled = $request->get("comments_disabled");

        $story = Story::with(['user'])->find($id)->update([
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
