<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use JetBrains\PhpStorm\ArrayShape;

class UtilsController extends Controller
{
    //upload file to aws
    /**
     * @throws ValidationException
     */
    public function uploadFiles(Request $request): \Illuminate\Http\JsonResponse
    {

        $this->validate($request, [
            'files' => 'required|array'
        ]);

        $files = $request->file('files');
        $folder = $request->get('folder') ?: '';

        $paths = [];
        foreach ($files as $file) {
            // save file in s3 and return link to file
            $path = Storage::disk('s3')->putFile($folder, $file);
            $paths[] = $path;
        }

        return response()->json(ApiResponse::successResponseWithData($paths));
    }
}
