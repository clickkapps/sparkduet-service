<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailAuthCodeGenerated extends Notification implements ShouldQueue
{
    use Queueable;
    private string $code;
    private string $name;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $code, string $name)
    {
        $this->code = $code;
        $this->name = $name;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $subject = 'Verification Code';
        $message = 'Your verification code is ' . $this->code;

        return (new MailMessage)
            ->subject($subject)
            ->markdown('mail.message',
                [
                    'message' => $message,
                    'name' => $this->name
                ]
            );
    }
}
