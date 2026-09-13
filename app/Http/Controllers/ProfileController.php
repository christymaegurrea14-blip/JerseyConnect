<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\DesignRequest;
use App\Models\Jersey;
use App\Models\Order;
use App\Models\User;
use App\Notifications\PasswordChanged;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{

    public function index(Request $request): Response
    {
        return Inertia::render('Client/Profile', [
            'stats' => $this->clientStats($request->user()),
        ]);
    }

    /**
     * Display the admin's own profile.
     */
    public function adminIndex(Request $request): Response
    {
        return Inertia::render('Admin/Profile', [
            'stats' => [
                'jerseys_count' => Jersey::count(),
                'orders_count' => Order::count(),
                'clients_count' => User::where('role', 'client')->count(),
                'pending_reviews' => DesignRequest::where('status', 'pending_review')->count(),
            ],
        ]);
    }

    /**
     * Real, per-client account stats for the profile hero — no fabricated
     * numbers, just straight counts scoped to this user.
     */
    private function clientStats(User $user): array
    {
        return [
            'design_requests_count' => DesignRequest::where('user_id', $user->id)->count(),
            'orders_count' => Order::where('user_id', $user->id)->count(),
            'total_sets' => (int) Order::where('user_id', $user->id)->sum('quantity'),
            'teams_count' => DesignRequest::where('user_id', $user->id)
                ->pluck('team_name')
                ->unique()
                ->count(),
        ];
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }
    
    /**
     * Update the user's information.
     */
    public function updateInformation(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'phone' => ['required', 'regex:/^9\d{9}$/'],
            'address' => 'required|string|max:255',
        ]);

        $request->user()->userInfo()->update([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'birth_date' => $request->birth_date,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        if ($request->user()->role === 'admin') {
            return Redirect::route('admin.profile');
        }

        return Redirect::route('client.profile.index');
    }

    /**
     * Update the user's profile picture.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:4096'], // 4MB
        ]);

        $userInfo = $request->user()->userInfo;

        if ($userInfo->avatar) {
            Storage::disk('public')->delete($userInfo->avatar);
        }

        $userInfo->update([
            'avatar' => $request->file('avatar')->store('avatars', 'public'),
        ]);

        return back();
    }

    /**
     * Update the user's credentials.
     */
    public function updateCredentials(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($request->user()->id)],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        $request->user()->update([
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            $request->user()->update([
                'password' => Hash::make($request->password),
            ]);

            $request->user()->notify(new PasswordChanged());
        }

        if ($request->user()->role === 'admin') {
            return Redirect::route('admin.profile');
        }

        return Redirect::route('client.profile.index');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
