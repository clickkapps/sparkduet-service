<?php

namespace App\Http\Controllers;

use App\Classes\ApiResponse;
use App\Events\ChatConnectionCreatedEvent;
use App\Events\ChatConnectionDeletedEvent;
use App\Events\ChatConnectionsMatchedEvent;
use App\Events\ChatMessageCreatedEvent;
use App\Events\ChatMessageDeletedEvent;
use App\Events\ChatMessageReadEvent;
use App\Events\CountUnreadMessagesEvent;
use App\Events\LastChatMessageUpdatedEvent;
use App\Events\UnreadChatMessagesUpdatedEvent;
use App\Models\ChatConnection;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use App\Models\User;
use App\Models\UserBlock;
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

        $userId = $authUser->id;

        // Fetch the IDs of users blocked by the authenticated user
        $blockedUserIds = UserBlock::where('initiator_id', $userId)->pluck('offender_id');

        // Fetch the IDs of users who have blocked the authenticated user
        $blockedByUserIds = UserBlock::where('offender_id', $userId)->pluck('initiator_id');

        // Combine both lists of blocked user IDs
        $allBlockedUserIds = $blockedUserIds->merge($blockedByUserIds)->unique();

        // Fetch the IDs of users with whom the authenticated user has already started a conversation
        $conversationUserIds = ChatParticipant::where('user_id', $userId)
            ->whereNull('deleted_at')
            ->pluck('chat_connection_id');

        $conversationUserIds = ChatParticipant::whereIn('chat_connection_id', $conversationUserIds)
            ->where('user_id', '!=', $userId)
            ->whereNull('deleted_at')
            ->pluck('user_id');

        // Fetch the users (creators) of the stories that the authenticated user has liked, ordered by the most recent like
        $likedStoryCreators = User::whereHas('stories', function ($query) use ($userId) {
            $query->whereHas('likes', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            });
        })
            ->whereNotIn('id', ['14', '15']) // these are the store reviewers ids
            ->whereNotIn('id', $allBlockedUserIds) // Exclude blocked users
            ->whereNotIn('id', $conversationUserIds) // Exclude users with whom conversation has started
            ->with(['stories' => function ($query) use ($userId) {
                $query->whereHas('likes', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })->orderBy('created_at', 'desc');
            }])
            ->get();

        // Fetch the users whose profiles have been viewed by the authenticated user, ordered by the most recent view
        $viewedProfiles = User::whereHas('profileViews', function ($query) use ($userId) {
            $query->where('viewer_id', $userId);
        })
            ->whereNotIn('id', $allBlockedUserIds) // Exclude blocked users
            ->whereNotIn('id', $conversationUserIds) // Exclude users with whom conversation has started
            ->with(['profileViews' => function ($query) use ($userId) {
                $query->where('viewer_id', $userId)->orderBy('created_at', 'desc');
            }])
            ->get();

        // Fetch the users whose stories have been viewed by the authenticated user, ordered by the most views
        $viewedStoryUsers = User::whereHas('stories', function ($query) use ($userId) {
            $query->whereHas('views', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            });
        })
            ->whereNotIn('id', $allBlockedUserIds) // Exclude blocked users
            ->whereNotIn('id', $conversationUserIds) // Exclude users with whom conversation has started
            ->with(['stories' => function ($query) use ($userId) {
                $query->whereHas('views', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
//                    ->orderByRaw('COALESCE(watched_count, 1) DESC')
                    ->orderBy('created_at', 'desc');
            }])
            ->get();

        // Merge the three collections and remove duplicates
        $relatedUsers = $likedStoryCreators->merge($viewedProfiles)->merge($viewedStoryUsers)->unique('id');

        // Sort the merged collection by the most recent interaction (like, profile view, or story view)
        $relatedUsers = $relatedUsers->sortByDesc(function ($user) use ($userId) {
            $latestLike = $user->stories->pluck('likes')->flatten()->where('user_id', $userId)->sortByDesc('created_at')->first();
            $latestProfileView = $user->profileViews->where('viewer_id', $userId)->sortByDesc('created_at')->first();
            $latestStoryView = $user->stories->pluck('views')->flatten()->where('user_id', $userId)->sortByDesc('created_at')->first();

            return max(
                optional($latestLike)->created_at,
                optional($latestProfileView)->created_at,
                optional($latestStoryView)->created_at
            );
        })->values();

        // Take the first 10 users
        $relatedUsers = $relatedUsers->take(10);

        return response()->json(ApiResponse::successResponseWithData($relatedUsers));
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
            'user_id' => $firstParticipantId,
        ])
            ->whereNull('deleted_at')
            ->pluck('chat_connection_id');

        $secondParticipantConnections = ChatParticipant::with([])
            ->whereNull('deleted_at')
            ->where('user_id', $secondParticipantId)
            ->pluck('chat_connection_id');

        // Check if there's a common chat connection ID
        $commonConnectionIds = $firstParticipantConnections->intersect($secondParticipantConnections);

        Log::info("customLog: commonConnectionIds" . json_encode($commonConnectionIds));

        // Retrieve the connection that is not marked as deleted
        $activeConnection = ChatConnection::with([])->whereIn('id', $commonConnectionIds)
            ->whereNull('deleted_at')
            ->first();


        Log::info("customLog: activeConnection: " . json_encode($activeConnection));

        $chatConnection = null;
        if ($activeConnection) {
            Log::info('customLog: entered activeconnection ------');
            // Participants share at least one chat connection ID
            $chatConnection = ChatConnection::with(['participants'])->find($activeConnection->{'id'});

        } else if($createConnectionIfNotExist)  {

            Log::info('customLog: entered create new connection ------');

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

            event(new ChatConnectionCreatedEvent(chatConnection: $chatConnection));

        }else {
            Log::info('customLog: returning null connection ------');
        }

        return response()->json(ApiResponse::successResponseWithData($chatConnection));

    }

    public function fetchChatConnections(Request $request): \Illuminate\Http\JsonResponse {

        $user = $request->user();
        $limit = $request->get("limit") ?: 15;
        // Get chat connections along with participants
        $chatConnections = $user->chatConnections()
            ->whereNull('chat_connections.deleted_at')
            ->with(['participants' => function($query) {
                $query->withPivot('unread_messages');
            }, 'lastMessage'])
            ->orderByDesc('updated_at')->simplePaginate($limit);

        return response()->json(ApiResponse::successResponseWithData($chatConnections));
    }

    public function getChatConnectionById(Request $request, $id): \Illuminate\Http\JsonResponse {

        $user = $request->user();
        $chatConnection = $user->chatConnections()->with([
            'participants' => function($query) {
                $query->withPivot('unread_messages');
            },
            'lastMessage'
        ])
            ->whereNull('chat_connections.deleted_at')
            ->find($id);

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

        $message = ChatMessage::with(['parent'])->create([
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
            'unread_messages' => $unreadMessagesCount + 1
        ]);

        $connection = ChatConnection::with(['participants'])->find($chatConnectionId);
        if(blank($connection->{'read_first_impression_note_at'})) {
            $connection->update([
                'read_first_impression_note_at' => now()
            ]);
        }

        event(new ChatMessageCreatedEvent(message: $message));
        event(new CountUnreadMessagesEvent(chatConnectionId: $chatConnectionId, userId: $message->{'sent_to_id'}));

//        if matched_at in $connection is null,
//        check if this message is a reply, if it is, update matched_at and emit matched at event
        if(blank($connection->{'matched_at'})) {
            if($message->{'sent_to_id'} == $connection->{'created_by'}){ // if this condition passes, then its a reply
                $now = now();
                $connection->update(['matched_at' => $now]);
                event(new ChatConnectionsMatchedEvent(creatorId: $connection->{'created_by'}, connectionId: $chatConnectionId, matchedAt: $now));

            }
        }

        return response()->json(ApiResponse::successResponseWithData($message));

    }

    /**
     * @throws ValidationException
     */
    public function fetchMessages(Request $request): \Illuminate\Http\JsonResponse {
        $this->validate($request, [
            'conn_id' => 'required',
        ]);

        $chatConnectionId = $request->get('conn_id');

        $messages = ChatMessage::with(['parent'])->where([
            'deleted_at' => null,
            'chat_connection_id' => $chatConnectionId
        ])->orderByDesc('created_at')->simplePaginate($request->get("limit") ?: 15);
        return response()->json(ApiResponse::successResponseWithData($messages));
    }

    /**
     * @throws ValidationException
     */
    public function markMessagesRead(Request $request): \Illuminate\Http\JsonResponse {

        $this->validate($request, [
            'chat_connection_id' => 'required',
            'opponent_id' => 'required'
        ]);

        $user = $request->user();
        $chatConnectionId = $request->get('chat_connection_id');
        $senderId = $request->get('opponent_id');

        $participant = ChatParticipant::with([])->where([
            'user_id' => $user->{'id'},
            'chat_connection_id' => $chatConnectionId
        ])->first();
        $participant->update([
            'unread_messages' => 0
        ]);

        $query = ChatMessage::with([])
            ->where([
                'chat_connection_id' => $chatConnectionId,
                'sent_to_id' => $user->{'id'},
                'seen_at' => null
            ]);
        $q1 = clone  $query;
        $q2 = clone $query;
        $now = now();
        $messageIds = $q2->pluck('id');
        $q1->update(['seen_at' => $now]);


        event(new ChatMessageReadEvent(chatConnectionId: $chatConnectionId, opponentId: $senderId, messageIds: $messageIds, seenAt: $now));
        return response()->json(ApiResponse::successResponse());

    }

    /**
     * @throws ValidationException
     */
    public function deleteMessage(Request $request): \Illuminate\Http\JsonResponse
    {

        $this->validate($request, [
            'opponent_id' => 'required',
            'message_id' => 'required'
        ]);

        $messageId = $request->get('message_id');
        $opponentId = $request->get('opponent_id');
        $user = $request->user();

        $message = ChatMessage::with(['parent'])->find($messageId);
        $message->update(['deleted_at' => now()]);
        $message->refresh();

        // check if it's the last chat message, if yes raise the LastChatMessageUpdatedEvent
        $connection = ChatConnection::with([])->find($message->{'chat_connection_id'});
        if($connection->{'chat_message_id'} == $message->{'id'}) {
            // its last message
            event(new LastChatMessageUpdatedEvent(message: $message, opponentId: $opponentId));
        }

        // check if the seen_at is not equal to null, if its null
        //  - reduce the unread_messages count and raise UnreadChatMessagesUpdatedEvent
        //  - count the total unread and raise the TotalUnreadChatMessagesUpdatedEvent
        if($message->{'sent_by_id'} == $user->id) { // if the message was sent by me
            if(blank($message->{'seen_at'})) {// and the user has not read it yet
                $participant = ChatParticipant::with([])->where([
                    'user_id' => $opponentId,
                    'chat_connection_id' => $connection->{'id'}
                ])->first();
                $unreadMessages = $participant->{'unread_messages'};
                $newUnreadMessage = ($unreadMessages ?? 1) - 1;
                $participant->update(['unread_messages' => $newUnreadMessage,]);
            }

        }

        event(new ChatMessageDeletedEvent(message: $message, opponentId: $opponentId));
        event(new CountUnreadMessagesEvent(chatConnectionId: $connection->{'id'}, userId: $opponentId));

        return response()->json(ApiResponse::successResponse());
    }

    /**
     * @throws ValidationException
     */
    public function deleteChatConnection(Request $request): \Illuminate\Http\JsonResponse {
        $this->validate($request, [
            'chat_connection_id' => 'required',
            'opponent_id' => 'required',
        ]);

        $opponentId = $request->get('opponent_id');
        $chatConnectionId = $request->get('chat_connection_id');
        $chatConnection = ChatConnection::with(['participants'])->find($chatConnectionId);
        $chatConnection->update(['deleted_at' => now()]);

        // mark all related chat messages as deleted
        ChatMessage::with(['parent'])->where([
            'chat_connection_id' => $chatConnectionId
        ])->update(['deleted_at' => now()]);

        $chatConnection->refresh();

        // mark it as deleted for both parties
         ChatParticipant::with([])->where([
            'chat_connection_id' => $chatConnectionId,
        ])->update([
                'unread_messages' => 0,
                'deleted_at' => now()
             ]);

        event(new ChatConnectionDeletedEvent(userId: $opponentId, chatConnectionId: $chatConnectionId));
        event(new CountUnreadMessagesEvent(chatConnectionId: $chatConnectionId, userId: $opponentId));

        return response()->json(ApiResponse::successResponse());

    }

    public function getTotalUnreadChatMessages(Request $request): \Illuminate\Http\JsonResponse
    {
         $user = $request->user();
        $totalUnreadMessages = ChatParticipant::with([])->where([
            'user_id' => $user->id,
        ])->sum('unread_messages');
        return response()->json(ApiResponse::successResponseWithData($totalUnreadMessages));
    }




}
