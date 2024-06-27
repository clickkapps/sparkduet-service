<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use App\Events\UserBlocksOffenderEvent;
use App\Events\UserOnlineStatusChanged;
use App\Models\ProfileView;
use App\Models\Story;
use App\Models\StoryReport;
use App\Models\User;
use App\Models\UserBlock;
use App\Models\UserNotice;
use App\Models\UserOnline;
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
            'profile_id' => $profileId,
        ])->whereNull('profile_owner_read_at')->exists();
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
        if(blank($ids)) {
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

//        $paginated = ProfileView::with(['viewer', 'profile'])->where([
//            'profile_id' => $profileOwner->{'id'},
//        ])->orderByDesc('created_at')->simplePaginate($request->get('limit') ?: 10 );

        $distinctViewers = ProfileView::where('profile_id', $profileOwner->{'id'})
            ->groupBy('viewer_id')
            ->pluck('viewer_id');

        $paginated = ProfileView::with(['viewer', 'profile'])
            ->whereIn('viewer_id', $distinctViewers)
            ->where('profile_id', $profileOwner->{'id'})
            ->orderByDesc('created_at')
            ->simplePaginate($request->get('limit') ?: 10);

        return response()->json(ApiResponse::successResponseWithData($paginated));
    }

    public function countUnreadProfileViews(Request $request): JsonResponse {

        $profileOwner = $request->user();
        $unreadProfileViews = DB::table('profile_views')->where([
            'profile_id' => $profileOwner->{'id'},
            'profile_owner_read_at' => null
        ])->distinct('viewer_id')
            ->count();

        return response()->json(ApiResponse::successResponseWithData($unreadProfileViews));

    }


    public function reportUser(Request $request): \Illuminate\Http\JsonResponse
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
        ])->first();

        if(!$userBlock) {
            UserBlock::with([])->create([
                'initiator_id' => $user->{'id'},
                'offender_id' => $offenderId,
                'reason' => $reason
            ]);
            event(new UserBlocksOffenderEvent(offenderId: $offenderId));
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


        if($userBlock) {
            $userBlock->delete();
            event(new UserBlocksOffenderEvent(offenderId: $offenderId));
        }


        return response()->json(ApiResponse::successResponse());
    }

    public function getUserBlockUserStatus(Request $request): JsonResponse {
        $user = $request->user();
        $profileId = $request->get('profile_id');

        $youBlockedUser = UserBlock::with([])->where('initiator_id', '=', $user->{'id'})
            ->where('offender_id', '=', $profileId)
            ->exists();

        $userBlockedYou = UserBlock::with([])->where('offender_id', '=', $user->{'id'})
            ->where('initiator_id', '=', $profileId)
            ->exists();

        return response()->json(ApiResponse::successResponseWithData([
            'youBlockedUser' => $youBlockedUser,
            'userBlockedYou' => $userBlockedYou
        ]));

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


    public function fetchLikedUsers(Request $request, $postId): \Illuminate\Http\JsonResponse {

        $user = $request->user();
        $story = Story::with([])->find($postId);
        $limit = $request->get("limit") ?: 15;
        $users = $story->likedUsers()->with('info')
            ->where('user_id', '!=', $user->{'id'}) // except this user
            ->orderByDesc('created_at')->simplePaginate($limit);
        return response()->json(ApiResponse::successResponseWithData($users));
    }

    // Users online visibility ----------------------------------------------
    public function addUserToOnline(Request $request, $userId): JsonResponse
    {
        $exist = UserOnline::with([])->where([
            'user_id' => $userId
        ])->exists();
        if(!$exist) {
            UserOnline::with([])->create(['user_id' => $userId]);
        }
        $user = User::with(['info'])->find($userId);

        $onlineCount = $this->countPeopleOnline(userId: $user->{'id'});
        event(new UserOnlineStatusChanged(user: $user, status: "online", count:  $onlineCount));
        return response()->json(ApiResponse::successResponse());
    }

    public function removeUserFromOnline(Request $request, $userId): JsonResponse
    {
        $online = UserOnline::with([])->where([
            'user_id' => $userId
        ])->first();
        $online?->delete();
        $user = User::with(['info'])->find($userId);


        $onlineCount = $this->countPeopleOnline(userId: $user->{'id'});
        event(new UserOnlineStatusChanged(user: $user, status: "offline", count:  $onlineCount));
        return response()->json(ApiResponse::successResponse());
    }

    public function fetchUsersOnline(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = $request->get("limit") ?: 15;
        $users = UserOnline::with(['user'])
            ->where('user_id', '!=', $user->{'id'}) // except this user
            ->orderByDesc('created_at')->simplePaginate($limit);
        return response()->json(ApiResponse::successResponseWithData($users));
    }

    private function countPeopleOnline($userId): int
    {
        return UserOnline::with([])
            ->where('user_id', '!=', $userId)
            ->count();
    }
    public function countUsersOnline(Request $request): JsonResponse
    {

        $user = $request->user();
        $onlineCount = $this->countPeopleOnline(userId: $user->{'id'});
        return response()->json(ApiResponse::successResponseWithData($onlineCount));
    }

}
