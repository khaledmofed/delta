<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Motor\OrderController;
use App\Http\Controllers\Motor\QuoteController;
use App\Http\Controllers\PaymentReturnController;
use Illuminate\Support\Facades\Route;

// Public website (converted from the delta-website prototype).
// Flat URLs on purpose: the prototype JS builds relative paths like "images/insurers/x.png".
Route::view('/', 'site.home')->name('home');
Route::view('/about', 'site.about')->name('about');
Route::view('/services', 'site.services')->name('services');
Route::view('/contact', 'site.contact')->name('contact');

foreach (array_keys(config('site.services')) as $product) {
    Route::view("/{$product}-insurance", "site.products.{$product}")->name("products.{$product}");
}

// Motor journey: quote -> compare -> checkout -> insurer payment page -> confirmation.
Route::controller(QuoteController::class)->group(function () {
    Route::get('/motor-quote', 'create')->name('motor.quote');
    Route::post('/motor-quote', 'store')->middleware('throttle:10,1')->name('motor.quote.store');
    Route::get('/motor-quote/{quoteRequest}/results', 'results')->name('motor.results');
    Route::get('/motor-quote/{quoteRequest}/status', 'status')->name('motor.status');
    Route::get('/motor-quote/{quoteRequest}/compare', 'compare')->name('motor.compare');
    Route::post('/motor/offers/{quote}/select', 'select')->name('motor.select');
});
Route::controller(OrderController::class)->prefix('/motor/orders/{order}')->group(function () {
    Route::get('/checkout', 'checkout')->name('motor.checkout');
    Route::post('/checkout', 'submitCheckout')->middleware('throttle:10,1')->name('motor.checkout.submit');
    Route::get('/payment', 'payment')->name('motor.payment');
    Route::post('/payment', 'pay')->middleware('throttle:10,1')->name('motor.pay');
    Route::get('/confirmation', 'confirmation')->name('motor.confirmation');
    Route::get('/status', 'status')->name('motor.order.status');
});

// Admin panel (staff only). Permissions per role: config/admin.php.
Route::prefix('/admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [Admin\AuthController::class, 'show'])->name('login');
        Route::post('/login', [Admin\AuthController::class, 'login'])->middleware('throttle:20,1')->name('login.submit');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [Admin\AuthController::class, 'logout'])->name('logout');
        Route::get('/', Admin\DashboardController::class)->name('dashboard');

        Route::middleware('can:orders.view')->group(function () {
            Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders');
            Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
        });
        Route::middleware('can:quotes.view')->group(function () {
            Route::get('/quote-requests', [Admin\QuoteRequestController::class, 'index'])->name('quote-requests');
            Route::get('/quote-requests/{quoteRequest}', [Admin\QuoteRequestController::class, 'show'])->name('quote-requests.show');
        });
        Route::middleware('can:api_logs.view')->group(function () {
            Route::get('/api-logs', [Admin\ApiLogController::class, 'index'])->name('api-logs');
            Route::get('/api-logs/{log}', [Admin\ApiLogController::class, 'show'])->name('api-logs.show');
        });
        Route::get('/insurers', [Admin\InsurerController::class, 'index'])->middleware('can:providers.view')->name('insurers');
        Route::middleware('can:providers.update')->group(function () {
            Route::post('/insurers/{code}/toggle', [Admin\InsurerController::class, 'toggle'])->name('insurers.toggle');
            Route::post('/insurers/{code}/test', [Admin\InsurerController::class, 'test'])->middleware('throttle:10,1')->name('insurers.test');
        });
        Route::get('/leads', [Admin\LeadController::class, 'index'])->middleware('can:leads.view')->name('leads');
        Route::patch('/leads/{lead}', [Admin\LeadController::class, 'update'])->middleware('can:leads.update')->name('leads.update');
    });
});

// Insurers redirect the customer here after their hosted payment page.
Route::get('/motor/payment/return/{result}', PaymentReturnController::class)
    ->whereIn('result', ['success', 'fail'])
    ->name('payment.return');
