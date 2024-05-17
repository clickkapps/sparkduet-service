<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use App\Models\User;
use App\Traits\UserTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    use UserTrait;

}
