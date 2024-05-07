<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FilterController extends Controller
{
    public function setFilter(Request $request): \Illuminate\Http\JsonResponse
    {

        $minAge =  $request->get('min_age') ?: 16;
        $maxAge =  $request->get('max_age') ?: 80;
        $gender =  $request->get('gender') ?: 'any';
        $countryOption =  $request->get('countries_option') ?: 'all';
        $countryIds = $request->get('countries_ids');

        if($countryOption != 'all'){
            if(empty($countryIds)){
                return response()->json('Countries not specified');
            }
        }

        $user = $request->user();
//        $filter = $this->getOrCreateUserFilter($user->id);
//
//
//        $filter->update([
//            'min_age' => $minAge,
//            'max_age' => $maxAge,
//            'gender' =>  $gender,
//            'countries_option' => $countryOption
//        ]);

        return response()->json(ApiResponse::successResponse());

    }

    public function getFilter(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
//        $filters = $this->getOrCreateUserFilter($user->id);
        return response()->json();
    }


}
