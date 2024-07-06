<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use App\Events\StoryDisciplinaryActionTakenEvent;
use App\Models\Story;
use App\Models\StoryBookmark;
use App\Models\StoryLike;
use App\Models\StoryReport;
use App\Models\StoryView;
use App\Models\User;
use App\Notifications\StoryReportCreated;
use App\Traits\StoryTrait;
use App\Traits\UserTrait;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StoryController extends Controller
{

    use UserTrait, StoryTrait;

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
        $userId = $user->{'id'};
//        $query = Story::with(['user.info'])
//            ->where("user_id", "!=", $user->id)
//            ->where([
//                "deleted_at" => null,
//                "disciplinary_action" => null
//            ])
//            ->where('media_path', '!=', "");

        // apply user's personalization

        /// Preferences
        // Get preferred gender
        $preferredGenderOutput = [];
        if(!blank($user->info->{'preferred_gender'})) {
            $preferredGender = json_decode($user->info->{'preferred_gender'});
            //eg.  [ any ] , ["women","men","transgenders","non_binary_or_non_conforming"]
            foreach ($preferredGender as $gender) {
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
//            if($gender == "any") {
//                $preferredGenderOutput = ["female","male","transgender","non_binary_or_non_conforming"];
//            }else {
//
//            }
            }
        }

        // Get preferred minimum age and max age
        $preferredMinAge = $user->info->{"preferred_min_age"} ?? 18; // eg. 18
        $preferredMaxAge = $user->info->{"preferred_max_age"} ?? 70; // eg. 70

        // Get preferred races
        $preferredRacesOutput = [];
        if(!blank($user->info->{'preferred_races'})) {
            $preferredRaces = json_decode($user->info->{'preferred_races'});
            foreach ($preferredRaces as $race) {
//            if($race == "other") {
//                $preferredRacesOutput = [];
//            }
                if($race != "other") {
                    $preferredGenderOutput[] = $race;
                }
            }
        }
        // eg. ["white","hispanic_latino_or_spanish_origin","middle_eastern_or_north_african","native_hawaiian_or_other_pacific_islander","black_or_african_american","asian"]

        // Get preferred nationalities
        $includedNationalities = [];
        $excludedNationalities = [];
        if(!blank($user->info->{'preferred_nationalities'})) {
            $preferredNationalities = json_decode($user->info->{'preferred_nationalities'}, true);
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


        // Fetch stories with users' age between minAge and maxAge
        // Build the filtered query with joins and initial filters

//        $query = Story::with(['user.info'])
//            ->leftJoin('story_views', function ($join) use ($userId) {
//                $join->on('stories.id', '=', 'story_views.story_id')
//                    ->where('story_views.user_id', '=', $userId);
//            })
//            ->where('story_views.user_id', '=', null)
//            ->where('stories.user_id', '!=', $user->id)
//            ->whereNull('stories.deleted_at')
//            ->whereNull('stories.disciplinary_action')
//            ->where('stories.media_path', '!=', '')
//            ->join('users', 'stories.user_id', '=', 'users.id')
//            ->join('user_infos', 'users.id', '=', 'user_infos.user_id')
//            ->whereBetween('user_infos.age', [$preferredMinAge, $preferredMaxAge]);

        // Build the filtered query with joins and initial filters
        // Build the filtered query with joins and initial filters
        $query = Story::with(['user.info'])
            ->leftJoin('story_views', function ($join) use ($userId) {
                $join->on('stories.id', '=', 'story_views.story_id')
                    ->where('story_views.user_id', '=', $userId);
            })
            ->leftJoin('user_blocks as b1', function ($join) use ($userId) {
                $join->on('stories.user_id', '=', 'b1.offender_id')
                    ->where('b1.initiator_id', '=', $userId);
            })
            ->leftJoin('user_blocks as b2', function ($join) use ($userId) {
                $join->on('stories.user_id', '=', 'b2.initiator_id')
                    ->where('b2.offender_id', '=', $userId);
            })
            ->join('users', 'stories.user_id', '=', 'users.id')
            ->join('user_infos', 'users.id', '=', 'user_infos.user_id')
//            ->whereNull('story_views.user_id')
//            ->whereNull('b1.id')
//            ->whereNull('b2.id')
//            ->where('stories.user_id', '!=', $userId)
            ->whereNull('stories.deleted_at') // Exclude deleted stories
//            ->whereNull('stories.disciplinary_action')
//            ->where('stories.media_path', '!=', '')
//            ->whereNull('users.banned_at') // Exclude banned users
//            ->whereBetween('user_infos.age', [$preferredMinAge, $preferredMaxAge])
        ;

//        if (!empty($preferredGenderOutput)) {
//            $query->whereIn('user_infos.gender', $preferredGenderOutput);
//        }
//
//        if (!empty($preferredRace)) {
//            $query->whereIn('user_infos.race', $preferredRace);
//        }
//
//        // Apply nationality filters based on the presence of included or excluded nationalities
//        if (!empty($includedNationalities)) {
//            $query->whereIn('user_infos.country', $includedNationalities);
//        } elseif (!empty($excludedNationalities)) {
//            $query->whereNotIn('user_infos.country', $excludedNationalities);
//        }

        // Select only the stories columns and paginate
        $stories = $query->select('stories.*')->simplePaginate($request->get('limit') ?: 3);

        // If the filtered query results are empty, fallback to retrieving all stories except those already viewed by the user
//        if ($stories->isEmpty()) {
//            $stories = Story::with(['user.info'])
////                ->leftJoin('story_views', function ($join) use ($userId) {
////                    $join->on('stories.id', '=', 'story_views.story_id')
////                        ->where('story_views.user_id', '=', $userId);
////                })
////                ->where('story_views.user_id', '=', null)
//                ->where('stories.user_id', '!=', $user->id)
//                ->whereNull('stories.deleted_at')
//                ->whereNull('stories.disciplinary_action')
//                ->where('stories.media_path', '!=', '')
//                ->simplePaginate($request->get('limit') ?: 3);
//        }


        $updatedItems = $this->setAdditionalFeedParameters($request, $stories);

        $merged = $updatedItems->getCollection();

        $pageKey = $request->get('page');
        Log::info("page=".$pageKey);

        $introductoryPost = Story::with([])->where(["user_id" =>  $user->id, "purpose" => "introduction"])->first();
        if(blank($introductoryPost)) {
            if($pageKey == 1) {
                $merged = $updatedItems->concat([
                    [ 'id' => -1 ], /// Introductory video
                ]);
            }
        }else {
            $expectationPost = Story::with([])->where(["user_id" =>  $user->id, "purpose" => "expectations"])->first();
            if(blank($expectationPost)) {
                if($pageKey == 2) {
                    $merged = $updatedItems->concat([
                        [ 'id' => -2 ], /// Introductory video
                    ]);
                }
            }else {
                if($pageKey == 3) {
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
        }

//        if($updatedItems->isEmpty()) {
//
//            /// This prompts the user to create post
//
//
//        }else {
//
//            if(blank($introductoryPost)) {
//                // insert introductory post at position 2 (index 1)
//                $merged = $updatedItems->concat([
//                    [ 'id' => -1 ], /// Introductory video
//                ]);
//            }
//
//        }

        $stories->setCollection($merged);


        return response()->json(ApiResponse::successResponseWithData($stories));
    }

    public function fetchUserPosts(Request $request, $userId) : \Illuminate\Http\JsonResponse {


        $query = Story::with(['user.info'])
            ->where([
                "user_id" => $userId,
                "deleted_at" => null,
                "disciplinary_action" => null
            ])
            ->where('media_path', '!=', "")
            ->withCount(['likes', 'views'])
            ->orderByDesc("created_at");

        $posts = $query->simplePaginate($request->get("limit") ?: 9);

        $posts = $this->setAdditionalFeedParameters($request, $posts);

        return response()->json(ApiResponse::successResponseWithData($posts));
    }


    public function fetchUserBookmarkedPosts(Request $request, $userId) : \Illuminate\Http\JsonResponse {

        // Fetch the user with relationships
        $user = User::with(['bookmarkedStories.user.info'])->findOrFail($userId);

        // Get the paginated bookmarked stories
        $posts = $user->bookmarkedStories()
            ->with('user.info')
            ->where([
                "deleted_at" => null,
                "disciplinary_action" => null
            ])
            ->where('media_path', '!=', "")
            ->simplePaginate($request->get("limit") ?: 9); // Adjust the per-page limit as needed

        $posts = $this->setAdditionalFeedParameters($request, $posts);

        return response()->json(ApiResponse::successResponseWithData($posts));
    }


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
    public function likeStory(Request $request, $postId): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'action' => 'required'
        ]);

        if($validator->fails()) {
            return response()->json(ApiResponse::failedResponse($validator->errors()->first()));
        }

        $action = $request->get('action');
        $record = StoryLike::with([])->firstOrCreate(
            [
                'user_id' => $user->{'id'},
                'story_id' => $postId
            ],
            []
        );
        if($action == 'add') {

            $record->update([
                'count' => ($record->{'count'} ?? 0) + 1
            ]);
        }else {
            // remove
            $record->update([
                'count' => 0
            ]);
        }


        return response()->json(ApiResponse::successResponse());

    }


    public function bookmarkStory(Request $request, $storyId): \Illuminate\Http\JsonResponse
    {

        $user = $request->user();
        $table = DB::table('story_bookmarks');

        $this->toggleStoryTable($table, $storyId, $user);

        return response()->json(ApiResponse::successResponse());

    }

    public function viewStory(Request $request, $postId): \Illuminate\Http\JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'action' => 'required'
        ]);

        if($validator->fails()) {
            return response()->json(ApiResponse::failedResponse($validator->errors()->first()));
        }

        $user = $request->user();
        $action = $request->get('action');

        if (!in_array($action, ['seen', 'watched'])) {
            return response()->json(ApiResponse::failedResponse("Invalid request"));
        }

        $record = StoryView::with([])->firstOrCreate(
            [
                'user_id' => $user->{'id'},
                'story_id' => $postId
            ],
            []
        );

        $now = now();
        if($action == 'seen') {
            $record->update([
                "seen_at" => $now
            ]);
        }else {
            $payload = [];
            if(blank($record->{'watched_created_at'})){
                $payload['watched_created_at'] = $now;
            }
            $payload['watched_updated_at'] = $now;
            $payload['watched_count'] = ($record->{'watched_count'} ?? 0) + 1;

            $record->update($payload);
        }

        return response()->json(ApiResponse::successResponse());

    }


    public function reportStory(Request $request, $postId): \Illuminate\Http\JsonResponse
    {

        $validator = Validator::make($request->all(), [
           'reason' => 'required'
        ]);

        if($validator->fails()) {
            return response()->json(ApiResponse::failedResponse('state the reason for reporting'));
        }

        $user = $request->user();
        $reason = $request->get('reason');

        StoryReport::with([])->create([
            'user_id' => $user->id,
            'story_id' => $postId,
            'reason' => $reason,
        ]);

//        $reported->{'user'}->notify(new StoryReportCreated(storyId: $reported->{'id'}, reason: $reason));

        return response()->json(ApiResponse::successResponse());

    }

    // For admins
    // Change user status -> ["banned", "warned"]
    /**
     * @throws ValidationException
     * @throws \Exception
     */
    public function takeDisciplinaryActionOnStory(Request $request): \Illuminate\Http\JsonResponse {

        $this->validate($request, [
            'story_id' => 'required',
            'disciplinary_action' => 'required'
        ]);

        $admin = $request->user();
        $storyId = $request->get('story_id');
        $disciplinaryAction = $request->get('disciplinary_action');

        $story = Story::with([])->where(['id' => $storyId])
            ->first();

        if(blank($story)) {
            throw new \Exception("Invalid story id");
        }

        $story->update([
            'disciplinary_action' => $disciplinaryAction == "removed" ? null : $disciplinaryAction,
            'disciplinary_action_taken_at' => now(),
            'disciplinary_action_taken_by' => $admin->{'id'}
        ]);

        event(new StoryDisciplinaryActionTakenEvent(storyId: $storyId, disAction: $disciplinaryAction));

        return response()->json(ApiResponse::successResponse());
    }

    public function deleteStory($id): \Illuminate\Http\JsonResponse {

        $story = Story::with([])->find($id);
        $story->update(['deleted_at' => now()]);

        return response()->json(ApiResponse::successResponse());

    }


}
