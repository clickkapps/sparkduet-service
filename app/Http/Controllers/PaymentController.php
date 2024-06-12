<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\SubscriptionUpdated;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function revenueCatWebhookCallback(Request $request): \Illuminate\Http\JsonResponse
    {
        $event = $request->all()['event'];
        json_encode("Revcat callback payload => " . json_encode($event));
        $productId = $event['product_id'] ?? "";
        $appUserId = $event['app_user_id'] ?? "";
        $type = $event['type'] ?? "";
        $admin = getAdmin();
        if(!blank($admin)) {
            $admin->notify(new SubscriptionUpdated(appUserId: $appUserId, type: $type, productId: $productId));
        }
        return response()->json();
    }
}
