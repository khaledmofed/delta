<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Leads & consultation requests (advisor-assisted products). */
class LeadController extends Controller
{
    public function index(Request $request)
    {
        $q = Lead::latest('id');
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        if ($product = $request->query('product')) {
            $q->where('product', $product);
        }

        return view('admin.leads', [
            'leads' => $q->paginate(config('admin.per_page'))->withQueryString(),
            'staff' => User::orderBy('name')->get(['id', 'name']),
            'statuses' => config('admin.lead_statuses'),
        ]);
    }

    public function update(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'status' => ['sometimes', Rule::in(array_keys(config('admin.lead_statuses')))],
            'owner_id' => ['sometimes', 'nullable', 'exists:users,id'],
        ]);
        $before = $lead->only(array_keys($data));
        $lead->update($data);
        Audit::log('lead.updated', $lead, $before, $lead->only(array_keys($data)));

        return back()->with('toast', "Lead {$lead->reference} updated.");
    }
}
