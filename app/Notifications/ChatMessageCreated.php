<?php

namespace App\Notifications;

use App\Channels\PushNotificationChannel;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use JetBrains\PhpStorm\ArrayShape;

class ChatMessageCreated extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(private $sender, public readonly string $chatConnectionId)
    { }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via(mixed $notifiable): array
    {
        return [PushNotificationChannel::class];
    }


    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    #[ArrayShape(["userId" => "", 'title' => "mixed", 'message' => "string", 'data' => "array"])]
    public function toPush(mixed $notifiable): array
    {
        return [
            "userId" => $notifiable->{'username'},
            'title' =>  $this->sender->{'name'} ?: $this->sender->{'username'},
            'message' => "New message", // we do this to hide the message.
            'data' => [
                'pushType' => 'chat',
                'chatConnectionId' => $this->chatConnectionId
            ]
        ];
    }
}
