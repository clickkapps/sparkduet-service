<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use App\Models\Story;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SearchController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function searchTopResults(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->validate($request, [
           'terms' => 'required'
        ]);

        $terms = $request->get("terms");

        $users = User::search(''.$terms)->take(5)->get();
        $stories = Story::search(''.$terms)->take(5)->get();
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

        $users = Story::search(''.$terms)->simplePaginate($request->get("limit") ?: 15);
        return response()->json(ApiResponse::successResponseWithData($users));
    }

    /**
     * @throws ValidationException
     */
    public function searchPeople(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->validate($request, [
            'terms' => 'required'
        ]);

        $terms = $request->get("terms");

        $users = User::search(''.$terms)->simplePaginate($request->get("limit") ?: 15);
        return response()->json(ApiResponse::successResponseWithData($users));
    }

    /**
     * @throws ValidationException
     */
    public function addSearchTerm(Request $request) {
        $this->validate($request, [
            'terms' => 'required'
        ]);

        // save this term in the user histories

        // add to frequently searched items


    }


    public function getSearchTerm(Request $request) {

        // get saved term from the user histories

        // get  frequently searched items


    }
}
