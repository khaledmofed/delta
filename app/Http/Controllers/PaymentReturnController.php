<?php

namespace App\Http\Controllers;

use App\Services\MotorOrders;
use App\Support\JourneyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Landing point after the insurer-hosted payment page. The payment result is never
 * trusted from the URL: the order is confirmed by asking the insurer (policyStatus).
 */
class PaymentReturnController extends Controller
{
    public function __invoke(Request $request, string $result, MotorOrders $orders)
    {
        Log::info('Payment return', ['result' => $result, 'query' => array_keys($request->query())]);

        $order = JourneyAccess::currentOrder();
        if (! $order) {
            // Different browser / expired session: the scheduled poll will still confirm the order.
            return view('site.payment-return', ['result' => $result]);
        }
        $orders->refresh($order);

        return redirect()->route('motor.confirmation', $order);
    }
}
