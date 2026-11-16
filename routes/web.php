<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HistoryController;
use App\Http\Controllers\Admin\OdpController;
use App\Http\Controllers\Admin\PaketController;
use App\Http\Controllers\Admin\RouterController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\SlotMonitorController;
use App\Http\Controllers\Admin\TelegramGroupController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PsbOrderController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('psb.create')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/psb/create', [PsbOrderController::class, 'create'])->name('psb.create');
    Route::post('/psb', [PsbOrderController::class, 'store'])->name('psb.store');

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('admin')
        ->group(function (): void {
            Route::get('/', fn () => redirect()->route('admin.dashboard'));
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('/riwayat', [HistoryController::class, 'index'])->name('riwayat.index');
            Route::get('/riwayat/export', [HistoryController::class, 'export'])->name('riwayat.export');
            Route::post('/orders/{order}/retry', [DashboardController::class, 'retryProvisioning'])
                ->name('orders.retry');
            Route::post('/slots/sync', [DashboardController::class, 'syncSlots'])
                ->name('slots.sync');

            Route::get('/slots', [SlotMonitorController::class, 'index'])->name('slots.index');

            Route::resource('routers', RouterController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::resource('sales', SaleController::class)->only(['index', 'store', 'update', 'destroy']);

            Route::patch('/odps/{odp}/toggle-upgrade', [OdpController::class, 'toggleUpgrade'])
                ->name('odps.toggle-upgrade');
            Route::resource('odps', OdpController::class)->only(['index', 'store', 'update', 'destroy']);

            Route::resource('pakets', PaketController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::resource('telegram-groups', TelegramGroupController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
        });
});
