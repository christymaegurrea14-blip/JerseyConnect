<?php

namespace App\Http\Controllers;

use App\Models\DesignRequest;
use App\Models\Jersey;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Client/Home', [
            'data' => $this->activeJerseys(),
            'activeOrder' => $this->getActiveOrder(),
            'featuredJerseyId' => $this->getBestSellingJerseyId(),
            'pendingDesignRequestsCount' => DesignRequest::where('user_id', Auth::id())
                ->whereIn('status', ['pending_review', 'in_discussion', 'revision_requested'])
                ->count(),
        ]);
    }

    /**
     * The hero "featured" jersey automatically follows real demand — whichever
     * active template has sold the most sets across all orders. Falls back to
     * null (letting the frontend fall back to its own badge-priority pick)
     * once when the shop has no order history yet.
     */
    private function getBestSellingJerseyId(): ?int
    {
        $topSellerName = Order::select('template_name')
            ->selectRaw('SUM(quantity) as sold')
            ->groupBy('template_name')
            ->orderByDesc('sold')
            ->value('template_name');

        if (! $topSellerName) {
            return null;
        }

        return Jersey::where('status', 'active')
            ->where('name', $topSellerName)
            ->value('id');
    }

    /**
     * The full browsable catalog — reached from the "Browse Full Catalogue"
     * link on the home page preview.
     */
    public function catalogue(): Response
    {
        return Inertia::render('Client/Catalogue', [
            'data' => $this->activeJerseys(),
        ]);
    }

    private function activeJerseys()
    {
        return Jersey::query()
            ->where('status', 'active')
            ->latest()
            ->get()
            ->map(fn(Jersey $jersey) => [
                'id'            => $jersey->id,
                'name'          => $jersey->name,
                'description'   => $jersey->description,
                'sport'         => $jersey->sport,
                'price'         => $jersey->price,
                'badge'         => $jersey->badge,
                'primaryColor'  => $jersey->primary_color,
                'secondaryColor' => $jersey->secondary_color,
                'accentColor'   => $jersey->accent_color,
                'imagePath'     => $jersey->image_url,
            ]);
    }

    /**
     * The customer's most recent order that hasn't reached "completed" yet —
     * powers the real production-pipeline widget on the home page.
     */
    private function getActiveOrder(): ?array
    {
        $order = Order::where('user_id', Auth::id())
            ->where('status', '!=', 'completed')
            ->with('courierReceipt.courier')
            ->latest()
            ->first();

        if (! $order) {
            return null;
        }

        return [
            ...$order->only([
                'id', 'order_number', 'template_name', 'team_name',
                'quantity', 'status', 'created_at',
            ]),
            'courier_receipt' => $order->courierReceipt,
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'template_id'         => ['required', 'exists:jerseys,id'],
            'team_name'           => ['required', 'string', 'max:255'],
            'primary_color'       => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color'     => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color'        => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'font_style'          => ['nullable', 'string', 'max:255'],
            'estimated_quantity'  => ['nullable', 'integer', 'min:1'],
            'notes'               => ['nullable', 'string', 'max:2000'],
            'logo'                => ['nullable', 'image', 'max:4096'], // 4MB
        ]);

        $template = Jersey::where('status', 'active')
            ->findOrFail($validated['template_id']);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('design-requests/logos', 'public');
        }

        DesignRequest::create([
            'user_id'             => Auth::id(),
            'template_id'         => $template->id,
            'template_name'       => $template->name,
            'template_image'      => $template->image,
            'template_price'      => $template->price,
            'team_name'           => $validated['team_name'],
            'primary_color'       => $validated['primary_color'],
            'secondary_color'     => $validated['secondary_color'],
            'accent_color'        => $validated['accent_color'],
            'font_style'          => $validated['font_style'] ?? null,
            'estimated_quantity'  => $validated['estimated_quantity'] ?? null,
            'notes'               => $validated['notes'] ?? null,
            'logo_path'           => $logoPath,
            'status'              => 'pending_review',
        ]);

        return redirect()
            ->back()
            ->with('success', 'Your design request has been submitted!');
    }
}
