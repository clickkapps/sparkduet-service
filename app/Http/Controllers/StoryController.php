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
    public function fetchStoryFeeds(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $query = Story::with(['countries', 'media'])
            ->withCount(['likes', 'views'])
            ->where('feed_expiry_date', '>=', now())
            ->where('blocked', '=', false);

        /// apply filters if any
        if($user->filter) {
            // Filter fields -------
            //     'min_age',
            //        'max_age',
            //        'gender',
            //        'countries_option'
            // Filter fields -------

            // Story fields ---------
            //        'targeted_gender',
            //        'targeted_min_age',
            //        'targeted_max_age',
            //        'description',
            //        'feed_expiry_date',
            //        'targeted_countries_option',
            // End of Story fields ---------
            $minAge = $user->filter->{'min_age'};
            $maxAge = $user->filter->{'max_age'};
            $gender = $user->filter->{'gender'};
            $countriesOption = $user->filter->{'countries_option'};

            if($minAge) {
                $query->where('targeted_min_age', '>=', $minAge);
            }
            if($maxAge) {
                $query->where('targeted_max_age', '<=', $maxAge);
            }
            if($gender) {
                $anyGender = ($gender == 'any' || $gender == 'both');
                if(!$anyGender){
                    $query->where('targeted_gender', '=', $gender);
                }

            }

//            if($countriesOption && $countriesOption != 'all'){
//                if($countriesOption == 'except') {
//
//                }
//            }

        }


        $query->inRandomOrder();

        /// calculate match percentage

        $stories = $query->paginate()->through(function ($story, $key) use ($user){


            $mappedStory['id'] = $story->id;
            $mappedStory['description'] = $story->{'description'};
            $mappedStory['comments_enabled'] = $story->{'description'};
            $mappedStory['total_comments'] = $story->{'total_comments'};
            $mappedStory['likes_count'] = $story->{'likes_count'};
            $mappedStory['views_count'] = $story->{'views_count'};
            $mappedStory['liked'] = collect($story->likes)->contains('user_id', '=', $user->id);
            $mappedStory['bookmarked'] =  collect($story->bookmarks)->contains('user_id', '=', $user->id);
            $mappedStory['media'] = $story->{'media'};
            $mappedStory['user'] = $story->{'user'};
            $mappedStory['created_at'] = Carbon::parse($story->{'created_at'})->diffForHumans();

            // picking only needed properties for efficiency of payload
            return $mappedStory;
        });

        return response()->json(ApiResponse::successResponseV2($stories));
    }

    public function createStory(Request $request): \Illuminate\Http\JsonResponse
    {

        try {

            $user = $request->user();
            // if user is blocked, user cannot post a story


            $validator = Validator::make($request->all(), [
                'files' => 'required|array',
                'files_meta' => 'required|array',
            ]);

            if($validator->fails()){
                return response()->json(ApiResponse::failedResponse($validator->errors()->first()));
            }

            $description = $request->get('description');
            $targetedGender = $request->get('targeted_gender') ?: 'any';
            $targetedMinAge = $request->get('targeted_min_age') ?: 16;
            $targetedMaxAge = $request->get('targeted_max_age') ?: 70;
            $feedExpiryDate = $request->get('feed_expiry_date') ?: now()->addDays(30);
            $targetedCountriesOption = $request->get('targeted_countries_option') ?: "all"; // all / except / only
            $targetedCountriesValues = $request->get('targeted_countries_values');
            $commentsEnabled = $request->get('comments_enabled') ?: true;


            $targetedCountries = [];
            if($targetedCountriesOption != 'all') {

                if(!is_array($targetedCountriesValues) || empty($targetedCountriesValues)) {
                    Log::info('targeted_countries_values: ' .json_encode($targetedCountriesValues));
                    return response()->json(ApiResponse::failedResponse('Please specify targeted countries'));
                }

            }

            $story = Story::create([
                'user_id' => $user->id,
                'targeted_gender' => $targetedGender,
                'targeted_min_age' => $targetedMinAge,
                'targeted_max_age' => $targetedMaxAge,
                'description' => $description,
                'feed_expiry_date' => $feedExpiryDate,
                'targeted_countries_option' => $targetedCountriesOption,
                'comments_enabled' => $commentsEnabled
            ]);


            if($targetedCountriesOption != 'all') {

                foreach ($targetedCountriesValues as $countryId ) {
                    $targetedCountries[] = [
                        'story_id' => $story->id,
                        'country_id' => $countryId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }


                DB::table('story_countries')->insert($targetedCountries);
            }

            $uploadedFiles = [];

            if(!$request->has('files')){
                return response()->json(ApiResponse::failedResponse('Add photos or videos'));
            }

            $files = $request->file('files');
            $filesMeta = $request->get('files_meta');


            $countFiles = count($files);
            $countFilesMeta = count($filesMeta);


            Log::info('files count: ' . json_encode($countFiles));
            Log::info('files_meta count: ' . json_encode($countFilesMeta));
            Log::info('files_meta: ' . json_encode($filesMeta));

            if($countFiles != $countFilesMeta) {
                return response()->json(ApiResponse::failedResponse('specify the colorFilters and selected music for files'));
            }

            if(!$request->has('files')){
                return response()->json(ApiResponse::failedResponse('Add photos or videos'));
            }


            foreach ($files as $index => $file){

                $meta = $filesMeta[$index];

                // Automatically generate a unique ID for filename...
                $path = Storage::putFile('stories', $file , 'public');
                $size = Storage::size($path);
                $type = Storage::mimeType($path);
                $name = pathinfo($path)['basename'];
                $url  = Storage::url($path);

                $uploadedFiles[] = [
                    'path' => $url,
                    'size' => $size,
                    'type' => $type,
                    'name' => $name,
                    'source' => 'stories',
                    'story_id' => $story->id,
                    'color_filter' => $meta['color_filter'],
                    'background_music' =>  $meta['background_music'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                Log::info('media uploaded: ' . json_encode($uploadedFiles));

            }


            DB::table('medias')->insert($uploadedFiles);

            $story = Story::with('media')->where(['id' => $story->id])->first();

            return response()->json(ApiResponse::successResponseV2($story));


        }catch (\Exception $e) {
            $message = $e->getMessage();
            Log::error($message);
            return response()->json(ApiResponse::failedResponse($message));
        }


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

        $exists = $table->where([
            'user_id' => $user->{'id'},
            'story_id' => $storyId
        ])->exists();


        if(!$exists) {
            $table->insert([
                'user_id' => $user->id,
                'story_id' => $storyId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }


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
