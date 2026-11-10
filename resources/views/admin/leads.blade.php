@extends('layouts.admin')
@section('title', 'Leads')
@use('App\Support\AdminUi', 'UI')

@section('content')
<div class="sec-h"><div><h2>Leads &amp; consultation requests</h2><p>Requests for advisor-assisted products (Medical, Home, Travel, Boat, Pet) and general enquiries.</p></div></div>
<form class="toolbar" method="get" action="{{ route('admin.leads') }}">
  <select class="sel" name="status" data-autosubmit><option value="">All statuses</option>@foreach ($statuses as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select>
  <select class="sel" name="product" data-autosubmit><option value="">All products</option>@foreach (array_merge(array_keys(config('site.services')), ['general', 'claims']) as $p)<option value="{{ $p }}" @selected(request('product') === $p)>{{ $p === 'claims' ? 'Claims & Support' : ucfirst($p) }}</option>@endforeach</select>
  @if (request()->hasAny(['status', 'product']))<a class="btn-d sm ghost" href="{{ route('admin.leads') }}">Clear</a>@endif
  <span class="sp"></span><span class="muted">{{ $leads->total() }} leads</span>
</form>
@if ($leads->isEmpty())
<div class="card-d empty-d"><i class="fal fa-inbox"></i>No leads yet. The website lead forms are connected in the next step.</div>
@else
<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Lead</th><th>Product</th><th>Name</th><th>Request</th><th>Assignee</th><th>Status</th></tr></thead><tbody>
@foreach ($leads as $l)
@php($svc = config('site.services.'.$l->product))
<tr><td><b>{{ $l->reference }}</b><span class="sub">{{ UI::dt($l->created_at) }}</span></td>
<td>@if ($svc)<span class="who"><i class="fal {{ $svc['icon'] }}" style="color:var(--teal)"></i>{{ $svc['name'] }}</span>@else{{ $l->productName() }}@endif</td>
<td>{{ $l->name }}<span class="sub"><a href="tel:+{{ $l->mobile }}">+{{ $l->mobile }}</a>{{ $l->email ? ' · '.$l->email : '' }}</span></td>
<td><span style="max-width:300px;display:inline-block;white-space:pre-line">{{ \Illuminate\Support\Str::limit($l->data['message'] ?? '—', 240) }}</span><span class="sub">{{ implode(' · ', array_filter([...array_values($l->supportDetails()), $l->data['contact_pref'] ?? null, $l->data['emirate'] ?? null, match ($l->data['source'] ?? null) { 'contact' => 'Contact page', 'claims' => 'Claims & Support', 'product' => 'Product page', default => null }])) }}</span></td>
@can('leads.update')
<td><form method="post" action="{{ route('admin.leads.update', $l) }}">@csrf @method('patch')<select class="sel" name="owner_id" data-autosubmit><option value="">Unassigned</option>@foreach ($staff as $u)<option value="{{ $u->id }}" @selected($l->owner_id === $u->id)>{{ $u->name }}</option>@endforeach</select></form></td>
<td><form method="post" action="{{ route('admin.leads.update', $l) }}">@csrf @method('patch')<select class="sel" name="status" data-autosubmit>@foreach ($statuses as $k => $v)<option value="{{ $k }}" @selected($l->status === $k)>{{ $v }}</option>@endforeach</select></form></td>
@else
<td>{{ $staff->firstWhere('id', $l->owner_id)?->name ?? 'Unassigned' }}</td><td>{{ UI::pill($l->status, $statuses[$l->status] ?? null) }}</td>
@endcan
</tr>
@endforeach
</tbody></table></div>
@include('admin.partials.pager', ['p' => $leads])
@endif
@endsection
