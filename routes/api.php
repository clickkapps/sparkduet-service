<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->middleware('basicAuth')->group(function () {
    Route::post('email', [\App\Http\Controllers\AuthController::class, 'sendAuthEmailVerificationCode']);
    Route::post('email/verify', [\App\Http\Controllers\AuthController::class, 'verifyAuthEmail']);
});
Route::prefix('auth')->middleware('auth:sanctum')->group(function () {
    Route::post('profile-update', [\App\Http\Controllers\AuthController::class, 'updateAuthUserProfile']);
    Route::get('should-prompt-basic-info-update', [\App\Http\Controllers\AuthController::class, 'shouldPromptAuthUserToUpdateBasicInfo']);
    Route::get('basic-info-prompted', [\App\Http\Controllers\AuthController::class, 'setPromptBasicInfoCompleted']);
    Route::post('setup-location', [\App\Http\Controllers\AuthController::class, 'setupUserLocation']);

    Route::post('mark-account-for-deletion', [\App\Http\Controllers\AuthController::class, 'markAccountForDeletion']);
    Route::get('active-check', [\App\Http\Controllers\AuthController::class, 'checkIfAccountIsActive']);
});

//Route::prefix('chat')->middleware('basicAuth')->group(function () {
//    Route::post('message-created', [\App\Http\Controllers\ChatController::class, 'messageCreated']);
//});

/*
|--------------------------------------------------------------------------
| Users Routes
|--------------------------------------------------------------------------
*/

Route::prefix('user')->middleware('auth:sanctum')->group(function () {
    Route::get('profile/{id?}', [\App\Http\Controllers\UserController::class, 'getUserProfile']);
    Route::post('record-profile-view', [\App\Http\Controllers\UserController::class, 'recordProfileView']);
    Route::post('mark-profile-view-as-read', [\App\Http\Controllers\UserController::class, 'markProfileViewsAsRead']);
    Route::get('fetch-unread-profile-viewers', [\App\Http\Controllers\UserController::class, 'fetchUnreadProfileViewers']);
    Route::get('count-unread-profile-views', [\App\Http\Controllers\UserController::class, 'countUnreadProfileViews']);
    Route::get('get-notice', [\App\Http\Controllers\UserController::class, 'getUserNotice']);
    Route::post('mark-notice-as-read', [\App\Http\Controllers\UserController::class, 'markNoticeAsRead']);
    Route::post('report-user', [\App\Http\Controllers\UserController::class, 'reportUser']);
    Route::post('block-user', [\App\Http\Controllers\UserController::class, 'userBlocksOffender']);
    Route::post('unblock-user', [\App\Http\Controllers\UserController::class, 'userUnblocksOffender']);
    Route::post('block-status', [\App\Http\Controllers\UserController::class, 'getUserBlockUserStatus']);
    Route::get('get-disciplinary-record/{userId}', [\App\Http\Controllers\UserController::class, 'fetchUserLatestDisciplinaryAction']);
    Route::post('mark-disciplinary-as-read/{id}', [\App\Http\Controllers\UserController::class, 'markDisciplinaryActionAsRead']);

    Route::get('/liked/post/{postId}', [\App\Http\Controllers\UserController::class, 'fetchLikedUsers']);
    Route::post('/online/add/{userId}', [\App\Http\Controllers\UserController::class, 'addUserToOnline']);
    Route::post('/online/remove/{userId}', [\App\Http\Controllers\UserController::class, 'removeUserFromOnline']);
    Route::get('/online/get', [\App\Http\Controllers\UserController::class, 'fetchUsersOnline']);
    Route::get('/online/ids', [\App\Http\Controllers\UserController::class, 'getUserIdsOnline']);

});


/*
|--------------------------------------------------------------------------
| Chats Routes
|--------------------------------------------------------------------------
*/

Route::prefix('chat')->middleware('auth:sanctum')->group(function () {
    Route::get('suggested', [\App\Http\Controllers\ChatController::class, 'fetchSuggestedChats']);
    Route::post('create-chat-connection', [\App\Http\Controllers\ChatController::class, 'createChatConnection']);
    Route::get('fetch-chat-connections', [\App\Http\Controllers\ChatController::class, 'fetchChatConnections']);
    Route::get('get-chat-connection/{id}', [\App\Http\Controllers\ChatController::class, 'getChatConnectionById']);
    Route::post('send-message', [\App\Http\Controllers\ChatController::class, 'sendMessage']);
    Route::get('fetch-messages', [\App\Http\Controllers\ChatController::class, 'fetchMessages']);
    Route::post('delete-message', [\App\Http\Controllers\ChatController::class, 'deleteMessage']);
    Route::post('delete-connection', [\App\Http\Controllers\ChatController::class, 'deleteChatConnection']);
    Route::post('mark-messages-as-read', [\App\Http\Controllers\ChatController::class, 'markMessagesRead']);
    Route::get('total-unread-chat-messages', [\App\Http\Controllers\ChatController::class, 'getTotalUnreadChatMessages']);
});

/*
|--------------------------------------------------------------------------
| Preferences Routes
|--------------------------------------------------------------------------
*/

Route::prefix('preferences')->middleware('auth:sanctum')->group(function () {
    Route::get('/settings', [\App\Http\Controllers\PreferencesController::class, 'fetchSettings']);
    Route::post('/update-settings', [\App\Http\Controllers\PreferencesController::class, 'updateSettings']);
    Route::post('/create-feedback', [\App\Http\Controllers\PreferencesController::class, 'createFeedback']);
});


/*
|--------------------------------------------------------------------------
| Stories Routes
|--------------------------------------------------------------------------
*/

Route::prefix('posts')->middleware('auth:sanctum')->group(function () {

    Route::get('/', [\App\Http\Controllers\StoryController::class, 'fetchStoryFeeds']);
    Route::post('/create-post', [\App\Http\Controllers\StoryController::class, 'createPost']);
    Route::post('/attach-post-media/{id}', [\App\Http\Controllers\StoryController::class, 'attachMediaToPost']);
    Route::post('/update/{id}', [\App\Http\Controllers\StoryController::class, 'updateFeed']);

    // User posts
    Route::get('/user/{userId}', [\App\Http\Controllers\StoryController::class, 'fetchUserPosts']);
    Route::get('/bookmarked/user/{userId}', [\App\Http\Controllers\StoryController::class, 'fetchUserBookmarkedPosts']);

    // actions
    Route::post('/like/{postId}', [\App\Http\Controllers\StoryController::class, 'likeStory']);
    Route::post('/bookmark/{postId}', [\App\Http\Controllers\StoryController::class, 'bookmarkStory']);
    Route::post('/view/{postId}', [\App\Http\Controllers\StoryController::class, 'viewStory']);
    Route::post('/report/{postId}', [\App\Http\Controllers\StoryController::class, 'reportStory']);
});


/*
|--------------------------------------------------------------------------
| Comments Routes
|--------------------------------------------------------------------------
*/

Route::prefix('comments')->middleware('auth:sanctum')->group(function () {
//    Route::get('/', [\App\Http\Controllers\StoryController::class, 'fetchStoryFeeds']);
    Route::post('/create', [\App\Http\Controllers\CommentController::class, 'createComment']);
});


/*
|--------------------------------------------------------------------------
| Utils Routes
|--------------------------------------------------------------------------
*/

Route::prefix('utils')->middleware('auth:sanctum')->group(function () {
    Route::post('upload-files', [\App\Http\Controllers\UtilsController::class, 'uploadFiles']);
    Route::get('mux-create-upload-url', [\App\Http\Controllers\UtilsController::class, 'createMuxUploadUrl']);
    Route::post('mux-upload-status', [\App\Http\Controllers\UtilsController::class, 'getMuxUploadStatus']);
    Route::post('mux-video-status', [\App\Http\Controllers\UtilsController::class, 'getMuxVideoStatus']);
});

/*
|--------------------------------------------------------------------------
| Search Routes
|--------------------------------------------------------------------------
*/
Route::prefix('search')->middleware('auth:sanctum')->group(function () {
    Route::get('top', [\App\Http\Controllers\SearchController::class, 'searchTopResults']);
    Route::get('users', [\App\Http\Controllers\SearchController::class, 'searchPeople']);
    Route::get('posts', [\App\Http\Controllers\SearchController::class, 'searchStories']);
    Route::get('user-search-terms', [\App\Http\Controllers\SearchController::class, 'getUserSearchTerms']);
    Route::get('popular-search-terms', [\App\Http\Controllers\SearchController::class, 'popularSearches']);
});

/*
|--------------------------------------------------------------------------
| Notifications routes
|--------------------------------------------------------------------------
*/
Route::prefix('notifications')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [\App\Http\Controllers\NotificationsController::class, 'fetchNotifications']);
    Route::get('/count-unseen-count', [\App\Http\Controllers\NotificationsController::class, 'getCountUnseenNotificationsCount']);
    Route::post('/mark-as-seen', [\App\Http\Controllers\NotificationsController::class, 'markNotificationsAsSeen']);
    Route::post('/mark-as-read/{id}', [\App\Http\Controllers\NotificationsController::class, 'markNotificationAsRead']);
});

/*
|--------------------------------------------------------------------------
| Payment routes
|--------------------------------------------------------------------------
*/
Route::post('revenue-cat-callback', [\App\Http\Controllers\PaymentController::class, 'revenueCatWebhookCallback']);
