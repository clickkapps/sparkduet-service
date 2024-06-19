<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class CommentController extends Controller
{
    public function createComment(Request $request): \Illuminate\Http\JsonResponse
    {
        Redis::set('name', 'Ishmael');
        // learn more about redis
        //https://www.youtube.com/watch?v=2WQO3PvtAAw&list=PLS1QulWo1RIYZZxQdap7Sd0ARKFI-XVsd&index=5&ab_channel=ProgrammingKnowledge
        return response()->json(ApiResponse::successResponse('redis item created'));
    }
}
