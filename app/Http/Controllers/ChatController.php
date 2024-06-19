<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use App\Events\ChatConnectionCreatedEvent;
use App\Events\ChatConnectionDeletedEvent;
use App\Events\ChatMessageCreatedEvent;
use App\Events\ChatMessageDeletedEvent;
use App\Events\ChatMessageReadEvent;
use App\Models\ChatConnection;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
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

    /**
     * @throws ValidationException
     */
    public function createChatConnection(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->validate($request, [
            'first_participant_id' => 'required',
            'second_participant_id' => 'required',
            'create_connection_if_not_exist' => 'required'
        ]);

        $authUser = $request->user();
        $firstParticipantId = $request->get('first_participant_id');
        $secondParticipantId = $request->get('second_participant_id');
        $createConnectionIfNotExist = $request->get('create_connection_if_not_exist');

        $firstParticipantConnections = ChatParticipant::with([])->where([
            'user_id' => $secondParticipantId,
        ])->pluck('chat_connection_id');

        $secondParticipantConnections = ChatParticipant::with([])
            ->where('user_id', $secondParticipantId)
            ->pluck('chat_connection_id');

        // Check if there's a common chat connection ID
        $commonConnectionIds = $firstParticipantConnections->intersect($secondParticipantConnections);

        // Retrieve the connection that is not marked as deleted
        $activeConnection = ChatConnection::with([])->whereIn('id', $commonConnectionIds)
            ->whereNull('deleted_at')
            ->first();

        $chatConnection = null;
        if ($activeConnection) {
            // Participants share at least one chat connection ID
            $chatConnection = ChatConnection::with(['participants'])->find($activeConnection->{'id'});

        } else if($createConnectionIfNotExist)  {

            // No common chat connection ID found
            $chatConnection = ChatConnection::with(['participants'])->create([
                'chat_message_id' => null,
                'created_by' => $authUser->{'id'}
            ]);
            ChatParticipant::with([])->create([
                'chat_connection_id' => $chatConnection->{'id'},
                'user_id' => $firstParticipantId,
            ]);
            ChatParticipant::with([])->create([
                'chat_connection_id' => $chatConnection->{'id'},
                'user_id' => $secondParticipantId,
            ]);
            $chatConnection = $chatConnection->refresh();

        }

        event(new ChatConnectionCreatedEvent(chatConnection: $chatConnection));
        return response()->json(ApiResponse::successResponseWithData($chatConnection));

    }

    public function fetchChatConnections(Request $request): \Illuminate\Http\JsonResponse {

        $user = $request->user();
        // Get chat connections along with participants
        $chatConnections = $user->chatConnections()
            ->whereNull('deleted_at')
            ->with('participants')
            ->get();

        return response()->json(ApiResponse::successResponseWithData($chatConnections));
    }

    public function getChatConnection(Request $request, $id): \Illuminate\Http\JsonResponse {

        $chatConnection = ChatConnection::with(['participant'])->whereNull('deleted_at')->find($id);
        return response()->json(ApiResponse::successResponseWithData($chatConnection));

    }


    /**
     * @throws ValidationException
     */
    public function sendMessage(Request $request): \Illuminate\Http\JsonResponse {

        $this->validate($request, [
            'chat_connection_id' => 'required',
            'sent_by_id' => 'required',
            'sent_to_id' => 'required',
            'client_id' => 'required'
        ]);

        $chatConnectionId = $request->get('chat_connection_id');
        $sentById = $request->get('sent_by_id');
        $sentToId = $request->get('sent_to_id');
        $parentId = $request->get('parent_id') ?? null;
        $clientId = $request->get('client_id');
        $attachmentPath = $request->get('attachment_path');
        $attachmentType = $request->get('attachment_type');
        $text = $request->get('text');

        $message = ChatMessage::with([])->create([
            'chat_connection_id' => $chatConnectionId,
            'client_id' => $clientId,
            'parent_id' => $parentId,
            'deleted_at' => null,
            'delivered_at' => now(),
            'seen_at' => null,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
            'text' => $text,
            'sent_by_id' => $sentById,
            'sent_to_id' => $sentToId
        ]);

        $message->refresh();

        $participant = ChatParticipant::with([])->where([
            'user_id' => $sentToId,
            'chat_connection_id' => $chatConnectionId
        ])->first();
        $unreadMessagesCount = $participant->{'unread_messages'};
        $participant->update([
            $unreadMessagesCount
        ]);

        event(new ChatMessageCreatedEvent(message: $message));

        return response()->json(ApiResponse::successResponseWithData($message));

    }

    /**
     * @throws ValidationException
     */
    public function markMessagesRead(Request $request) {

        $this->validate($request, [
            'chat_connection_id' => 'required',
            'message_ids' => 'required|array'
        ]);

        $messageIds = $request->get('message_ids');
        $chatConnectionId = $request->get('chat_connection_id');

        ChatMessage::with([])->whereIn('id', $messageIds)->update(['seen_at' => now()]);
//        $message->update(['seen_at' => now()]);
//        $message->refresh();

        event(new ChatMessageReadEvent(chatConnectionId: $chatConnectionId, messageIds: $messageIds));

    }

    /**
     * @throws ValidationException
     */
    public function deleteMessage(Request $request) {

        $this->validate($request, [
            'opponent_id' => 'required',
            'message_id' => 'required'
        ]);

        $messageId = $request->get('message_id');
        $opponentId = $request->get('opponent_id');

        $message = ChatMessage::with([])->find($messageId);
        $message->update(['deleted_at' => now()]);
        event(new ChatMessageDeletedEvent(message: $message, opponentId: $opponentId));
    }

    /**
     * @throws ValidationException
     */
    public function deleteChatConnection(Request $request) {
        $this->validate($request, [
            'chat_connection_id' => 'required'
        ]);

        $chatConnectionId = $request->get('chat_connection_id');
        $chatConnection = ChatConnection::with(['participants'])->find($chatConnectionId);
        $chatConnection->update(['deleted_at' => now()]);
        $chatConnection->refresh();

        event(new ChatConnectionDeletedEvent(chatConnection: $chatConnection));

    }




}
