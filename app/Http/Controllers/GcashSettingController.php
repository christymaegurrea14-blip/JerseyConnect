<?php

namespace App\Http\Controllers;

use App\Models\DesignRequest;
use App\Models\GcashSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class GcashSettingController extends Controller
{
    public function index(): Response
    {
        $submissions = DesignRequest::query()
            ->whereNotNull('proof_image')
            ->with('user.userInfo')
            ->latest('updated_at')
            ->take(10)
            ->get()
            ->map(fn (DesignRequest $dr) => [
                'id' => $dr->id,
                'team_name' => $dr->team_name,
                // The real 50% down payment amount — template_price is a
                // PER-SET price, so this must factor in quantity too, not
                // just be shown on its own (that was the bug: a 6-set order
                // at ₱450/set was showing "₱450" as the amount instead of
                // the real ₱1,350 down payment).
                'amount' => (int) round($dr->template_price * ($dr->estimated_quantity ?: 1) * 0.5),
                'estimated_quantity' => $dr->estimated_quantity,
                'gcash_number' => $dr->gcash_number,
                'reference_number' => $dr->reference_number,
                'proof_image_url' => $dr->proof_image_url,
                'status' => $dr->status,
                'updated_at' => $dr->updated_at,
                'customer_name' => trim(($dr->user?->userInfo?->first_name ?? '') . ' ' . ($dr->user?->userInfo?->last_name ?? '')) ?: $dr->user?->email,
            ]);

        return Inertia::render('Admin/Gcash', [
            'gcash' => GcashSetting::current(),
            'submissions' => $submissions,
            'stats' => [
                'submitted' => DesignRequest::whereNotNull('proof_image')->count(),
                'pending_review' => DesignRequest::where('status', 'pending_down_payment_review')->count(),
                'approved' => DesignRequest::where('status', 'approved')->count(),
            ],
        ]);
    }

    public function updateDetails(Request $request)
    {
        $validated = $request->validate([
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'regex:/^09\d{9}$/', 'size:11'],
            'instructions' => ['nullable', 'string', 'max:2000'],
        ]);

        GcashSetting::current()->update($validated);

        return back()->with('success', 'GCash details updated.');
    }

    public function updateQr(Request $request)
    {
        $request->validate([
            'qr_image' => ['required', 'image', 'max:4096'], // 4MB
        ]);

        $gcash = GcashSetting::current();

        if ($gcash->qr_image_path) {
            Storage::disk('public')->delete($gcash->qr_image_path);
        }

        $path = $request->file('qr_image')->store('gcash', 'public');

        $gcash->update(['qr_image_path' => $path]);

        return back()->with('success', 'GCash QR code updated.');
    }
}
