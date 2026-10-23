<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\HousekeepingController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomServiceController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StayController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Rooms: everyone can view and change status; only the owner adds/edits/deletes (below).
    Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::patch('/rooms/{room}/status', [RoomController::class, 'updateStatus'])->name('rooms.status');

    // Guests (no delete: history must stay intact).
    Route::resource('guests', GuestController::class)->except('destroy');

    // Stays: reserve / check-in / check-out and everything billed to a room.
    Route::get('/stays', [StayController::class, 'index'])->name('stays.index');
    Route::get('/stays/create', [StayController::class, 'create'])->name('stays.create');
    Route::post('/stays', [StayController::class, 'store'])->name('stays.store');
    Route::get('/stays/{stay}', [StayController::class, 'show'])->name('stays.show');
    Route::post('/stays/{stay}/check-in', [StayController::class, 'checkIn'])->name('stays.check-in');
    Route::post('/stays/{stay}/cancel', [StayController::class, 'cancel'])->name('stays.cancel');
    Route::get('/stays/{stay}/checkout', [StayController::class, 'checkoutForm'])->name('stays.checkout');
    Route::post('/stays/{stay}/checkout', [StayController::class, 'checkout']);
    Route::post('/stays/{stay}/charges', [StayController::class, 'addCharge'])->name('stays.charges.store');
    Route::post('/stays/{stay}/amenities', [StayController::class, 'issueAmenity'])->name('stays.amenities.store');
    Route::post('/stays/{stay}/orders', [RoomServiceController::class, 'store'])->name('stays.orders.store');
    Route::patch('/orders/{order}', [RoomServiceController::class, 'updateStatus'])->name('orders.status');
    Route::post('/stays/{stay}/payments', [PaymentController::class, 'store'])->name('stays.payments.store');
    Route::post('/stays/{stay}/invoice', [InvoiceController::class, 'generate'])->name('stays.invoice');

    // Billing
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

    // Housekeeping & maintenance
    Route::get('/housekeeping', [HousekeepingController::class, 'index'])->name('housekeeping.index');
    Route::post('/housekeeping', [HousekeepingController::class, 'store'])->name('housekeeping.store');
    Route::patch('/housekeeping/{task}', [HousekeepingController::class, 'update'])->name('housekeeping.update');
    Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
    Route::post('/maintenance', [MaintenanceController::class, 'store'])->name('maintenance.store');
    Route::patch('/maintenance/{maintenance}', [MaintenanceController::class, 'update'])->name('maintenance.update');

    // Owner only
    Route::middleware('owner')->group(function () {
        Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
        Route::put('/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');

        Route::resource('room-types', RoomTypeController::class)->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['room-types' => 'roomType']);

        Route::get('/catalog/{kind}', [CatalogController::class, 'index'])->name('catalog.index');
        Route::post('/catalog/{kind}', [CatalogController::class, 'store'])->name('catalog.store');
        Route::put('/catalog/{kind}/{id}', [CatalogController::class, 'update'])->name('catalog.update');
        Route::delete('/catalog/{kind}/{id}', [CatalogController::class, 'destroy'])->name('catalog.destroy');

        Route::patch('/invoices/{invoice}/discount', [InvoiceController::class, 'discount'])->name('invoices.discount');

        Route::resource('staff', StaffController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{type}', [ReportController::class, 'show'])->name('reports.show');
    });
});
