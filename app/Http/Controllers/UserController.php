<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('userInfo')
        ->where('role', 'client')
        ->latest()
        ->get();

        $recentOrders = Order::with('user.userInfo')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'team_name' => $order->team_name,
                'quantity' => $order->quantity,
                'status' => $order->status,
                'amount' => $order->amount,
                'created_at' => $order->created_at,
                'customer_name' => trim(($order->user?->userInfo?->first_name ?? '') . ' ' . ($order->user?->userInfo?->last_name ?? '')) ?: $order->user?->email,
            ]);

        return Inertia::render('Admin/Users', [
            'data' => $users,
            'recentOrders' => $recentOrders,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $user = User::findOrFail($id);
        $user->update($validated);

        return back()->with('success', 'User status updated successfully.');
    }
}
