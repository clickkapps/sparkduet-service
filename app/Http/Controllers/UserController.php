<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use App\Models\ProfileView;
use App\Models\StoryReport;
use App\Models\User;
use App\Models\UserBlock;
use App\Models\UserNotice;
use App\Traits\UserTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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

        $paginated = ProfileView::with(['viewer', 'profile'])->where([
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


    public function reportUser(Request $request, $postId): \Illuminate\Http\JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'reason' => 'required',
            'offender_id' => 'required'
        ]);

        if($validator->fails()) {
            return response()->json(ApiResponse::failedResponse('state the reason for reporting'));
        }

        $user = $request->user();
        $reason = $request->get('reason');

        DB::table('user_reports')->insert([
            'user_id' => $user->id,
            'offender_id' => $user->id,
            'reason' => $reason,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(ApiResponse::successResponse());

    }

    /**
     * @throws ValidationException
     */
    public function createUserNotice(Request $request): JsonResponse
    {

        $this->validate($request, [
            'notice' => 'required',
            'user_id' => 'required'
        ]);

        $userId = $request->get('user_id');
        $notice = $request->get('notice');
        $link = $request->get('link') ?: null;

        DB::table('user_notices')->insert([
            'user_id' => $userId,
            'notice' => $notice,
            'link' => $link,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(ApiResponse::successResponse());
    }

    /**
     * @throws ValidationException
     */
    public function deleteUserNotice(Request $request): JsonResponse
    {

        $this->validate($request, [
            'notice_id' => 'required',
        ]);

        $noticeId = $request->get('notice_id');

        DB::table('user_notices')->where('id' , $noticeId)->delete();

        return response()->json(ApiResponse::successResponse());
    }

    // Any notice served to the user by the admin
    public function getUserNotice(Request $request): JsonResponse {
        $user = $request->user();
        $notice = UserNotice::with('user')->where([
            'user_id' => $user->{'id'},
            'notice_read_at' => null
        ])->orderByDesc('created_at')->first();

        return response()->json(ApiResponse::successResponseWithData($notice));
    }

    // Any notice served to the user by the admin
    /**
     * @throws ValidationException
     */
    public function markNoticeAsRead(Request $request): JsonResponse {

        $this->validate($request, [
            'notice_id' => 'required',
        ]);
        $noticeId = $request->get('notice_id');
        UserNotice::with([])->where([
            'id' => $noticeId,
        ])->update(['notice_read_at' => now()]);

        return response()->json(ApiResponse::successResponse());
    }


    /**
     * @throws ValidationException
     */
    public function userBlocksOffender(Request $request): JsonResponse {

        $this->validate($request, [
            'offender_id' => 'required',
        ]);

        $user = $request->user();
        $offenderId = $request->get('offender_id');
        $reason = $request->get('reason');

        $userBlock = UserBlock::with([])->where([
            'initiator_id' => $user->{'id'},
            'offender_id' => $offenderId,
        ])->firstOrCreate();


        if(!blank($reason)) {
            $userBlock->update(['reason' => $reason]);
        }

        return response()->json(ApiResponse::successResponse());
    }

    /**
     * @throws ValidationException
     */
    public function userUnblocksOffender(Request $request): JsonResponse {

        $this->validate($request, [
            'offender_id' => 'required',
        ]);

        $user = $request->user();
        $offenderId = $request->get('offender_id');

        $userBlock = UserBlock::with([])->where([
            'initiator_id' => $user->{'id'},
            'offender_id' => $offenderId,
        ])->first();


        if(!blank($userBlock)) {
            $userBlock->delete();
        }

        return response()->json(ApiResponse::successResponse());
    }

    // For admins
    // Change user status -> ["banned", "warned"]
    /**
     * @throws ValidationException
     * @throws \Exception
     */
    public function takeDisciplinaryActionOnUser(Request $request) {

        $this->validate($request, [
            'user_id' => 'required',
            'disciplinary_action' => 'required'
        ]);

        $admin = $request->user();
        $userId = $request->get('user_id');
        $disciplinaryAction = $request->get('disciplinary_action');

        $offender = User::with([])->where(['id' => $userId])
            ->first();

        if(blank($offender)) {
            throw new \Exception("Invalid offender id");
        }

        $offender->update([
            'disciplinary_action' => $disciplinaryAction,
            'disciplinary_action_taken_at' => now(),
            'disciplinary_action_taken_by' => $admin->{'id'}
        ]);
    }

}
