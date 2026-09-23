<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailSignInCode extends Notification
{
    public function __construct(public string $code) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your sign-in code')
            ->line('Your sign-in code is '.$this->code.'.')
            ->line('This code expires in 10 minutes.');
    }
}
