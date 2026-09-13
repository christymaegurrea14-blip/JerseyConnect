<?php

use App\Http\Controllers\AdminDesignRequestController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\CourierController;
use App\Http\Controllers\DesignRequestController;
use App\Http\Controllers\DesignRequestPlayerController;
use App\Http\Controllers\GcashSettingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JerseyController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AdminMessageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Models\Jersey;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $jerseys = Jersey::query()
        ->where('status', 'active')
        ->latest()
        ->take(8)
        ->get()
        ->map(fn (Jersey $jersey) => [
            'id' => $jersey->id,
            'name' => $jersey->name,
            'sport' => $jersey->sport,
            'price' => $jersey->price,
            'badge' => $jersey->badge,
            'primaryColor' => $jersey->primary_color,
            'secondaryColor' => $jersey->secondary_color,
            'accentColor' => $jersey->accent_color,
            'imagePath' => $jersey->image_url,
        ]);

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'jerseys' => $jerseys,
    ]);
})->name('landing-page');

Route::middleware(['auth', 'client'])->prefix('client')->name('client.')->group(function () {
    Route::resource('home', HomeController::class)->only(['index']);
    Route::get('/catalogue', [HomeController::class, 'catalogue'])->name('catalogue.index');
    Route::resource('design', DesignRequestController::class)->only(['index']);
    Route::get('/design/{designRequest}', [DesignRequestController::class, 'show'])->name('design.show');
    Route::get('/design/{designRequest}/roster', [DesignRequestController::class, 'roster'])->name('design.roster');
    Route::get('/design/{designRequest}/pay', [DesignRequestController::class, 'payShow'])->name('design.pay.show');
    Route::get('/design/{designRequest}/players/template', [DesignRequestPlayerController::class, 'template'])->name('design.players.template');
    Route::get('/rosters', [DesignRequestController::class, 'rosters'])->name('rosters.index');
    Route::resource('orders', OrderController::class)->only(['index']);
    Route::resource('chat', ChatController::class)->only(['index']);

    Route::middleware(['throttle:api'])->group(function () {
        Route::resource('home', HomeController::class)->only(['store']);

        // Client Design Requests
        Route::post('/design/{designRequest}/pay', [DesignRequestController::class, 'pay'])->name('design.pay');
        Route::delete('/design/{designRequest}/cancel', [DesignRequestController::class, 'cancel'])->name('design.cancel');

        // Client Design Request Roster
        Route::post('/design/{designRequest}/players', [DesignRequestPlayerController::class, 'store'])->name('design.players.store');
        Route::post('/design/{designRequest}/players/import', [DesignRequestPlayerController::class, 'import'])->name('design.players.import');
        Route::put('/design/players/{player}', [DesignRequestPlayerController::class, 'update'])->name('design.players.update');
        Route::delete('/design/players/{player}', [DesignRequestPlayerController::class, 'destroy'])->name('design.players.destroy');

        //Client Orders
        Route::patch('/orders/{order}/address', [OrderController::class, 'updateAddress'])->name('orders.update-address');

        // Client Chat
        Route::post('/chat/{thread}/reply', [ChatController::class, 'reply'])->name('chat.reply');
        Route::patch('/chat/{thread}/read', [ChatController::class, 'markRead'])->name('chat.mark-read');
    });

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
    });
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard',[DashboardController::class, 'index'])->name('dashboard');

    Route::resource('jersey', JerseyController::class)->only(['index']);
    Route::resource('design', AdminDesignRequestController::class)->only(['index']);
    Route::resource('orders', AdminOrderController::class)->only(['index']);
    Route::get('/orders/{order}/packing-slip', [AdminOrderController::class, 'packingSlip'])->name('orders.packing-slip');
    Route::resource('couriers', CourierController::class)->only(['index']);
    Route::resource('gcash', GcashSettingController::class)->only(['index']);
    Route::resource('messages', AdminMessageController::class)->only(['index']);
    Route::resource('users', UserController::class)->only(['index']);

    Route::middleware(['throttle:api'])->group(function () {
        Route::resource('jersey', JerseyController::class)->only(['store', 'update', 'destroy']);

        // Admin Design Requests
        Route::put('/design/{designRequest}', [AdminDesignRequestController::class, 'update'])->name('design.update');
        Route::delete('/design/{designRequest}', [AdminDesignRequestController::class, 'destroy'])->name('design.cancel');
        Route::post('/design/{designRequest}/approve-payment', [AdminDesignRequestController::class, 'approvePayment'])->name('design.approve-payment');
        Route::post('/design/{designRequest}/reject-payment', [AdminDesignRequestController::class, 'rejectPayment'])->name('design.reject-payment');

        // Admin Orders
        Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.update-status');

        // Admin Couriers
        Route::resource('couriers', CourierController::class)->only(['store', 'update', 'destroy']);

        // Admin Gcash
        Route::put('/gcash/details', [GcashSettingController::class, 'updateDetails'])->name('gcash.details-update');
        Route::post('/gcash/qr', [GcashSettingController::class, 'updateQr'])->name('gcash.qr-update');

        // Admin Messages
        Route::post('/messages/{thread}/reply', [AdminMessageController::class, 'reply'])->name('messages.reply');
        Route::patch('/messages/{thread}/read', [AdminMessageController::class, 'markRead'])->name('messages.mark-read');

        // Admin Users
         Route::put('/users/{user}', [UserController::class, 'update'])->name('user.update');
    });

    Route::get('profile', [ProfileController::class, 'adminIndex'])->name('profile');
});

Route::middleware('auth')->group(function () {
    Route::put('/update-information', [ProfileController::class, 'updateInformation'])->name('update-information');
    Route::put('/update-credentials', [ProfileController::class, 'updateCredentials'])->name('update-credentials');
    Route::post('/update-avatar', [ProfileController::class, 'updateAvatar'])->name('update-avatar');
});

require __DIR__ . '/auth.php';
