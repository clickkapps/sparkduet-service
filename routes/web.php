<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
//    return phpinfo();
    $url = "https://sparkduet.com";
    return redirect()->away($url);
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::prefix('/auth')->group(function () {

    Route::get('/social', [\App\Http\Controllers\AuthController::class, 'redirectToOAuth2Provider']);
    Route::match(['post', 'get'],'/social/callback', [\App\Http\Controllers\AuthController::class, 'oAuth2ProviderCallback']);

});



