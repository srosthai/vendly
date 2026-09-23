<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailSignInCode extends Notification
{
    public function __construct(public string $code, public string $purpose = 'sign-in') {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = $this->purpose === 'registration' ? 'registration code' : 'sign-in code';

        return (new MailMessage)
            ->subject('Your '.$label)
            ->line('Your '.$label.' is '.$this->code.'.')
            ->line('This code expires in 10 minutes.');
    }
}
