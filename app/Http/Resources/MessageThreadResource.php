<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageThreadResource extends JsonResource
{
    private const DESIGN_BADGES = [
        'pending_review'              => ['Pending Review', 'bg-yellow-100 text-yellow-700'],
        'in_discussion'                => ['In Discussion', 'bg-blue-100 text-blue-700'],
        'revision_requested'           => ['Revision Requested', 'bg-orange-100 text-orange-700'],
        'waiting_for_down_payment'     => ['Waiting for Down Payment', 'bg-pink-100 text-pink-700'],
        'pending_down_payment_review'  => ['Pending Down Payment Review', 'bg-red-100 text-red-700'],
        'approved'                     => ['Approved', 'bg-green-100 text-green-700'],
        'cancelled'                    => ['Cancelled', 'bg-gray-200 text-gray-600'],
    ];

    private const ORDER_BADGES = [
        'processing'          => ['Processing', 'bg-yellow-100 text-yellow-700'],
        'in_production'       => ['In Production', 'bg-blue-100 text-blue-700'],
        'ready_for_delivery'  => ['Ready for Delivery', 'bg-purple-100 text-purple-700'],
        'shipped'              => ['Shipped', 'bg-indigo-100 text-indigo-700'],
        'delivered'            => ['Delivered', 'bg-teal-100 text-teal-700'],
        'completed'            => ['Completed', 'bg-green-100 text-green-700'],
    ];

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $designRequest = $this->designRequest;
        $order = $designRequest->order;
        $stage = $order ? 'order' : 'design';

        [$statusLabel, $statusClass] = $stage === 'order'
            ? self::ORDER_BADGES[$order->status]
            : self::DESIGN_BADGES[$designRequest->status];

        $viewer = $request->user();
        $lastMessage = $this->messages->last();
        $lastReadAt = $viewer->role === 'admin' ? $this->admin_last_read_at : $this->client_last_read_at;
        $isRead = !$lastMessage
            || $lastMessage->user_id === $viewer->id
            || ($lastReadAt && $lastReadAt->gte($lastMessage->created_at));

        $players = $designRequest->players->map(fn($player) => [
            'id' => $player->id,
            'name' => $player->name,
            'number' => $player->number,
            'position' => $player->position,
            'size' => $player->size,
        ]);

        return [
            'id' => $this->id,
            'design_request_id' => $designRequest->id,
            'design_request_ref' => sprintf('DR-%d-%04d', $designRequest->created_at->year, $designRequest->id),
            'order_id' => $order?->id,
            'order_ref' => $order?->order_number,
            'stage' => $stage,
            'status_key' => $stage === 'order' ? $order->status : $designRequest->status,
            'status_label' => $statusLabel,
            'status_class' => $statusClass,
            'team_name' => $order->team_name ?? $designRequest->team_name,
            'template_name' => $order->template_name ?? $designRequest->template_name,
            'template_image' => $order ? $order->template_image_url : $designRequest->template_image_url,
            'client_name' => trim(
                ($designRequest->user->userInfo?->first_name ?? '') . ' ' .
                    ($designRequest->user->userInfo?->last_name ?? '')
            ) ?: $designRequest->user->email,
            'read' => $isRead,
            'updated_at' => $lastMessage?->created_at ?? $this->updated_at,
            'closed' => $stage === 'order' && $order->status === 'completed',
            'messages' => MessageResource::collection($this->messages),

            // Real order-spec data for the context panel — nothing here is
            // fabricated; it's a straight read of the design request/order.
            'unit_price' => $order->unit_price ?? $designRequest->template_price,
            'quantity' => $order->quantity ?? $designRequest->estimated_quantity,
            'primary_color' => $order->primary_color ?? $designRequest->primary_color,
            'secondary_color' => $order->secondary_color ?? $designRequest->secondary_color,
            'accent_color' => $order->accent_color ?? $designRequest->accent_color,
            'font_style' => $order->font_style ?? $designRequest->font_style,
            'players' => $players,
        ];
    }
}
