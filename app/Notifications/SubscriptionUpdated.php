<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class SubscriptionUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(public $appUserId, public $type, public $productId, public $environment)
    {}

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via(mixed $notifiable)
    {
        return [TelegramChannel::class];
    }

    public function toTelegram($notifiable)
    {

        $message = "Subscription Update:\n";
        $message .= "------------------------------- \n";
        $message .= "User ID: ". $this->appUserId . "\n";
        $message .= "type: " .$this->type . " \n";
        $message .= "product: " .$this->productId . " \n";
        $message .= "environment: " .$this->environment . " \n";
        $message .= "------------------------------- \n";

        return TelegramMessage::create()
            // Optional recipient user id.
            ->to(config('custom.telegram_channel_id'))
            // Markdown supported.
            ->content($message);

    }
}
