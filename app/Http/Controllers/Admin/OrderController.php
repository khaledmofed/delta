<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

/** Orders with their state; a row opens the drawer with the state machine, payments and policy. */
class OrderController extends Controller
{
    public function index(Request $request, ?Order $order = null)
    {
        $q = Order::with(['customer', 'quote', 'policy'])->latest('id');
        if ($state = $request->query('state')) {
            $q->where('state', $state);
        }
        if ($search = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('reference', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($c) => $c->where('email', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")));
        }

        return view('admin.orders', [
            'orders' => $q->paginate(config('admin.per_page'))->withQueryString(),
            'states' => Order::STATES,
            'open' => $order?->load(['customer', 'quote', 'policy', 'payments', 'quoteRequest']),
        ]);
    }

    public function show(Request $request, Order $order)
    {
        return $this->index($request, $order);
    }
}
