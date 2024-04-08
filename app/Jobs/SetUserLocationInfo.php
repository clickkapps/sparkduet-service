<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserInfo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SetUserLocationInfo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public UserInfo $userInfo, public string $userIp){ }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {

            $appEnv = config("app.env");
            $userIp = $appEnv == "local" ? "41.155.61.195" : $this->userIp;
            $ipInfoApiKey = config('custom.ipinfo_api_key');
            $path = "https://ipinfo.io/$userIp?token=$ipInfoApiKey";
            Log::info("location IP: $userIp");
            Log::info("location path: $path");
            $response = Http::get($path);

            if($response->failed()){
                Log::info('request failed: ' . $response->reason());
                return;
            }

            $responseBodyAsString = $response->body(); // string
            $responseBody = $response->object(); // object
            Log::info("response: $responseBodyAsString");
            $city = $responseBody->{'city'}; // eg. "Accra"
            $region = $responseBody->{'region'}; // eg. "Greater Accra"
            $country = $responseBody->{'country'}; // eg. "GH"
            $loc = $responseBody->{'loc'}; // eg. "5.5560,-0.1969"
            $timezone = $responseBody->{'timezone'}; //eg. "Africa/Accra",

            $this->userInfo->update([
                'city' => $city,
                'country' => $country,
                'region' => $region,
                'loc' => $loc,
                'timezone' => $timezone,
            ]);


        }catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }
}
