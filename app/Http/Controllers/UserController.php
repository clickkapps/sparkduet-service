<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use App\Models\User;
use App\Traits\UserTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    use UserTrait;

    /**
     * @throws \Exception
     */
    public function recordProfileView(Request $request): JsonResponse
    {
        $viewer = $request->user();
        $profileId = $request->get('profile_id');
        $viewerId = $viewer->{'id'};
        if(blank($profileId)) {
            throw new \Exception("Invalid request");
        }

        $exists = DB::table('profile_views')->where([
            'viewer_id' => $viewerId,
            'profile_id' => $profileId
        ])->exists();
        if(!$exists) {
            DB::table('profile_views')->insert([
                'viewer_id' => $viewerId,
                'profile_id' => $profileId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        return response()->json(ApiResponse::successResponse());
    }


    /**
     * @throws \Exception
     */
    public function markProfileViewsAsRead(Request $request): JsonResponse
    {
//        $profileOwner = $request->user();
        $ids = $request->get('ids'); // profile_views table ids
        if(blank($ids) || !is_array($ids) ) {
            throw new \Exception("Invalid request");
        }
        DB::table('profile_views')->whereIn('id', $ids)->update([
            'profile_owner_read_at' => now()
        ]);
        return response()->json(ApiResponse::successResponse());
    }

    public function fetchUnreadProfileViewers(Request $request): JsonResponse
    {
        $profileOwner = $request->user();

        $paginated = DB::table('profile_views')->where([
            'profile_id' => $profileOwner->{'id'},
            'profile_owner_read_at' => null
        ])->simplePaginate($request->get('limit') ?: 10 );

        return response()->json(ApiResponse::successResponseWithData($paginated));
    }

    public function countUnreadProfileViews(Request $request): JsonResponse {

        $profileOwner = $request->user();
        $unreadProfileViews = DB::table('profile_views')->where([
            'profile_id' => $profileOwner->{'id'},
            'profile_owner_read_at' => null
        ])->count();

        return response()->json(ApiResponse::successResponseWithData($unreadProfileViews));

    }

}
