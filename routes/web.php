<?php

use App\Http\Controllers\PaymentReturnController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'site.home')->name('home');

// Insurers redirect the customer here after their hosted payment page.
Route::get('/motor/payment/return/{result}', PaymentReturnController::class)
    ->whereIn('result', ['success', 'fail'])
    ->name('payment.return');
