<?php

namespace App\Notifications;

use App\Models\DesignRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DesignRequestStatusUpdated extends Notification
{
    use Queueable;

    private const MEANING = [
        'pending_review' => 'Our team is reviewing your request.',
        'in_discussion' => "We're discussing details with you via chat.",
        'revision_requested' => "We've asked for changes — check your messages for details.",
        'waiting_for_down_payment' => 'Ready for the 50% down payment to begin production.',
        'pending_down_payment_review' => "We're verifying your GCash payment.",
        'approved' => 'Approved — your order has been created and production is starting.',
        'cancelled' => 'This request was cancelled.',
    ];

    private const LABEL = [
        'pending_review' => 'Pending Review',
        'in_discussion' => 'In Discussion',
        'revision_requested' => 'Revision Requested',
        'waiting_for_down_payment' => 'Waiting for Down Payment',
        'pending_down_payment_review' => 'Pending Down Payment Review',
        'approved' => 'Approved',
        'cancelled' => 'Cancelled',
    ];

    public function __construct(private DesignRequest $designRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->designRequest->status;
        $label = self::LABEL[$status] ?? ucfirst(str_replace('_', ' ', $status));
        $meaning = self::MEANING[$status] ?? null;

        $mail = (new MailMessage)
            ->subject("Design request update — {$this->designRequest->team_name}")
            ->greeting('Hi there,')
            ->line("Your design request for **{$this->designRequest->team_name}** ({$this->designRequest->template_name}) is now:")
            ->line("**{$label}**");

        if ($meaning) {
            $mail->line($meaning);
        }

        return $mail
            ->action('View Design Request', url('/client/design/' . $this->designRequest->id))
            ->line('Thanks for choosing PrintCode!');
    }
}
