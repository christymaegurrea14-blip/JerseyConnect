<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\DesignRequest;
use App\Models\Order;
use App\Notifications\DesignRequestStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AdminDesignRequestController extends Controller
{
    /**
     * The admin's own requests only — never another user's.
     */

    public function index(): Response
    {
        $data = DesignRequest::query()
            ->with(['template:id,image', 'players'])
            ->latest()
            ->get()
            ->map(function (DesignRequest $designRequest) {
                $array = $designRequest->toArray();
                $array['original_template_image'] = $designRequest->template?->image_url;
                unset($array['template']);

                return $array;
            });

        return Inertia::render('Admin/Design', [
            'data' => $data,
        ]);
    }

    public function update(Request $request, DesignRequest $designRequest)
    {
        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'quantity'         => ['required', 'integer', 'min:1'],
            'image'            => ['nullable', 'image', 'max:4096'],
            'primary_color'    => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color'  => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color'     => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'status'           => [
                'required',
                'in:pending_review,in_discussion,revision_requested,waiting_for_down_payment',
            ],
        ]);

        $data = [
            'template_name'      => $validated['name'],
            'estimated_quantity' => $validated['quantity'],
            'primary_color'      => $validated['primary_color'],
            'secondary_color'    => $validated['secondary_color'],
            'accent_color'       => $validated['accent_color'],
            'status'             => $validated['status'],
        ];

        if ($request->hasFile('image')) {
            $data['template_image'] = $request->file('image')->store('design-requests/designs', 'public');
        }

        $statusChanged = $designRequest->status !== $data['status'];

        $designRequest->update($data);

        if ($statusChanged) {
            $designRequest->user->notify(new DesignRequestStatusUpdated($designRequest));
        }

        return redirect()
            ->back()
            ->with('success', 'Design request updated.');
    }

    public function destroy(DesignRequest $designRequest)
    {
        $designRequest->update(['status' => 'cancelled']);

        return redirect()
            ->back()
            ->with('success', 'Design request cancelled.');
    }

    public function approvePayment(DesignRequest $designRequest)
    {
        if ($designRequest->status !== 'pending_down_payment_review') {
            return redirect()->back()->with('error', 'This request has no payment awaiting review.');
        }

        DB::transaction(function () use ($designRequest) {
            $designRequest->update(['status' => 'approved']);

            $order = Order::create([
                'order_number'      => Order::generateOrderNumber(),
                'design_request_id' => $designRequest->id,
                'user_id'           => $designRequest->user_id,
                'template_name'     => $designRequest->template_name,
                'template_image'    => $designRequest->template_image,
                'team_name'         => $designRequest->team_name,
                'primary_color'     => $designRequest->primary_color,
                'secondary_color'   => $designRequest->secondary_color,
                'accent_color'      => $designRequest->accent_color,
                'font_style'        => $designRequest->font_style,
                'quantity'          => $designRequest->estimated_quantity,
                'unit_price'        => $designRequest->template_price,
                'status'            => 'processing',
            ]);

            Address::create(['order_id' => $order->id]);
        });

        $designRequest->refresh()->user->notify(new DesignRequestStatusUpdated($designRequest));

        return redirect()->back()->with('success', 'Payment approved — order created.');
    }

    public function rejectPayment(DesignRequest $designRequest)
    {
        if ($designRequest->status !== 'pending_down_payment_review') {
            return redirect()->back()->with('error', 'This request has no payment awaiting review.');
        }

        $designRequest->update(['status' => 'waiting_for_down_payment']);

        $designRequest->user->notify(new DesignRequestStatusUpdated($designRequest));

        return redirect()->back()->with('success', 'Payment rejected — customer has been asked to resubmit.');
    }
}
