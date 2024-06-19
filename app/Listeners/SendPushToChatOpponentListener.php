<?php

namespace App\Listeners;

use App\Events\ChatMessageCreatedEvent;
use App\Models\ChatParticipant;
use App\Models\User;
use App\Notifications\ChatMessageCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPushToChatOpponentListener
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
        $senderId = $event->message->{'sent_by_id'};
        $chatConnectionId = $event->message->{'chat_connection_id'};
        $opponent = User::with([])->find($opponentId);
        $sender = User::with([])->find($senderId);

        $unreadMessagesCount = ChatParticipant::with([])->where([
            'user_id' => $opponentId,
            'chat_connection_id' => $chatConnectionId
        ])->first()->{'unread_messages'};

        $opponent->notify(new ChatMessageCreated(sender: $sender, chatConnectionId: $chatConnectionId, unreadMessagesCount: $unreadMessagesCount));

    }
}
