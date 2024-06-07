<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use App\Events\NotificationsUpdatedEvent;
use App\Traits\NotificationTrait;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    use NotificationTrait;
    public function fetchNotifications(): \Illuminate\Http\JsonResponse
    {
        $this->countNotifications();
        return response()->json(ApiResponse::successResponse());
    }
}
