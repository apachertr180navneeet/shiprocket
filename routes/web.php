<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\OrderController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('/', [HomeController::class, 'index'])->name('/');
Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::name('admin.')->prefix('admin')->group(function () {
    Route::get('/', [AdminAuthController::class, 'index']);

    Route::get('login', [AdminAuthController::class, 'login'])->name('login');

    Route::post('login', [AdminAuthController::class, 'postLogin'])->name('login.post');

    Route::get('forget-password', [AdminAuthController::class, 'showForgetPasswordForm'])->name('forget.password.get');

    Route::post('forget-password', [AdminAuthController::class, 'submitForgetPasswordForm'])->name('forget.password.post');

    Route::get('reset-password/{token}', [AdminAuthController::class, 'showResetPasswordForm'])->name('reset.password.get');

    Route::post('reset-password', [AdminAuthController::class, 'submitResetPasswordForm'])->name('reset.password.post');

    Route::middleware(['admin'])->group(function () {
    	Route::get('dashboard', [AdminAuthController::class, 'adminDashboard'])->name('dashboard');

        Route::get('change-password', [AdminAuthController::class, 'changePassword'])->name('change.password');

        Route::post('update-password', [AdminAuthController::class, 'updatePassword'])->name('update.password');

        Route::get('logout', [AdminAuthController::class, 'logout'])->name('logout');

        Route::get('profile', [AdminAuthController::class, 'adminProfile'])->name('profile');

        Route::post('profile', [AdminAuthController::class, 'updateAdminProfile'])->name('update.profile');

        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{id}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::put('orders/{id}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.updateStatus');
        Route::put('orders/{id}/payment-status', [AdminOrderController::class, 'updatePaymentStatus'])->name('orders.updatePaymentStatus');
        Route::post('orders/{id}/shipment', [AdminOrderController::class, 'addShipment'])->name('orders.addShipment');
        Route::post('orders/{id}/pickup', [AdminOrderController::class, 'schedulePickup'])->name('orders.pickup');
        Route::get('orders/{id}/label', [AdminOrderController::class, 'generateLabel'])->name('orders.label');
        Route::get('orders/{id}/invoice', [AdminOrderController::class, 'generateInvoice'])->name('orders.invoice');
        Route::get('orders/{id}/track', [AdminOrderController::class, 'track'])->name('orders.track');

    });

});

Route::name('web.')->prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('/', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/{id}/track', [OrderController::class, 'track'])->name('orders.track');
    Route::get('/{id}/edit', [OrderController::class, 'edit'])->name('orders.edit');
    Route::put('/{id}', [OrderController::class, 'update'])->name('orders.update');
    Route::delete('/{id}', [OrderController::class, 'destroy'])->name('orders.destroy');
});



