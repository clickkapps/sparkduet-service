<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\Telegram;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class StoryReportCreated extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(public $storyId, public  $reason)
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

        $reason = $this->reason;
        $storyId = $this->storyId;
        $reporterId = $notifiable->{'id'};

        $message = "Reported: Story ID - $storyId\n";
        $message .= $reason . "\n";
        $message .= "--Reporter ID: $reporterId -- \n";

        return TelegramMessage::create()
            // Optional recipient user id.
            ->to(config('custom.telegram_channel_id'))
            // Markdown supported.
            ->content($message);

    }
}
