<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Order;
use App\Models\QuoteRequest;
use App\Support\InsurerHealth;
use Illuminate\Support\Carbon;

/** Operations overview — real counts only (no ERP / commission figures until those exist). */
class DashboardController extends Controller
{
    public function __invoke()
    {
        $issued = Order::where('state', 'POLICY_ISSUED');
        $qrCount = QuoteRequest::count();
        $policies = (clone $issued)->count();
        $paid = Order::whereIn('state', ['PAYMENT_SUCCESS', 'INSURANCE_PROCESSING', 'POLICY_REQUESTED', 'POLICY_ISSUED'])->count();

        $attention = collect()
            ->merge(Order::where('state', 'PAYMENT_FAILED')->latest()->limit(5)->get()->map(fn ($o) => ['bad', "Payment failed — {$o->reference}", route('admin.orders.show', $o)]))
            ->merge(Order::whereIn('state', ['PAYMENT_SUCCESS', 'INSURANCE_PROCESSING'])->latest()->limit(5)->get()->map(fn ($o) => ['warn', "Paid, awaiting policy — {$o->reference}", route('admin.orders.show', $o)]))
            ->merge(Order::where('state', 'PAYMENT_PENDING')->where('updated_at', '<', now()->subHours(2))->latest()->limit(5)->get()->map(fn ($o) => ['warn', "Payment pending > 2 h — {$o->reference}", route('admin.orders.show', $o)]))
            ->merge(Lead::where('status', 'new')->latest()->limit(5)->get()->map(fn ($l) => ['info', 'New lead — '.ucfirst($l->product).' · '.$l->name, route('admin.leads')]));

        $start = Carbon::today()->subDays(13);
        $perDay = QuoteRequest::where('created_at', '>=', $start)->get(['created_at'])
            ->groupBy(fn ($q) => $q->created_at->toDateString())->map->count();
        $days = collect(range(0, 13))->map(fn ($i) => ['d' => $start->copy()->addDays($i), 'n' => $perDay[$start->copy()->addDays($i)->toDateString()] ?? 0]);

        $premiumByInsurer = (clone $issued)->selectRaw('insurer, sum(premium) as total')->groupBy('insurer')->pluck('total', 'insurer');

        return view('admin.dashboard', [
            'kpi' => [
                'premium' => (float) (clone $issued)->sum('premium'),
                'policies' => $policies,
                'quoteRequests' => $qrCount,
                'conversion' => $qrCount ? round($policies / $qrCount * 100) : 0,
                'attention' => $attention->count(),
            ],
            'funnel' => [
                ['Quote requests', $qrCount],
                ['Quotes received', QuoteRequest::where('status', 'QUOTES_RECEIVED')->count()],
                ['Offer selected', Order::count()],
                ['Paid', $paid],
                ['Policy issued', $policies],
            ],
            'attention' => $attention->take(7),
            'days' => $days,
            'premiumByInsurer' => $premiumByInsurer,
            'health' => InsurerHealth::all(),
        ]);
    }
}
