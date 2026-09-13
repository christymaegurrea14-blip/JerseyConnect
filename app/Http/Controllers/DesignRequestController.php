<?php

namespace App\Http\Controllers;

use App\Models\DesignRequest;
use App\Models\GcashSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Inertia\Inertia;
use Inertia\Response;

class DesignRequestController extends Controller
{
    /**
     * "My Designs" was merged into the unified "My Orders" list — keep the
     * old URL working for anyone with it bookmarked.
     */
    public function index()
    {
        return redirect()->route('client.orders.index');
    }

    public function show(DesignRequest $designRequest): Response
    {
        $this->authorizeOwner($designRequest);
        $designRequest->load(['template:id,image', 'players']);

        return Inertia::render('Client/DesignDetail', [
            'request' => $this->transform($designRequest),
        ]);
    }

    public function roster(DesignRequest $designRequest): Response
    {
        $this->authorizeOwner($designRequest);
        $designRequest->load('players');

        return Inertia::render('Client/DesignRoster', [
            'request' => $this->transform($designRequest),
        ]);
    }

    public function payShow(DesignRequest $designRequest): Response
    {
        $this->authorizeOwner($designRequest);
        $designRequest->load('players');

        return Inertia::render('Client/DesignPayment', [
            'request' => $this->transform($designRequest),
            'gcash' => GcashSetting::current(),
        ]);
    }

    private function transform(DesignRequest $designRequest): array
    {
        $array = $designRequest->toArray();
        $array['original_template_image'] = $designRequest->template?->image_url;
        unset($array['template']);

        return $array;
    }

    public function pay(Request $request, DesignRequest $designRequest)
    {
        $this->authorizeOwner($designRequest);

        if ($designRequest->status !== 'waiting_for_down_payment') {
            return redirect()->back()->with('error', 'This request is not awaiting a down payment.');
        }

        $validated = $request->validate([
            'gcash_number'     => ['required', 'regex:/^09\d{9}$/', 'size:11'],
            'reference_number' => ['required', 'string', 'max:50'],
            'proof_image'      => ['required', 'image', 'max:4096'],
        ]);

        $proofPath = $request->file('proof_image')->store('design-requests/proofs', 'public');

        $designRequest->update([
            'gcash_number'     => $validated['gcash_number'],
            'reference_number' => $validated['reference_number'],
            'proof_image'      => $proofPath,
            'status'           => 'pending_down_payment_review',
        ]);

        return redirect()
            ->route('client.orders.index')
            ->with('success', 'Payment proof submitted — we\'ll review it shortly.');
    }

    /**
     * "Team Rosters" hub — every design request the client has, regardless
     * of lifecycle stage, since a roster can be managed at any point.
     */
    public function rosters(): Response
    {
        $data = DesignRequest::query()
            ->where('user_id', Auth::id())
            ->with('players')
            ->latest()
            ->get()
            ->map(fn(DesignRequest $designRequest) => $this->transform($designRequest));

        return Inertia::render('Client/Rosters', [
            'data' => $data,
        ]);
    }

    public function cancel(DesignRequest $designRequest)
    {
        $this->authorizeOwner($designRequest);

        $nonCancellable = ['revision_requested', 'waiting_for_down_payment', 'pending_down_payment_review', 'approved'];

        if (in_array($designRequest->status, $nonCancellable, true)) {
            return redirect()->back()->with('error', 'This request can no longer be cancelled.');
        }

        $designRequest->update(['status' => 'cancelled']);

        return redirect()->back()->with('success', 'Design request cancelled.');
    }

    private function authorizeOwner(DesignRequest $designRequest): void
    {
        if ($designRequest->user_id !== Auth::id()) {
            throw new HttpException(403, 'This is not your design request.');
        }
    }
}
