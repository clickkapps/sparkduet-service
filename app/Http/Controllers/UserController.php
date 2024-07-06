<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use App\Events\UserBlocksOffenderEvent;
use App\Events\UserDisciplinaryRecordEvent;
use App\Events\UserOnlineStatusChanged;
use App\Models\ProfileView;
use App\Models\Story;
use App\Models\StoryReport;
use App\Models\User;
use App\Models\UserBlock;
use App\Models\UserDisciplinaryRecord;
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
    public function takeDisciplinaryActionOnUser(Request $request): JsonResponse
    {

        $this->validate($request, [
            'user_id' => 'required',
            'disciplinary_action' => 'required',
            'reason' => 'required'
        ]);

        $admin = $request->user();
        $userId = $request->get('user_id');
        $disciplinaryAction = $request->get('disciplinary_action');
        $reason = $request->get('reason');

        $offender = User::with([])->where(['id' => $userId])
            ->first();

        if(blank($offender)) {
            throw new \Exception("Invalid offender id");
        }

        if($disciplinaryAction == "banned") {
            $offender->update(['banned_at' => now()]);
        }

        $activeRecord = UserDisciplinaryRecord::with([])->create([
            'user_id' => $userId,
            'disciplinary_action' => $disciplinaryAction,
            'disciplinary_action_taken_by' => $admin->{'id'},
            'reason' => $reason,
            'user_read_at' => null,
            'status'  => 'opened' // opened / closed if its opened we show it to the user
        ]);

        event(new UserDisciplinaryRecordEvent(userId: $activeRecord->{'user_id'}, disRecordId: $activeRecord->{'id'}, disciplinaryRecord: $activeRecord));
        return response()->json(ApiResponse::successResponse());
    }

    public function fetchUserLatestDisciplinaryAction($userId): JsonResponse
    {
        $activeRecord = UserDisciplinaryRecord::with([])
            ->where('user_id', $userId)
            ->where('status', 'opened')
            ->orderByDesc('created_at')
            ->first();

        return response()->json(ApiResponse::successResponseWithData($activeRecord));
    }

    public function markDisciplinaryActionAsRead($id): JsonResponse
    {
        $activeRecord = UserDisciplinaryRecord::with([])->find($id);
        $activeRecord?->update(['user_read_at' => now()]);
        return response()->json(ApiResponse::successResponseWithData($activeRecord));
    }

    public function closeDisciplinaryAction($id): JsonResponse
    {
        $activeRecord = UserDisciplinaryRecord::with([])->find($id);
        $activeRecord?->update(['status' => 'closed']);


        if($activeRecord->{'disciplinary_action'} == "banned") {
            $userId = $activeRecord->{'user_id'};
            $offender = User::with([])->find($userId);
            $offender?->update(['banned_at' => null]);
        }


        event(new UserDisciplinaryRecordEvent(userId: $activeRecord->{'user_id'}, disRecordId: $activeRecord->{'id'}, disciplinaryRecord: null));
        return response()->json(ApiResponse::successResponseWithData($activeRecord));
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
        ])->first();
        if(!$exist) {
            UserOnline::with([])->create(['user_id' => $userId, 'status' => 'online']);
        }else {
            $exist->update(['status' => 'online']);
        }
        $user = User::with(['info'])->find($userId);

        $onlineIds = $this->getUserOnlineIds($request);
        event(new UserOnlineStatusChanged(ids:  $onlineIds));
        return response()->json(ApiResponse::successResponse());
    }

    public function removeUserFromOnline(Request $request, $userId): JsonResponse
    {

        $online = UserOnline::with([])->where([
            'user_id' => $userId
        ])->first();
        if($online) {
            $online->update(['status' => 'offline']);
        }else {
            UserOnline::with([])->create(['user_id' => $userId, 'status' => 'offline']);
        }

        $onlineIds = $this->getUserOnlineIds($request);
        event(new UserOnlineStatusChanged(ids:  $onlineIds));
        return response()->json(ApiResponse::successResponse());
    }

    public function fetchUsersOnline(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = $request->get("limit") ?: 15;
//        $users = UserOnline::with(['user'])
//            ->where('user_id', '!=', $user->{'id'}) // except this user
//            ->where('status', '=', 'online')
//            ->orderByDesc('created_at')->simplePaginate($limit);
        $users = $this->getUsersOnlineQuery($request)
            ->orderByDesc('created_at')->simplePaginate($limit);
        return response()->json(ApiResponse::successResponseWithData($users));
    }

    private function getUserOnlineIds(Request $request): \Illuminate\Support\Collection {
//        return UserOnline::with([])->where('status', '=', 'online')->pluck('user_id');
        return $this->getUsersOnlineQuery($request)->pluck('user_onlines.user_id');
    }

    public function getUserIdsOnline(Request $request): JsonResponse {
        $usersOnline = $this->getUserOnlineIds($request);
        return response()->json(ApiResponse::successResponseWithData($usersOnline));
    }

    private function getUsersOnlineQuery(Request $request): \Illuminate\Database\Eloquent\Builder {

        $user = $request->user();
        $userId = $user->{'id'};

        /// Preferences  --------------------

        // Get preferred gender
        $preferredGenderOutput = [];
        if(!blank($user->info->{'preferred_gender'})) {
            $preferredGender = json_decode($user->info->{'preferred_gender'});
            //eg.  [ any ] , ["women","men","transgenders","non_binary_or_non_conforming"]
            Log::info("preferred_genders: " . $user->info->{'preferred_gender'});
            foreach ($preferredGender as $gender) {

                if($gender == "any") {
//                    $preferredGenderOutput = ["female","male","transgender","non_binary_or_non_conforming"];
                }else {
                    if($gender == "women") {
                        $preferredGenderOutput[] = "female";
                    }
                    if($gender == "men") {
                        $preferredGenderOutput[] = "male";
                    }
                    if($gender == "transgenders") {
                        $preferredGenderOutput[] = "transgender";
                    }
                    if($gender == "non_binary_or_non_conforming") {
                        $preferredGenderOutput[] = "non_binary_or_non_conforming";
                    }
                }
            }
        }

        // Get preferred nationalities
        $includedNationalities = [];
        $excludedNationalities = [];
        if(!blank($user->info->{'preferred_nationalities'})) {
            Log::info('preferred_nationalities: ' . $user->info->{'preferred_nationalities'});
            $preferredNationalities = json_decode($user->info->{'preferred_nationalities'}, true);
            //eg. {"key":"only","values":["GH"]}
            $key = $preferredNationalities['key'];
            $values = $preferredNationalities['values'];
            if($key == 'only') {
                foreach ($values as $value) {
                    $includedNationalities[] = $value;
                }
            }
            if($key == 'except') {
                foreach ($values as $value) {
                    $excludedNationalities[] = $value;
                }
            }

        }

        /// -------------------------

        // Build the filtered query with joins and initial filters
        $query = User::with(['info'])
            ->leftJoin('user_onlines', 'users.id', '=', 'user_onlines.user_id')
            ->leftJoin('user_blocks as b1', function ($join) use ($userId) {
                $join->on('users.id', '=', 'b1.offender_id')
                    ->where('b1.initiator_id', '=', $userId);
            })
            ->leftJoin('user_blocks as b2', function ($join) use ($userId) {
                $join->on('users.id', '=', 'b2.initiator_id')
                    ->where('b2.offender_id', '=', $userId);
            })
            ->join('user_infos', 'users.id', '=', 'user_infos.user_id')
            ->whereNull('b1.id')
            ->whereNull('b2.id')
            ->where('users.id', '!=', $userId)
            ->whereNull('users.banned_at') // Exclude banned users
            ->where('user_onlines.status', 'online'); // Filter for online users

        if (!empty($preferredGenderOutput)) {
            $query->whereIn('user_infos.gender', $preferredGenderOutput);
        }

        // Apply nationality filters based on the presence of included or excluded nationalities
        if (!empty($includedNationalities)) {
            $query->whereIn('user_infos.country', $includedNationalities);
        } elseif (!empty($excludedNationalities)) {
            $query->whereNotIn('user_infos.country', $excludedNationalities);
        }

        return $query;

//        // Select users and paginate
//        $users = $query->select('users.*')->simplePaginate($request->get('limit') ?: 10);
//
//        return $users;
    }

}
