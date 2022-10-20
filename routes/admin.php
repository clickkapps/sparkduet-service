<?php

use App\Classes\ApiResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(ApiResponse::successResponse('Admin api route is running'));
});
