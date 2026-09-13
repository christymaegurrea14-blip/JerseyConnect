<?php

namespace App\Http\Controllers;

use App\Models\Courier;
use App\Models\Order;
use App\Notifications\OrderStatusUpdated;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\ValidationException;

class AdminOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $orders = Order::query()
            ->with(['address', 'courierReceipt.courier', 'designRequest.players'])
            ->latest()
            ->get()
            ->map(fn(Order $order) => $this->transform($order));

        // dd($orders);

        return Inertia::render('Admin/Orders', [
            'orders' => $orders,
            'couriers' => Courier::where('status', true)->get(['id', 'name', 'site']),
            'openOrderId' => $request->query('order'),
        ]);
    }

    /**
     * A print-friendly packing slip: real roster + order/delivery info only,
     * meant to go inside the jersey package alongside the printed order.
     */
    public function packingSlip(Order $order)
    {
        $order->load(['address', 'designRequest.players']);

        return view('admin.orders.packing-slip', [
            'order' => $order,
            'players' => $order->designRequest?->players ?? collect(),
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $allowed = $order->nextAllowedStatuses();

        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', $allowed)],
            'courier_id' => ['required_if:status,shipped', 'nullable', 'exists:couriers,id'],
            'transaction_number' => ['required_if:status,shipped', 'nullable', 'string', 'max:100'],
            'shipping_fee' => ['required_if:status,shipped', 'nullable', 'integer', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validated['status'] !== 'processing' && ! $order->address?->isComplete()) {
            throw ValidationException::withMessages([
                'status' => 'Customer has not filled in the delivery address yet.',
            ]);
        }

        $order->update(['status' => $validated['status']]);

        if ($validated['status'] === 'shipped') {
            $order->courierReceipt()->create([
                'courier_id' => $validated['courier_id'],
                'transaction_number' => $validated['transaction_number'],
                'shipping_fee' => $validated['shipping_fee'],
                'date_shipped' => now(),
                'remarks' => $validated['remarks'] ?? null,
            ]);
            $order->update(['shipping_fee' => $validated['shipping_fee']]);
        }

        $order->refresh()->load('courierReceipt.courier')->user->notify(new OrderStatusUpdated($order));

        return redirect()->back()->with('success', 'Order status updated.');
    }

    private function transform(Order $order): array
    {
        return [
            ...$order->only([
                'id',
                'order_number',
                'design_request_id',
                'template_name',
                'team_name',
                'primary_color',
                'secondary_color',
                'accent_color',
                'font_style',
                'quantity',
                'unit_price',
                'shipping_fee',
                'status',
                'created_at',
                'updated_at',
            ]),
            'template_image' => $order->template_image_url,
            'address' => $order->address,
            'courier_receipt' => $order->courierReceipt,
            'players' => $order->designRequest?->players ?? [],
        ];
    }
}
