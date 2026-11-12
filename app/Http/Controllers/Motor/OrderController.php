<?php

namespace App\Http\Controllers\Motor;

use App\Http\Controllers\Controller;
use App\Insurers\Data\MotorQuoteInput;
use App\Models\Order;
use App\Services\MotorOrders;
use App\Support\JourneyAccess;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/** Motor journey after an offer is selected: checkout -> insurer payment page -> confirmation. */
class OrderController extends Controller
{
    /** States in which the customer can still (re)start payment. */
    protected const PAYABLE = ['QUOTE_SELECTED', 'PAYMENT_PENDING', 'PAYMENT_FAILED'];

    public function checkout(Order $order, MotorOrders $orders)
    {
        $order = JourneyAccess::order($order);
        if (! in_array($order->state, self::PAYABLE, true)) {
            return redirect()->route('motor.confirmation', $order);
        }

        return view('site.motor.checkout', ['order' => $order, 'qr' => $order->quoteRequest, 'quote' => $order->quote, 'covers' => $orders->selectedCovers($order)]);
    }

    public function submitCheckout(Request $request, Order $order, MotorOrders $orders)
    {
        $order = JourneyAccess::order($order);
        if (! in_array($order->state, self::PAYABLE, true)) {
            return redirect()->route('motor.confirmation', $order);
        }
        $request->merge(['mobile' => preg_replace('/\D/', '', (string) $request->input('mobile'))]);
        $v = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:150'],
            'mobile' => ['required', 'regex:/^5\d{8}$/'],
            'policyStart' => ['nullable', 'date', 'after_or_equal:today', 'before_or_equal:'.CarbonImmutable::today()->addDays(30)->toDateString()],
            'd1' => ['accepted'], 'd2' => ['accepted'], 'd3' => ['accepted'],
        ], [
            'mobile.regex' => 'Please enter a UAE mobile number.',
            'policyStart.*' => 'Choose a date within the next 30 days.',
            'd1.accepted' => 'Please confirm all declarations.', 'd2.accepted' => 'Please confirm all declarations.', 'd3.accepted' => 'Please confirm all declarations.',
        ]);

        // Delta's contact details for this customer (the insurer already has the quote data).
        $order->customer?->update(['name' => $v['name'], 'email' => $v['email'], 'mobile' => MotorQuoteInput::mobile($v['mobile'])]);
        if (! empty($v['policyStart'])) {
            $order->update(['policy_start' => $v['policyStart']]);
        }

        return $this->startPayment($order, $orders, 'motor.checkout');
    }

    public function payment(Order $order, MotorOrders $orders)
    {
        $order = JourneyAccess::order($order);
        if (! in_array($order->state, self::PAYABLE, true)) {
            return redirect()->route('motor.confirmation', $order);
        }

        return view('site.motor.payment', ['order' => $order, 'quote' => $order->quote, 'covers' => $orders->selectedCovers($order)]);
    }

    public function pay(Order $order, MotorOrders $orders)
    {
        $order = JourneyAccess::order($order);
        if (! in_array($order->state, self::PAYABLE, true)) {
            return redirect()->route('motor.confirmation', $order);
        }

        return $this->startPayment($order, $orders, 'motor.payment');
    }

    public function confirmation(Order $order, MotorOrders $orders)
    {
        $order = $orders->refresh(JourneyAccess::viewing($order), 10);

        return view('site.motor.confirmation', ['order' => $order, 'quote' => $order->quote, 'policy' => $order->policy]);
    }

    public function status(Order $order, MotorOrders $orders)
    {
        $order = $orders->refresh(JourneyAccess::order($order), 10);

        return response()->json([
            'state' => $order->state,
            'done' => ! in_array($order->state, MotorOrders::OPEN_STATES, true),
        ]);
    }

    protected function startPayment(Order $order, MotorOrders $orders, string $backRoute)
    {
        $res = $orders->pay($order);
        if (! $res->ok) {
            return redirect()->route($backRoute, $order)->withInput()->withErrors(['checkout' => $orders->checkoutError($order, $res)]);
        }
        JourneyAccess::grantOrder($order);

        return redirect()->away($res->paymentUrl);
    }
}
