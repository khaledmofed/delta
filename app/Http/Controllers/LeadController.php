<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeadRequest;
use App\Insurers\Data\MotorQuoteInput;
use App\Mail\LeadReceived;
use App\Mail\NewLeadAlert;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/** Website lead forms -> leads table -> email to the customer and to the Delta team (queued). */
class LeadController extends Controller
{
    public function store(LeadRequest $request)
    {
        if ($request->isSpam()) {
            Log::notice('Lead honeypot triggered', ['ip' => $request->ip()]);

            return $this->done($request, null, Str::before($request->input('name'), ' '));
        }

        $v = $request->validated();
        $lead = Lead::create([
            'product' => $v['product'],
            'name' => trim($v['name']),
            'email' => strtolower(trim($v['email'])),
            'mobile' => MotorQuoteInput::mobile($v['mobile']),
            'status' => 'new',
            'data' => array_filter([
                'message' => isset($v['message']) ? trim($v['message']) : null,
                'contact_pref' => $v['contactPref'] ?? null,
                'emirate' => $v['emirate'] ?? null,
                'request_type' => $v['requestType'] ?? null,
                'policy_no' => isset($v['policyNo']) ? strtoupper(trim($v['policyNo'])) : null,
                'insurer' => $v['insurer'] ?? null,
                'source' => $v['source'],
                'page' => parse_url((string) $request->headers->get('referer'), PHP_URL_PATH) ?: null,
            ], fn ($x) => $x !== null && $x !== ''),
        ]);

        // A mail problem must never lose the lead: it is already saved and visible in the admin panel.
        try {
            Mail::to($lead->email, $lead->name)->queue(new LeadReceived($lead));
            Mail::to(config('site.leads_email'))->queue(new NewLeadAlert($lead));
        } catch (\Throwable $e) {
            Log::error('Lead emails could not be queued', ['lead' => $lead->reference, 'e' => $e->getMessage()]);
        }

        return $this->done($request, $lead->reference, Str::before($lead->name, ' '));
    }

    protected function done(LeadRequest $request, ?string $reference, string $firstName)
    {
        // Bots get the same answer as people (no reference is created for them).
        $reference ??= 'LD-'.now()->format('ymd').'-'.Str::upper(Str::random(5));

        return $request->expectsJson()
            ? response()->json(['reference' => $reference, 'name' => $firstName])
            : back()->with('lead_reference', $reference);
    }
}
