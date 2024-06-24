<?php

namespace App\Listeners;

use App\Events\ChatMessageCreatedEvent;
use App\Events\TotalUnreadChatMessagesUpdatedEvent;
use App\Events\UnreadChatMessagesUpdatedEvent;
use App\Models\ChatParticipant;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CountUnreadChatMessagesForOpponentListener implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param   $event
     * @return void
     */
    public function handle($event)
    {
        Log::info('CountUnreadChatMessagesForOpponentListener called...');
        $opponentId = $event->message->{'sent_to_id'};
        $chatConnectionId = $event->message->{'chat_connection_id'};

        $participant = ChatParticipant::with([])->where([
            'user_id' => $opponentId,
            'chat_connection_id' => $chatConnectionId
        ])->first();
        $unreadMessagesCount = $participant->{'unread_messages'};

        event(new UnreadChatMessagesUpdatedEvent(userId: $opponentId, chatConnectionId: $chatConnectionId, count: $unreadMessagesCount));

        // sum total unread messages
        $totalUnreadMessages = ChatParticipant::with([])->where([
            'user_id' => $opponentId,
        ])->sum('unread_messages');
        event(new TotalUnreadChatMessagesUpdatedEvent(userId: $opponentId, count: $totalUnreadMessages));


    }
}
