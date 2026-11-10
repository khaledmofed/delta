<?php

namespace App\Http\Controllers\Motor;

use App\Http\Controllers\Controller;
use App\Http\Requests\MotorQuoteRequest;
use App\Insurers\InsurerManager;
use App\Jobs\RunQuoteEngine;
use App\Models\Quote;
use App\Models\QuoteRequest;
use App\Services\MotorOrders;
use App\Services\MotorQuotes;
use App\Support\JourneyAccess;
use App\Support\MotorPresenter;
use Illuminate\Http\Request;

/** Motor journey: wizard -> quote request -> results (polling) -> compare -> select offer. */
class QuoteController extends Controller
{
    public function create(InsurerManager $insurers)
    {
        return view('site.motor.quote', [
            'insurers' => array_map(fn ($code) => MotorPresenter::insurer($code), array_keys($insurers->forProduct('motor'))),
        ]);
    }

    public function store(MotorQuoteRequest $request, MotorQuotes $quotes)
    {
        $qr = $quotes->create($request);
        JourneyAccess::grantQuote($qr);
        RunQuoteEngine::dispatch($qr->id);

        $url = route('motor.results', $qr);

        return $request->expectsJson() ? response()->json(['reference' => $qr->reference, 'redirect' => $url]) : redirect($url);
    }

    public function results(QuoteRequest $quoteRequest)
    {
        $qr = JourneyAccess::quote($quoteRequest);

        return view('site.motor.results', ['qr' => $qr, 'state' => MotorPresenter::results($qr)]);
    }

    public function status(QuoteRequest $quoteRequest)
    {
        return response()->json(MotorPresenter::results(JourneyAccess::quote($quoteRequest)->fresh()));
    }

    public function compare(Request $request, QuoteRequest $quoteRequest)
    {
        $qr = JourneyAccess::quote($quoteRequest);
        $ids = array_slice(array_filter(array_map('intval', explode(',', (string) $request->query('ids')))), 0, 3);
        $quotes = $qr->quotes()->whereIn('id', $ids)->get()->sortBy(fn ($q) => array_search($q->id, $ids))->values();

        return view('site.motor.compare', ['qr' => $qr, 'quotes' => $quotes]);
    }

    public function select(Quote $quote, MotorOrders $orders)
    {
        $qr = JourneyAccess::quote($quote->quoteRequest);
        if ($quote->valid_until && $quote->valid_until->isPast()) {
            return redirect()->route('motor.results', $qr)->with('toast', 'This offer has expired. Please request a new quote.');
        }
        $order = $orders->select($quote);
        JourneyAccess::grantOrder($order);

        return redirect()->route('motor.checkout', $order);
    }
}
