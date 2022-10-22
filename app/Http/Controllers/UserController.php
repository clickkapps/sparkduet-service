<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getUserProfile(Request $request, $id = null): \Illuminate\Http\JsonResponse
    {
        // if userId is null get the authenticated user profile
        $user = $request->user();
        if(!blank($id)){
            $user = User::find($id);
        }

        return response()->json(ApiResponse::successResponseV2($user));

    }
}
