<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function revenueCatWebhookCallback(Request $request): \Illuminate\Http\JsonResponse
    {
        $event = $request->all()['event'];
        $revCatAppUserId = $event->payload['app_user_id'];
        json_encode("Revcat callback payload => " . json_encode($event->payload));
        return response()->json();
    }
}
