<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use MuxPhp\Api\AssetsApi;
use MuxPhp\ApiException;
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

    private function getMuxConfiguration(): Configuration {
        $muxTokenId = config('custom.mux_token_id_dev');
        $muxTokenSecret = config('custom.mux_token_secret_dev');

        Log::info("muxTokenId: $muxTokenId");
        Log::info("muxTokenSecret: $muxTokenSecret");


        // Authentication Setup
        return Configuration::getDefaultConfiguration()
            ->setUsername($muxTokenId)
            ->setPassword($muxTokenSecret);

    }
    private function getMuxDirectUploadInstance() : DirectUploadsApi {

        $config = $this->getMuxConfiguration();
        return new DirectUploadsApi(
            new Client(),
            $config
        );
    }

    /**
     * @throws ApiException
     */
    public function createMuxUploadUrl(): \Illuminate\Http\JsonResponse
    {

        $appUrl = config('app.url');
        Log::info("appUrl: $appUrl");

        $uploadInstance = $this->getMuxDirectUploadInstance();


        $createAssetRequest = new CreateAssetRequest(["playback_policy" => [PlaybackPolicy::_PUBLIC], "encoding_tier" => "baseline"]);
        $createUploadRequest = new CreateUploadRequest(["timeout" => 3600, "new_asset_settings" => $createAssetRequest, "cors_origin" => $appUrl]);

        $upload = $uploadInstance->createDirectUpload($createUploadRequest);

        return response()->json(ApiResponse::successResponseWithData($upload));
    }

    /**
     * @throws ValidationException
     * @throws ApiException
     */
    public function getMuxUploadStatus(Request $request): \Illuminate\Http\JsonResponse
    {

        $this->validate($request, [
            'videoId' => 'required'
        ]);

        $videoId = $request->get('videoId');

        $uploadInstance = $this->getMuxDirectUploadInstance();


        $result = $uploadInstance->getDirectUpload($videoId);

        return response()->json(ApiResponse::successResponseWithData($result));
    }

    /**
     * @throws ValidationException
     * @throws ApiException
     */
    public function getMuxVideoStatus(Request $request): \Illuminate\Http\JsonResponse {

        $this->validate($request, [
            'assetId' => 'required'
        ]);

        $assetId = $request->get('assetId');
        $config = $this->getMuxConfiguration();


        $apiInstance = new AssetsApi(
        // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
        // This is optional, `GuzzleHttp\Client` will be used as default.
            new Client(),
            $config
        );

        $result = $apiInstance->getAsset($assetId);
        return response()->json(ApiResponse::successResponseWithData($result));

    }
}
