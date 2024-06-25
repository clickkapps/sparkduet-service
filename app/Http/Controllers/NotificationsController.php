<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use App\Events\NotificationsUpdatedEvent;
use App\Models\UserNotification;
use App\Traits\NotificationTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationsController extends Controller
{
    use NotificationTrait;
    public function fetchNotifications(Request $request): \Illuminate\Http\JsonResponse
    {
        Log::info('fetchNotifications called .....xxxx...');
        $user = $request->user();
        $limit = $request->get('limit') ?: 15;
        $userNotifications = $user->userNotifications()->with('user')->simplePaginate($limit);
        return response()->json(ApiResponse::successResponseWithData($userNotifications));
    }

    public function getCountUnseenNotificationsCount(Request $request): \Illuminate\Http\JsonResponse
    {

        $user = $request->user();
        $count = $this->countUnseenNotifications(user: $user);

        return response()->json(ApiResponse::successResponseWithData($count));

    }

    public function markNotificationsAsSeen(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $user->userNotifications()->whereNull('seen_at')->update([
           'seen_at' => now()
        ]);
        return response()->json(ApiResponse::successResponse());

    }

    public function markNotificationAsRead(Request $request, $id): \Illuminate\Http\JsonResponse {
        UserNotification::with([])->find($id)->update([
            'read_at' => now()
        ]);
        return response()->json(ApiResponse::successResponse());
    }


}
