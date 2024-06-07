<?php
/**
 * Created by PhpStorm.
 * User: dannyk
 * Date: 6/16/21
 * Time: 6:15 AM
 */

namespace App\Channels;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\Notification;


class PushNotificationChannel
{
    public function send($notifiable, Notification $notification): void {

        $requestArray = $notification->toPush($notifiable);

        Log::info('push request array: ' . json_encode($requestArray));

        $userId = $requestArray['userId'];
        $message = $requestArray['message'];
        $title = $requestArray['title'];

        $appId = config('custom.one_signal_app_id');
        $url = config('custom.one_signal_api_url');
        $largeIcon = config('custom.onesignal_large_icon');
        $apiKey = config('custom.one_signal_api_key');


        $postData = array(
            'app_id' => $appId,
            'include_aliases' => [
                'external_id' => [$userId]
            ],
            "target_channel" => "push",
            'contents' => ["en" => $message],
            'headings' => ["en" => $title],
            'large_icon' => $largeIcon
        );

        if (array_key_exists('data', $requestArray)) {
           $postData['data'] = $requestArray['data'];
        }

        Log::info('apiKey => ' . $apiKey);
        Log::info('url => ' . $url);
        Log::info('post data => ' . json_encode($postData));

        try{

            $response = Http::withToken($apiKey)->post($url, $postData);
            Log::info("push response: " . json_encode($response->json()));


        }catch (\Exception $e){
            Log::info("push notification error: " . $e->getMessage());
        }


    }
}
