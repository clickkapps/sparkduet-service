<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use JetBrains\PhpStorm\ArrayShape;
use MuxPhp\Configuration;
use MuxPhp\Api\DirectUploadsApi;
use GuzzleHttp\Client;
use MuxPhp\Models\CreateAssetRequest;
use MuxPhp\Models\CreateUploadRequest;
use MuxPhp\Models\PlaybackPolicy;

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


    public function getMuxUploadUrl(Request $request): \Illuminate\Http\JsonResponse
    {

        // Authentication Setup
        $config = Configuration::getDefaultConfiguration()
            ->setUsername(getenv('MUX_TOKEN_ID'))
            ->setPassword(getenv('MUX_TOKEN_SECRET'));

        $uploadsApi = new DirectUploadsApi(
            new Client(),
            $config
        );

        $appUrl = config('app.url');
        $createAssetRequest = new CreateAssetRequest(["playback_policy" => [PlaybackPolicy::_PUBLIC], "encoding_tier" => "baseline"]);
        $createUploadRequest = new CreateUploadRequest(["timeout" => 3600, "new_asset_settings" => $createAssetRequest, "cors_origin" => $appUrl]);
        $upload = $uploadsApi->createDirectUpload($createUploadRequest);

        return response()->json(ApiResponse::successResponseWithData($upload));
    }
}
