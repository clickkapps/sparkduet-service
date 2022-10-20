<?php

namespace App\Classes;

use JetBrains\PhpStorm\Pure;

class ApiResponse
{
    public bool $status;
    public string $message;
    public mixed $extra;

    public function __construct(bool $status, string $message, mixed $extra)
    {
        $this->message = $message;
        $this->status = $status;
        $this->extra =  $extra;
    }

    #[Pure] static public function failedResponse($message = "FAILED", $extra = null): ApiResponse
    {
        return new ApiResponse(false,$message,$extra);
    }

    #[Pure] static public function successResponse($message = "SUCCESS", $extra = null): ApiResponse
    {
        return new ApiResponse(true,$message,$extra);
    }

    #[Pure] static public function successResponseV2($extra = null, $message = "SUCCESS"): ApiResponse
    {
        return new ApiResponse(true,$message,$extra);
    }

}
