<?php
/**
 * Created by PhpStorm.
 * User: dannyk
 * Date: 5/24/21
 * Time: 4:55 AM
 */
return [
    // 'name' => env('APP_NAME', 'Laravel'),
    'currency' => env('CURRENCY', 'USD'),
    'basic_auth_username' => env('BASIC_AUTH_USERNAME', null),
    'basic_auth_password' => env('BASIC_AUTH_PASSWORD', null),

//    'sms_client_id' => env('SMS_CLIENT_ID', null),
//    'sms_api_key' => env('SMS_API_KEY', null),
//
    'telegram_channel_id' => env('TELEGRAM_CHAT_ID',null),
    'ipinfo_api_key' => env('IPINFO_API_KEY',null),
    'mux_token_id' => env('MUX_TOKEN_ID',null),
    'mux_token_secret' => env('MUX_TOKEN_SECRET',null),
    'mux_token_id_dev' => env('MUX_TOKEN_ID_DEV',null),
    'mux_token_secret_dev' => env('MUX_TOKEN_SECRET_DEV',null),

    'one_signal_app_id' => env("ONE_SIGNAL_APP_ID", null),
    'one_signal_api_key' => env("ONE_SIGNAL_API_KEY", null),
    'one_signal_api_url' => env("ONE_SIGNAL_API_URL", null),
    'one_signal_large_icon' => env("ONE_SIGNAL_LARGE_ICON", null),

];
