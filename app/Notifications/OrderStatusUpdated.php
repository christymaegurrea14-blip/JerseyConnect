<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusUpdated extends Notification
{
    use Queueable;

    private const LABEL = [
        'processing' => 'Processing',
        'in_production' => 'In Production',
        'ready_for_delivery' => 'Ready for Delivery',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'completed' => 'Completed',
    ];

    public function __construct(private Order $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = self::LABEL[$this->order->status] ?? ucfirst(str_replace('_', ' ', $this->order->status));

        $mail = (new MailMessage)
            ->subject("Order {$this->order->order_number} — {$label}")
            ->greeting('Hi there,')
            ->line("Your order **{$this->order->order_number}** ({$this->order->team_name} — {$this->order->template_name}) is now:")
            ->line("**{$label}**");

        if ($this->order->status === 'shipped' && $this->order->courierReceipt) {
            $mail->line("Courier: {$this->order->courierReceipt->courier?->name}")
                ->line("Tracking #: {$this->order->courierReceipt->transaction_number}");
        }

        return $mail
            ->action('View Order', url('/client/orders'))
            ->line('Thanks for choosing PrintCode!');
    }
}
