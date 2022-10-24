<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use Illuminate\Http\Request;

class StoryController extends Controller
{
    public function fetchStories(): \Illuminate\Http\JsonResponse
    {
        return response()->json(ApiResponse::successResponseV2([]));
    }
}
