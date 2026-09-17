<?php

namespace App\Http\Controllers;

use App\Models\Courier;
use App\Models\DesignRequest;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportsController extends Controller
{
    private const RANGE_DAYS = [
        '7' => 7,
        '30' => 30,
        '90' => 90,
    ];

    public function index(Request $request)
    {
        $range = $request->string('range')->value();
        $days = self::RANGE_DAYS[$range] ?? 30;
        $start = now()->subDays($days - 1)->startOfDay();

        return Inertia::render('Admin/Reports', [
            'range' => (string) $days,
            'sales' => $this->getSales($start, $days),
            'designPipeline' => $this->getDesignPipeline($start),
            'users' => $this->getUsers($start, $days),
            'shipping' => $this->getShipping($start),
        ]);
    }

    private function dailyBuckets(Carbon $start, int $days, string $table, string $dateColumn, string $selectRaw, string $valueKey): array
    {
        $rows = DB::table($table)
            ->where($dateColumn, '>=', $start)
            ->selectRaw("DATE($dateColumn) as day, $selectRaw as value")
            ->groupBy('day')
            ->pluck('value', 'day');

        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $out[] = [
                'date' => $date->format('Y-m-d'),
                'label' => $date->format('M d'),
                $valueKey => (int) ($rows[$date->format('Y-m-d')] ?? 0),
            ];
        }

        return $out;
    }

    private function getSales(Carbon $start, int $days): array
    {
        $revenueExpr = 'SUM((quantity * unit_price) + COALESCE(shipping_fee, 0))';

        $revenueTrend = $this->dailyBuckets($start, $days, 'orders', 'created_at', $revenueExpr, 'revenue');
        $ordersTrend = $this->dailyBuckets($start, $days, 'orders', 'created_at', 'COUNT(*)', 'orders');

        $ordersInRange = Order::where('created_at', '>=', $start);
        $totalRevenue = (int) (clone $ordersInRange)->selectRaw("$revenueExpr as total")->value('total') ?? 0;
        $totalOrders = (clone $ordersInRange)->count();
        $avgOrderValue = $totalOrders > 0 ? (int) round($totalRevenue / $totalOrders) : 0;

        $statusBreakdown = (clone $ordersInRange)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $bestSelling = Order::where('created_at', '>=', $start)
            ->select('template_name', DB::raw('SUM(quantity) as sold'), DB::raw('COUNT(*) as order_count'))
            ->groupBy('template_name')
            ->orderByDesc('sold')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->template_name,
                'sold' => (int) $row->sold,
                'orderCount' => (int) $row->order_count,
            ]);

        return [
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'avgOrderValue' => $avgOrderValue,
            'revenueTrend' => $revenueTrend,
            'ordersTrend' => $ordersTrend,
            'statusBreakdown' => [
                'processing' => (int) ($statusBreakdown['processing'] ?? 0),
                'in_production' => (int) ($statusBreakdown['in_production'] ?? 0),
                'ready_for_delivery' => (int) ($statusBreakdown['ready_for_delivery'] ?? 0),
                'shipped' => (int) ($statusBreakdown['shipped'] ?? 0),
                'delivered' => (int) ($statusBreakdown['delivered'] ?? 0),
                'completed' => (int) ($statusBreakdown['completed'] ?? 0),
            ],
            'bestSelling' => $bestSelling,
        ];
    }

    private function getDesignPipeline(Carbon $start): array
    {
        $requestsInRange = DesignRequest::where('created_at', '>=', $start);

        $statusBreakdown = (clone $requestsInRange)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $total = (clone $requestsInRange)->count();
        $approved = (int) ($statusBreakdown['approved'] ?? 0);
        $cancelled = (int) ($statusBreakdown['cancelled'] ?? 0);
        $decided = $approved + $cancelled;

        $avgTurnaroundDays = (clone $requestsInRange)
            ->whereIn('status', ['approved', 'cancelled'])
            ->selectRaw('AVG(DATEDIFF(updated_at, created_at)) as avg_days')
            ->value('avg_days');

        return [
            'total' => $total,
            'approved' => $approved,
            'cancelled' => $cancelled,
            'inProgress' => $total - $decided,
            'approvalRate' => $decided > 0 ? round(($approved / $decided) * 100, 1) : 0,
            'avgTurnaroundDays' => $avgTurnaroundDays !== null ? round((float) $avgTurnaroundDays, 1) : null,
            'statusBreakdown' => [
                'pending_review' => (int) ($statusBreakdown['pending_review'] ?? 0),
                'in_discussion' => (int) ($statusBreakdown['in_discussion'] ?? 0),
                'revision_requested' => (int) ($statusBreakdown['revision_requested'] ?? 0),
                'waiting_for_down_payment' => (int) ($statusBreakdown['waiting_for_down_payment'] ?? 0),
                'pending_down_payment_review' => (int) ($statusBreakdown['pending_down_payment_review'] ?? 0),
                'approved' => $approved,
                'cancelled' => $cancelled,
            ],
        ];
    }

    private function getUsers(Carbon $start, int $days): array
    {
        $newUsersTrend = $this->dailyBuckets($start, $days, 'users', 'created_at', 'COUNT(*)', 'users');

        $totalClients = User::where('role', 'client')->count();
        $newClients = User::where('role', 'client')->where('created_at', '>=', $start)->count();

        return [
            'totalClients' => $totalClients,
            'newClients' => $newClients,
            'newUsersTrend' => $newUsersTrend,
        ];
    }

    private function getShipping(Carbon $start): array
    {
        $perCourier = Courier::withCount(['courierReceipts as shipments' => function ($q) use ($start) {
                $q->where('date_shipped', '>=', $start);
            }])
            ->get()
            ->map(fn ($courier) => [
                'name' => $courier->name,
                'shipments' => $courier->shipments,
            ])
            ->sortByDesc('shipments')
            ->values();

        $ordersInRange = Order::where('created_at', '>=', $start);
        $shippedOrDelivered = (clone $ordersInRange)->whereIn('status', ['shipped', 'delivered', 'completed'])->count();
        $awaitingShipment = (clone $ordersInRange)->whereIn('status', ['processing', 'in_production', 'ready_for_delivery'])->count();

        return [
            'perCourier' => $perCourier,
            'shippedOrDelivered' => $shippedOrDelivered,
            'awaitingShipment' => $awaitingShipment,
        ];
    }
}
