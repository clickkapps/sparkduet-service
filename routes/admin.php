<?php

use App\Classes\ApiResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(ApiResponse::successResponse('Admin api route is running'));
});

Route::prefix('auth')->middleware('basicAuth')->group(function () {
    Route::post('login', [\App\Http\Controllers\AuthController::class, 'adminLogin']);
});

/*
|--------------------------------------------------------------------------
| Users Routes
|--------------------------------------------------------------------------
*/
Route::prefix('user')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('create-notice', [\App\Http\Controllers\UserController::class, 'createUserNotice']);
    Route::post('delete-notice', [\App\Http\Controllers\UserController::class, 'deleteUserNotice']);
    Route::post('take-disciplinary-action', [\App\Http\Controllers\UserController::class, 'takeDisciplinaryActionOnUser']);
});


Route::prefix('stories')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('take-disciplinary-action', [\App\Http\Controllers\StoryController::class, 'takeDisciplinaryActionOnStory']);
});



