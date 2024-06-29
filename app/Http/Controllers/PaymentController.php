<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\SubscriptionUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function revenueCatWebhookCallback(Request $request): \Illuminate\Http\JsonResponse
    {
        $event = $request->all()['event'];
//        json_encode("Revcat callback payload => " . json_encode($event));
        $productId = $event['product_id'] ?? "";
        $appUserId = $event['app_user_id'] ?? "";
        $type = $event['type'] ?? "";
        $environment = $event['environment'] ?? "";

        /// later move this block of code to live
        $created = DB::table('daily_subscriptions_records')->whereDate('created_at', '=', today())->first();
        if($type == "INITIAL_PURCHASE" || $type == "RENEWAL" || $type == "UNCANCELLATION" || $type == "SUBSCRIPTION_EXTENDED") {
            // subscriptions
            if($created) {
                $counter = $created->{'sub_counter'} + 1;
                $created->update([
                    'sub_counter' => $counter
                ]);
            }else{
                DB::table('daily_subscriptions_records')->insert([
                    'sub_counter' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
        if($type == "CANCELLATION" || $type == "SUBSCRIPTION_PAUSED" || $type == "EXPIRATION") {
            // unsubscriptions
            if($created) {
                $counter = $created->{'unsub_counter'} + 1;
                $created->update([
                    'unsub_counter' => $counter
                ]);
            }else{
                DB::table('daily_subscriptions_records')->insert([
                    'unsub_counter' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // notify us for only live purchases
        if($environment != "SANDBOX") {
            $admin = getAdmin();
            if(!blank($admin)) {
                $admin->notify(new SubscriptionUpdated(appUserId: $appUserId, type: $type, productId: $productId, environment: $environment));
            }
        }else {
            json_encode("Revcat: " . json_encode($event));
        }

        return response()->json();
    }
}
