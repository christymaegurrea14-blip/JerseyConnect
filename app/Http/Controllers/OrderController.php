<?php

namespace App\Http\Controllers;

use App\Models\DesignRequest;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OrderController extends Controller
{
    /**
     * "My Orders" is the client's single unified list — it covers a kit
     * request from submission all the way through delivery, matching how
     * the client actually thinks about it (one thing, not two). Under the
     * hood a request is still a DesignRequest until it's approved+paid,
     * at which point an Order row exists for it — we just merge both real
     * sources here instead of splitting them into separate pages.
     */
    public function index(): Response
    {
        $orders = Order::query()
            ->where('user_id', Auth::id())
            ->with(['address', 'courierReceipt.courier', 'designRequest.players'])
            ->latest()
            ->get()
            ->map(fn(Order $order) => $this->transform($order));

        $designs = DesignRequest::query()
            ->where('user_id', Auth::id())
            ->whereDoesntHave('order')
            ->with(['template:id,image', 'players'])
            ->latest()
            ->get()
            ->map(function (DesignRequest $designRequest) {
                $array = $designRequest->toArray();
                $array['original_template_image'] = $designRequest->template?->image_url;
                unset($array['template']);

                return $array;
            });

        return Inertia::render('Client/Orders', [
            'orders' => $orders,
            'designs' => $designs,
            'readOnly' => false,
        ]);
    }

    public function updateAddress(Request $request, Order $order)
    {
        $this->authorizeOwner($order);

        if (! in_array($order->status, ['processing', 'in_production', 'ready_for_delivery'], true)) {
            return redirect()->back()->with('error', 'This order can no longer have its address changed.');
        }

        $validated = $request->validate([
            'recipient_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'regex:/^09\d{9}$/', 'size:11'],
            'line1' => ['required', 'string', 'max:255'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $order->address()->update($validated);

        return redirect()->back()->with('success', 'Delivery address updated.');
    }

    private function authorizeOwner(Order $order): void
    {
        if ($order->user_id !== Auth::id()) {
            throw new HttpException(403, 'This is not your order.');
        }
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
