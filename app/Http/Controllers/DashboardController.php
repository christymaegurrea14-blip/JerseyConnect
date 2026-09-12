<?php

namespace App\Http\Controllers;

use App\Models\DesignRequest;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $thisWeekStart = $now->copy()->startOfWeek();
        $lastWeekStart = $now->copy()->subWeek()->startOfWeek();
        $lastWeekEnd = $now->copy()->subWeek()->endOfWeek();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $this->getStats($thisWeekStart, $lastWeekStart, $lastWeekEnd),
            'bestSellingTemplates' => $this->getBestSellingTemplates(),
            'recentOrders' => $this->getRecentOrders(),
            'needsAttention' => $this->getNeedsAttention(),
            'designRequests' => $this->getRecentDesignRequests(),
            'gcashTransactions' => $this->getGcashTransactions(),
            'recentMessages' => $this->getRecentMessages(),
        ]);
    }

    private function percentChange(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function getStats(Carbon $thisWeekStart, Carbon $lastWeekStart, Carbon $lastWeekEnd): array
    {
        // Orders
        $ordersThisWeek = Order::where('created_at', '>=', $thisWeekStart)->count();
        $ordersLastWeek = Order::whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])->count();

        // Revenue = sum(quantity * unit_price) + shipping_fee
        $revenueExpr = DB::raw('SUM((quantity * unit_price) + COALESCE(shipping_fee, 0)) as total');

        $revenueThisWeek = (int) Order::where('created_at', '>=', $thisWeekStart)
            ->select($revenueExpr)->value('total') ?? 0;
        $revenueLastWeek = (int) Order::whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])
            ->select($revenueExpr)->value('total') ?? 0;

        // Pending design requests
        $pendingDesignRequests = DesignRequest::where('status', 'pending_review')->count();

        // Pending GCash verification (proof submitted, awaiting admin)
        $pendingGcash = DesignRequest::where('status', 'pending_down_payment_review')
            ->whereNotNull('proof_image')
            ->count();

        // Unread messages (from clients, not yet read by admin)
        $unreadMessages = Message::whereHas('user', fn($q) => $q->where('role', 'client'))
            ->whereHas('thread', function ($q) {
                $q->whereColumn('messages.created_at', '>', 'message_threads.admin_last_read_at')
                    ->orWhereNull('message_threads.admin_last_read_at');
            })
            ->count();

        // New users this/last week
        $newUsersThisWeek = User::where('role', 'client')->where('created_at', '>=', $thisWeekStart)->count();
        $newUsersLastWeek = User::where('role', 'client')
            ->whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])->count();

        return [
            'totalOrders' => [
                'value' => $ordersThisWeek,
                'change' => $this->percentChange($ordersThisWeek, $ordersLastWeek),
            ],
            'revenue' => [
                'value' => $revenueThisWeek,
                'change' => $this->percentChange($revenueThisWeek, $revenueLastWeek),
            ],
            'pendingDesignRequests' => [
                'value' => $pendingDesignRequests,
                'change' => 0,
            ],
            'pendingGcash' => [
                'value' => $pendingGcash,
                'change' => 0,
            ],
            'unreadMessages' => [
                'value' => $unreadMessages,
                'change' => 0,
            ],
            'newUsers' => [
                'value' => $newUsersThisWeek,
                'change' => $this->percentChange($newUsersThisWeek, $newUsersLastWeek),
            ],
        ];
    }

    private function getBestSellingTemplates()
    {
        return Order::select(
                'template_name',
                DB::raw('SUM(quantity) as sold'),
                DB::raw('MAX(template_image) as template_image'),
            )
            ->groupBy('template_name')
            ->orderByDesc('sold')
            ->limit(4)
            ->get()
            ->map(fn($row) => [
                'name' => $row->template_name,
                'sold' => (int) $row->sold,
                'image' => $row->template_image ? Storage::disk('public')->url($row->template_image) : null,
            ]);
    }

    private function orderStatusLabel(string $status): string
    {
        return match ($status) {
            'processing' => 'Processing',
            'in_production' => 'In Production',
            'ready_for_delivery' => 'Ready for Delivery',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'completed' => 'Completed',
            default => ucfirst($status),
        };
    }

    private function getRecentOrders()
    {
        return Order::with(['courierReceipt.courier', 'user.userInfo'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($order) => [
                'id' => $order->order_number,
                'customer' => $order->user->userInfo
                    ? trim($order->user->userInfo->first_name . ' ' . $order->user->userInfo->last_name)
                    : $order->user->email,
                'template' => $order->template_name,
                'amount' => $order->amount,
                'status' => $this->orderStatusLabel($order->status),
                'courier' => $order->courierReceipt->courier->name ?? '-',
                'date' => $order->created_at->format('M d, Y'),
            ]);
    }

    private function getNeedsAttention(): array
    {
        $pendingGcash = DesignRequest::where('status', 'pending_down_payment_review')
            ->whereNotNull('proof_image')->count();

        $pendingDesignRequests = DesignRequest::where('status', 'pending_review')->count();

        $stuckOrders = Order::where('status', 'processing')
            ->where('created_at', '<=', now()->subDays(3))
            ->count();

        return [
            [
                'label' => 'GCash payments awaiting verification',
                'count' => $pendingGcash,
                'icon' => 'fa-solid fa-wallet',
            ],
            [
                'label' => 'Design requests pending review',
                'count' => $pendingDesignRequests,
                'icon' => 'fa-solid fa-spray-can-sparkles',
            ],
            [
                'label' => 'Orders stuck in Processing 3+ days',
                'count' => $stuckOrders,
                'icon' => 'fa-solid fa-triangle-exclamation',
            ],
        ];
    }

    private function designStatusLabel(string $status): string
    {
        return match ($status) {
            'pending_review' => 'New',
            'in_discussion' => 'In Discussion',
            'revision_requested' => 'Revision Requested',
            'waiting_for_down_payment' => 'Waiting for Payment',
            'pending_down_payment_review' => 'In Review',
            'approved' => 'Approved',
            'cancelled' => 'Rejected',
            default => ucfirst($status),
        };
    }

    private function getRecentDesignRequests()
    {
        return DesignRequest::with('user.userInfo')
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn($dr) => [
                'customer' => $dr->user->userInfo
                    ? trim($dr->user->userInfo->first_name . ' ' . $dr->user->userInfo->last_name)
                    : $dr->user->email,
                'status' => $this->designStatusLabel($dr->status),
                'date' => $dr->created_at->format('M d, Y'),
            ]);
    }

    private function getGcashTransactions()
    {
        return DesignRequest::with('user.userInfo')
            ->whereNotNull('reference_number')
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn($dr) => [
                'ref' => $dr->reference_number,
                'customer' => $dr->user->userInfo
                    ? trim($dr->user->userInfo->first_name . ' ' . $dr->user->userInfo->last_name)
                    : $dr->user->email,
                'amount' => (int) round($dr->template_price * 0.5), // adjust if you store an actual down payment amount
                'status' => $dr->status === 'pending_down_payment_review' ? 'Pending' : 'Verified',
            ]);
    }

    private function getRecentMessages()
    {
        return Message::with('user.userInfo')
            ->whereHas('user', fn($q) => $q->where('role', 'client'))
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn($m) => [
                'customer' => $m->user->userInfo
                    ? trim($m->user->userInfo->first_name . ' ' . $m->user->userInfo->last_name)
                    : $m->user->email,
                'preview' => \Illuminate\Support\Str::limit($m->body, 60),
                'time' => $m->created_at->isToday()
                    ? $m->created_at->format('g:i A')
                    : ($m->created_at->isYesterday() ? 'Yesterday' : $m->created_at->format('M d')),
            ]);
    }
}
