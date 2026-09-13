<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\CourierReceipt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourierController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Couriers', [
            'couriers' => Courier::withCount('courierReceipts')->latest()->get(),
            'stats' => [
                'total_waybills' => CourierReceipt::count(),
                'total_shipping_fees' => (int) CourierReceipt::sum('shipping_fee'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'site'   => ['nullable', 'url', 'max:255']
        ]);

        Courier::create($validated);

        return back()->with('success', 'Courier created successfully.');
    }

    public function update(Request $request, Courier $courier): RedirectResponse
    {
        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'site'   => ['nullable', 'url', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $courier->update($validated);

        return back()->with('success', 'Courier updated successfully.');
    }

    public function destroy(Courier $courier): RedirectResponse
    {
        if ($courier->courierReceipts()->exists()) {
            return back()->withErrors([
                'courier' => 'This courier has existing receipts and cannot be deleted. Set it to Inactive instead.',
            ]);
        }

        $courier->delete();

        return back()->with('success', 'Courier deleted successfully.');
    }
}
