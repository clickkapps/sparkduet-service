<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function getUserProfile(Request $request, $id = null): \Illuminate\Http\JsonResponse
    {
        // if userId is null get the authenticated user profile
        $userId = $request->user()->id;

        if(!blank($id)){
            $userId = $id;
        }

//        Log::info("userId: ${$userId}");

        $user = User::with(['info'])->find($userId);

//        return response()->json(ApiResponse::successResponseV2($user));
        return response()->json(ApiResponse::successResponseWithData($user));

    }
}
