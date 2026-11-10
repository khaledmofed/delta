<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Landing point after the insurer-hosted payment page. The payment result is never
 * trusted from the URL: the order is confirmed by asking the insurer (policyStatus).
 */
class PaymentReturnController extends Controller
{
    public function __invoke(Request $request, string $result)
    {
        Log::info('Payment return', ['result' => $result, 'query' => $request->query()]);

        return view('site.payment-return', ['result' => $result]);
    }
}
