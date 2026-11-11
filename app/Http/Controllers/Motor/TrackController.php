<?php

namespace App\Http\Controllers\Motor;

use App\Http\Controllers\Controller;
use App\Insurers\Data\MotorQuoteInput;
use App\Models\Order;
use App\Models\QuoteRequest;
use App\Services\MotorOrders;
use App\Support\JourneyAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * "Track my order" (no customer accounts yet): order number + the email or mobile used for the order
 * opens the order for this browser session. Signed links in emails do the same without the form.
 */
class TrackController extends Controller
{
    protected const MAX_ATTEMPTS = 5;

    public function show(Request $request)
    {
        return view('site.motor.track', ['ref' => $request->query('ref')]);
    }

    public function lookup(Request $request)
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:40'],
            'contact' => ['required', 'string', 'max:150'],
        ], ['reference.required' => 'Please enter your order number.', 'contact.required' => 'Please enter your email or mobile.']);

        $ref = Str::upper(trim($data['reference']));
        // Per IP and per order number, so a reference cannot be guessed from many addresses either.
        $keys = ['track:ip:'.$request->ip(), 'track:ref:'.$ref];
        foreach ($keys as $key) {
            if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
                throw ValidationException::withMessages(['reference' => 'Too many attempts. Please try again in '.ceil(RateLimiter::availableIn($key) / 60).' minutes or call us on '.config('site.phone').'.']);
            }
        }

        $order = Order::with('customer')->where('reference', $ref)->first();
        if (! $order || ! $this->matches($order, $data['contact'])) {
            foreach ($keys as $key) {
                RateLimiter::hit($key, 15 * 60);
            }
            Log::notice('Track my order: no match', ['ref' => $ref, 'ip' => $request->ip()]);

            // Same message whether the order exists or not.
            throw ValidationException::withMessages(['reference' => 'We couldn’t find an order with these details. Please check the order number and the email or mobile you used.']);
        }

        RateLimiter::clear('track:ref:'.$ref);
        JourneyAccess::grantOrder($order);

        return redirect()->route('motor.confirmation', $order);
    }

    /** Email (case-insensitive) or UAE mobile in any common format. */
    protected function matches(Order $order, string $contact): bool
    {
        $contact = trim($contact);
        $customer = $order->customer;
        if (! $customer) {
            return false;
        }
        if (str_contains($contact, '@')) {
            return hash_equals(Str::lower((string) $customer->email), Str::lower($contact));
        }
        $digits = preg_replace('/\D/', '', $contact);

        return strlen($digits) >= 9 && hash_equals((string) $customer->mobile, MotorQuoteInput::mobile($digits));
    }

    /** Signed email link: open the order in this browser, then go to the page. */
    public function openOrder(Request $request, Order $order, string $to)
    {
        if (! $request->hasValidSignature()) {
            return $this->expired($order->reference);
        }
        JourneyAccess::grantOrder($order);

        return match ($to) {
            'payment' => redirect()->route('motor.payment', $order),
            'document' => redirect()->route('motor.policy.document', $order),
            default => redirect()->route('motor.confirmation', $order),
        };
    }

    /** Signed email link: open the quote results in this browser. */
    public function openQuote(Request $request, QuoteRequest $quoteRequest)
    {
        if (! $request->hasValidSignature()) {
            return redirect()->route('motor.quote')->with('notice', 'This link has expired. Offers are valid for 7 days — please request a new quote.');
        }
        JourneyAccess::grantQuote($quoteRequest);

        return redirect()->route('motor.results', $quoteRequest);
    }

    protected function expired(string $reference)
    {
        return redirect()->route('track', ['ref' => $reference])
            ->with('notice', 'This link has expired. Enter your order number and the email or mobile you used to open your order.');
    }

    /** Policy schedule stored from the insurer (private disk), for the customer who opened the order. */
    public function document(Order $order)
    {
        $order = JourneyAccess::order($order);
        $doc = $order->documents()->where('type', 'policy_schedule')->latest('id')->first();
        abort_unless($order->state === 'POLICY_ISSUED' && $doc && Storage::disk($doc->disk)->exists($doc->path), 404);

        // Type from the content itself (insurer file names are not reliable: DIN names its PDF ".html").
        [$ext, $mime] = MotorOrders::documentType((string) Storage::disk($doc->disk)->get($doc->path));
        $name = 'Policy-'.Str::slug($order->policy?->policy_no ?: $order->reference).'.'.$ext;

        // Always a download (never rendered on our domain): insurer content is not trusted.
        return Storage::disk($doc->disk)->download($doc->path, $name, ['Content-Type' => $mime, 'X-Content-Type-Options' => 'nosniff']);
    }
}
