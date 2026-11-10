<?php

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

// Insurers redirect the customer here after their hosted payment page.
Route::get('/motor/payment/return/{result}', PaymentReturnController::class)
    ->whereIn('result', ['success', 'fail'])
    ->name('payment.return');
