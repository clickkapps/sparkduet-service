<?php

namespace App\Listeners;

use App\Events\ChatMessageCreatedEvent;
use App\Events\LastChatMessageUpdatedEvent;
use App\Events\NotificationsUpdatedEvent;
use App\Models\ChatConnection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateChatConnectionLastMessageListener
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
        $chatConnectionId =  $event->message->{'chat_connection_id'};
        $chatConnection = ChatConnection::with(['participants'])->find($chatConnectionId);
        $chatConnection->update([
            'chat_message_id' => $event->message->{'id'}
        ]);

        $chatConnection->refresh();

        event(new LastChatMessageUpdatedEvent(chatConnection: $chatConnection));

    }
}
