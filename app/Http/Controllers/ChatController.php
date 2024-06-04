<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use App\Models\User;
use App\Traits\UserTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    use UserTrait;

    public function fetchSuggestedChats(Request $request): \Illuminate\Http\JsonResponse
    {
        $authUser = $request->user();
        $suggestedUsers = User::with(['info'])->where('user_id', '!=', $authUser->{'id'})->get();

        $users = $suggestedUsers->map(function ($user) use ($authUser) {
            $user = $this->attachUserComputedAge($user);
            // Hide fields you don't want to include in the JSON response
            $user->makeHidden(['first_login_at', 'public_key', 'last_login_at']);
            return $user;
        });

        return response()->json(ApiResponse::successResponseWithData($users));
    }
}
