<?php

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

// Insurers redirect the customer here after their hosted payment page.
Route::get('/motor/payment/return/{result}', PaymentReturnController::class)
    ->whereIn('result', ['success', 'fail'])
    ->name('payment.return');
