<?php

namespace App\Listeners;

use App\Events\ChatMessageCreatedEvent;
use App\Events\UnreadChatMessagesUpdatedEvent;
use App\Models\ChatParticipant;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CountUnreadChatMessagesForOpponentListener
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
     * @param  \App\Events\ChatMessageCreatedEvent  $event
     * @return void
     */
    public function handle(ChatMessageCreatedEvent $event)
    {
        $opponentId = $event->message->{'sent_to_id'};
        $chatConnectionId = $event->message->{'chat_connection_id'};

        $participant = ChatParticipant::with([])->where([
            'user_id' => $opponentId,
            'chat_connection_id' => $chatConnectionId
        ])->first();
        $unreadMessagesCount = $participant->{'unread_messages'};

        event(new UnreadChatMessagesUpdatedEvent(userId: $opponentId, chatConnectionId: $chatConnectionId, count: $unreadMessagesCount));


    }
}
