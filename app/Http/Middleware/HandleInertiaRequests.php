<?php

namespace App\Http\Middleware;

use App\Models\DesignRequest;
use App\Models\MessageThread;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Inertia\Inertia;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? $request->user()->load('userInfo') : null,
            ],
            'unreadMessagesCount' => Inertia::always(
                fn () => $this->unreadMessagesCount($request)
            ),
            'pendingDesignRequestsCount' => Inertia::always(
                fn () => $request->user()?->role === 'admin'
                    ? DesignRequest::where('status', 'pending_review')->count()
                    : 0
            ),
            'pendingGcashCount' => Inertia::always(
                fn () => $request->user()?->role === 'admin'
                    ? DesignRequest::where('status', 'pending_down_payment_review')
                        ->whereNotNull('proof_image')
                        ->count()
                    : 0
            ),
        ]);
    }

    private function unreadMessagesCount(Request $request): int
    {
        $user = $request->user();
        if (! $user) {
            return 0;
        }

        $query = MessageThread::query()->with('messages');

        if ($user->role !== 'admin') {
            $query->whereHas('designRequest', fn($q) => $q->where('user_id', $user->id));
        }

        return $query->get()->filter(function (MessageThread $thread) use ($user) {
            $last = $thread->messages->sortByDesc('created_at')->first();

            if (! $last || $last->user_id === $user->id) {
                return false;
            }

            $lastReadAt = $user->role === 'admin'
                ? $thread->admin_last_read_at
                : $thread->client_last_read_at;

            return ! $lastReadAt || $last->created_at->gt($lastReadAt);
        })->count();
    }
}
