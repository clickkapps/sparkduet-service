<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use App\Models\User;
use App\Notifications\ChatMessageCreated;
use App\Traits\UserTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ChatController extends Controller
{
    use UserTrait;

    public function fetchSuggestedChats(Request $request): \Illuminate\Http\JsonResponse
    {
        $authUser = $request->user();
        $suggestedUsers = User::with(['info'])->where('id', '!=', $authUser->{'id'})->get();

        $users = $suggestedUsers->map(function ($user) use ($authUser) {
            $user = $this->attachUserComputedAge($user);
            // Hide fields you don't want to include in the JSON response
            $user->makeHidden(['first_login_at', 'public_key', 'last_login_at']);
            return $user;
        });

        return response()->json(ApiResponse::successResponseWithData($users));
    }

    /**
     * @throws ValidationException
     */
    public function messageCreated(Request $request): \Illuminate\Http\JsonResponse
    {

        Log::info("messageCreated in controller called..");
        $this->validate($request, [
           'sender_id' => 'required',
           'recipient_id' => 'required',
           'chat_connection_id' => 'required',
        ]);
        $senderId = $request->get('sender_id');
        $recipientId = $request->get('recipient_id');
        $chatConnectionId = $request->get('chat_connection_id');

        $sender = User::with([])->find($senderId);
        $recipient =  User::with([])->find($recipientId);

        if(blank($sender) || blank($recipient)) {
            Log::info("invalid sender or recipient");
            return response()->json(ApiResponse::failedResponse("Invalid request"));
        }

        $recipient->notify(new ChatMessageCreated(sender: $sender,  chatConnectionId: $chatConnectionId));

        return response()->json(ApiResponse::successResponse());

    }
}
