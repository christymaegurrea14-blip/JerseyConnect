<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChanged extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your password was changed')
            ->greeting('Hi there,')
            ->line('This is a confirmation that the password on your PrintCode account was just changed.')
            ->line('If you made this change, no action is needed.')
            ->line("If you didn't make this change, please contact us immediately.")
            ->action('Go to My Profile', url('/client/profile'));
    }
}
