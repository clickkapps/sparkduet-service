<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->middleware('basicAuth')->group(function () {
    Route::post('email', [\App\Http\Controllers\AuthController::class, 'sendAuthEmailVerificationCode']);
    Route::post('email/verify', [\App\Http\Controllers\AuthController::class, 'verifyAuthEmail']);
});


/*
|--------------------------------------------------------------------------
| Users Routes
|--------------------------------------------------------------------------
*/

Route::prefix('user')->middleware('auth:sanctum')->group(function () {
    Route::get('profile/{id?}', [\App\Http\Controllers\UserController::class, 'getUserProfile']);
});


/*
|--------------------------------------------------------------------------
| Stories Routes
|--------------------------------------------------------------------------
*/

Route::prefix('stories')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [\App\Http\Controllers\StoryController::class, 'fetchStories']);
});
