<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;

/** Quote requests with each insurer's result (status, timing, error code, request id). */
class QuoteRequestController extends Controller
{
    public function index(Request $request, ?QuoteRequest $quoteRequest = null)
    {
        $q = QuoteRequest::with('customer')->withCount('quotes')->latest('id');
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        if ($search = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('reference', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($c) => $c->where('email', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")));
        }

        return view('admin.quote-requests', [
            'requests' => $q->paginate(config('admin.per_page'))->withQueryString(),
            'statuses' => ['QUOTE_REQUESTED', 'QUOTING', 'QUOTES_RECEIVED', 'NO_QUOTES'],
            'open' => $quoteRequest?->load(['customer', 'quotes', 'documents']),
        ]);
    }

    public function show(Request $request, QuoteRequest $quoteRequest)
    {
        return $this->index($request, $quoteRequest);
    }
}
