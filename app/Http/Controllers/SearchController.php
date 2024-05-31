<?php

namespace App\Http\Controllers;

use Algolia\AlgoliaSearch\SearchClient;
use App\Classes\ApiResponse;
use App\Models\Story;
use App\Models\User;
use App\Models\UserSearch;
use App\Traits\StoryTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SearchController extends Controller
{
    use StoryTrait;
    /***/
    /**
     * @throws ValidationException
     */
    public function searchTopResults(Request $request): \Illuminate\Http\JsonResponse
    {

        $user = $request->user();
        $this->validate($request, [
            'terms' => 'required'
        ]);
        $terms = $request->get("terms") ?: "";

        $users = User::search($terms)
            ->query(fn ($query) => $query->with('info'))
            ->simplePaginate(5);

        $stories = Story::search($terms)
            ->query(fn ($query) => $query->with('user.info'))
            ->simplePaginate(5);
        $stories = $this->setAdditionalFeedParameters($request, $stories);

        UserSearch::with([])->create([
            'user_id' => $user->{'id'},
            'query' => $terms,
        ]);
        return response()->json(ApiResponse::successResponseWithData([
            'users' => $users,
            'stories' => $stories
        ]));
    }

    /**
     * @throws ValidationException
     */
    public function searchStories(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->validate($request, [
            'terms' => 'required'
        ]);

        $terms = $request->get("terms");

        $stories = Story::search(''.$terms)
            ->query(fn ($query) => $query->with('user.info'))
            ->simplePaginate($request->get("limit") ?: 15);

        $stories = $this->setAdditionalFeedParameters($request, $stories);

        return response()->json(ApiResponse::successResponseWithData($stories));
    }

    /**
     * @throws ValidationException
     */
    public function searchPeople(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->validate($request, [
            'terms' => 'required'
        ]);

        $terms = $request->get("terms") ?: "";

        $users = User::search(''.$terms)
            ->query(fn ($query) => $query->with('info'))
            ->simplePaginate($request->get("limit") ?: 15);
        return response()->json(ApiResponse::successResponseWithData($users));
    }


    public function getUserSearchTerms(Request $request): \Illuminate\Http\JsonResponse
    {

        $user = $request->user();

        $popularSearches = UserSearch::with([])->select('query', DB::raw('count(*) as count'))
            ->where('user_id', $user->{'id'})
            ->groupBy('query')
            ->orderBy('count', 'desc')
            ->take(10) // Adjust the number as needed
            ->get();
        return response()->json(ApiResponse::successResponseWithData($popularSearches));
    }

    /**
     * @throws GuzzleException
     */
    public function popularSearches(): \Illuminate\Http\JsonResponse
    {
        $appId = config('scout.algolia.id');
        $apiKey = config('scout.algolia.secret');

        $client = new Client();

        $response = $client->get("https://analytics.algolia.com/2/searches", [
            'query' => [
                'index' => 'stories',
                'limit' => 10, // Adjust the limit as needed
            ],
            'headers' => [
                'X-Algolia-Application-Id' => $appId,
                'X-Algolia-API-Key' => $apiKey,
            ],
        ]);

        $popularSearches = json_decode($response->getBody()->getContents(), true);

        return response()->json(ApiResponse::successResponseWithData($popularSearches['searches']));
    }
}
