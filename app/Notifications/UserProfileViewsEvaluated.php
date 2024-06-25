<?php

namespace App\Notifications;

use App\Channels\PushNotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use JetBrains\PhpStorm\ArrayShape;

class UserProfileViewsEvaluated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(public string $message)
    {}

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
    #[ArrayShape(["userId" => "", 'title' => "mixed", 'message' => "string", 'unread' => "int", 'data' => "array"])]
    public function toPush(mixed $notifiable): array
    {
        return [
            "userId" => $notifiable->{'username'},
            'title' =>  'New profile views',
            'message' => $this->message, // we do this to hide the message.
            'data' => [
                'pushType' => 'profile_views'
            ]
        ];
    }
}
