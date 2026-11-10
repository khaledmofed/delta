<?php

namespace App\Services;

use App\Insurers\Data\CheckoutResult;
use App\Insurers\Data\PolicyStatus;
use App\Insurers\Din\DinErrors;
use App\Insurers\InsurerManager;
use App\Models\Insurer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Policy;
use App\Models\Quote;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Order lifecycle after the customer picks an offer:
 * select() -> pay() (adapter->checkout, customer goes to the insurer payment page) -> refresh() (adapter->policyStatus).
 * The payment result is only ever taken from the insurer, never from the return URL.
 */
class MotorOrders
{
    /** Order states in which the insurer may still change the outcome. */
    public const OPEN_STATES = ['PAYMENT_PENDING', 'PAYMENT_SUCCESS', 'POLICY_REQUESTED', 'INSURANCE_PROCESSING'];

    /** Wizard add-on => pattern matched against the insurer's optional cover names. */
    public const ADDON_PATTERNS = [
        'roadside' => '/road\s*side|roadside|breakdown|assistance/i',
        'rentACar' => '/rent|replacement|hire car/i',
        'pa' => '/personal accident|\bP\.?A\.?\b/i',
        'gcc' => '/\bgcc\b/i',
    ];

    public function __construct(protected InsurerManager $insurers, protected MotorQuotes $quotes, protected JourneyMailer $mailer) {}

    public function select(Quote $quote): Order
    {
        $qr = $quote->quoteRequest;
        $covers = $this->matchAddons($quote, $qr->input['addons'] ?? []);
        [$premium, $vat, $total] = $this->totals($quote, $covers);

        return DB::transaction(function () use ($quote, $qr, $covers, $premium, $vat, $total) {
            $order = Order::create([
                'quote_request_id' => $qr->id,
                'quote_id' => $quote->id,
                'customer_id' => $qr->customer_id,
                'insurer' => $quote->insurer,
                'state' => 'QUOTE_SELECTED',
                'selected_covers' => $covers,
                'premium' => $premium,
                'vat' => $vat,
                'total' => $total,
                'commission_rate' => (float) (Insurer::where('code', $quote->insurer)->value('commission_rate') ?? 0),
                'request_id' => $quote->raw['request_id'] ?? ($qr->insurer_results[$quote->insurer]['request_id'] ?? null),
                'policy_start' => $qr->input['policy_start'] ?? null,
            ]);
            $order->transition('QUOTE_SELECTED', 'Offer selected: '.$quote->plan_name, 'customer');

            return $order;
        });
    }

    /** Optional cover codes on this offer that match the add-ons ticked in the wizard. */
    public function matchAddons(Quote $quote, array $addons): array
    {
        $codes = [];
        foreach ($quote->covers ?? [] as $c) {
            if (($c['type'] ?? null) !== 'optional') {
                continue;
            }
            foreach ($addons as $a) {
                if (isset(self::ADDON_PATTERNS[$a]) && preg_match(self::ADDON_PATTERNS[$a], (string) $c['name'])) {
                    $codes[] = (string) $c['code'];
                    break;
                }
            }
        }

        return array_values(array_unique($codes));
    }

    /** @return array{0: float, 1: float, 2: float} premium, VAT, total for the offer plus the chosen optional covers */
    public function totals(Quote $quote, array $coverCodes): array
    {
        $extra = 0.0;
        foreach ($quote->covers ?? [] as $c) {
            if (($c['type'] ?? null) === 'optional' && in_array((string) $c['code'], $coverCodes, true)) {
                $extra += (float) $c['premium'];
            }
        }
        if ($extra <= 0) {
            return [(float) $quote->premium, (float) $quote->vat, (float) $quote->total];
        }
        $premium = round((float) $quote->premium + $extra, 2);
        $vat = round($premium * 0.05, 2);

        return [$premium, $vat, round($premium + $vat, 2)];
    }

    /** Selected optional covers as [{code,name,premium}] for display. */
    public function selectedCovers(Order $order): array
    {
        $codes = $order->selected_covers ?? [];

        return array_values(array_filter($order->quote->covers ?? [], fn ($c) => ($c['type'] ?? null) === 'optional' && in_array((string) $c['code'], $codes, true)));
    }

    /**
     * Start (or resume) payment: asks the insurer for its hosted payment page.
     * Returns the insurer CheckoutResult; on success the order is PAYMENT_PENDING.
     */
    public function pay(Order $order): CheckoutResult
    {
        $lastPayment = $order->payments()->latest('id')->first();
        if ($order->state === 'PAYMENT_PENDING' && $order->payment_url && $lastPayment?->status === 'pending') {
            return new CheckoutResult(true, paymentUrl: $order->payment_url, insurerQuoteNo: $order->insurer_quote_no);
        }

        try {
            $res = $this->insurers->get($order->insurer)->checkout(
                $this->quotes->input($order->quoteRequest),
                QuoteEngine::offerFromQuote($order->quote),
                $order->selected_covers ?? [],
                (string) $order->request_id,
            );
        } catch (\Throwable $e) {
            Log::error('Checkout failed', ['order' => $order->reference, 'e' => $e->getMessage()]);
            $res = new CheckoutResult(false, errorCode: 'EXCEPTION', errorMessage: $e->getMessage());
        }

        if (! $res->ok || ! $res->paymentUrl) {
            $reason = trim(($res->errorCode ?? '').' '.($res->errorMessage ?? 'no payment URL'));
            $order->transition($order->state, 'Checkout failed: '.$reason);
            $this->mailer->failure($order, 'Insurer refused checkout', $reason);

            return $res->ok ? new CheckoutResult(false, errorCode: 'NO_PAYMENT_URL', errorMessage: 'The insurer did not return a payment page.') : $res;
        }

        $order->forceFill(array_filter([
            'payment_url' => $res->paymentUrl,
            'insurer_quote_no' => $res->insurerQuoteNo,
            // DIN confirms the final price at ChooseScheme; keep the indicative one if none is returned.
            'premium' => $res->total ? $res->premium : null,
            'vat' => $res->total ? $res->vat : null,
            'total' => $res->total ?: null,
        ], fn ($v) => $v !== null))->save();

        Payment::create([
            'order_id' => $order->id,
            'attempt' => $order->payments()->count() + 1,
            'amount' => $order->total,
            'channel' => 'insurer_hosted',
            'status' => 'pending',
            'reference' => $res->insurerQuoteNo,
        ]);
        $order->transition('PAYMENT_PENDING', 'Sent to the insurer payment page');
        if ($order->payments()->count() === 1) {   // first time only, not on every retry
            $this->mailer->awaitingPayment($order);
        }

        return $res;
    }

    /** Customer-facing message for a failed checkout. */
    public function checkoutError(Order $order, CheckoutResult $res): string
    {
        $name = config("insurers.providers.{$order->insurer}.name", 'the insurer');

        return ($order->insurer === 'din' ? DinErrors::forCustomer($res->errorCode) : null)
            ?? "We couldn’t open the secure payment page of {$name}. Please try again in a moment or call us on ".config('site.phone').'.';
    }

    /**
     * Ask the insurer where this order stands and move the state machine.
     * $minInterval throttles calls when a page polls (seconds; 0 = always ask).
     */
    public function refresh(Order $order, int $minInterval = 0): Order
    {
        if (! in_array($order->state, self::OPEN_STATES, true) || ! $order->request_id) {
            return $order;
        }
        if ($minInterval > 0 && ! Cache::add("order:{$order->id}:policy-check", 1, $minInterval)) {
            return $order;
        }

        // The scheduler and a polling page can check the same order at once: only one may move it,
        // otherwise the policy (and its emails) would be processed twice.
        $lock = Cache::lock("order:{$order->id}:refresh", 60);
        if (! $lock->get()) {
            return $order;
        }
        try {
            $order->refresh();
            if (! in_array($order->state, self::OPEN_STATES, true)) {
                return $order;
            }

            return $this->applyInsurerStatus($order);
        } finally {
            $lock->release();
        }
    }

    protected function applyInsurerStatus(Order $order): Order
    {
        try {
            $status = $this->insurers->get($order->insurer)->policyStatus($order->request_id, [
                'awnic_quote_no' => $order->insurer_quote_no,
                'premium' => $order->premium,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Policy status check failed', ['order' => $order->reference, 'e' => $e->getMessage()]);

            return $order;
        }

        $payment = $order->payments()->latest('id')->first();

        if ($status->status === PolicyStatus::ISSUED) {
            DB::transaction(function () use ($order, $status, $payment) {
                $payment?->update(['status' => 'success', 'raw' => $status->raw]);
                if ($order->state === 'PAYMENT_PENDING') {
                    $order->transition('PAYMENT_SUCCESS', 'Payment confirmed by the insurer');
                }
                $this->issuePolicy($order, $status);
                $order->transition('POLICY_ISSUED', 'Policy '.$status->policyNo.' issued');
            });
            $this->mailer->policyIssued($order->fresh(['policy', 'customer', 'quote', 'quoteRequest']));
        } elseif ($status->paymentStatus === 'S') {
            if ($order->state === 'PAYMENT_PENDING') {
                $payment?->update(['status' => 'success', 'raw' => $status->raw]);
                $order->transition('PAYMENT_SUCCESS', 'Payment confirmed by the insurer');
                $order->transition('INSURANCE_PROCESSING', 'Waiting for the insurer to issue the policy');
            }
        } elseif ($status->paymentStatus === 'F') {
            // Only an explicit "payment failed" from the insurer fails the payment.
            $payment?->update(['status' => 'failed', 'raw' => $status->raw]);
            $order->transition('PAYMENT_FAILED', 'Payment declined at the insurer'.($status->errorCode ? " ({$status->errorCode})" : ''));
            $this->mailer->failure($order, 'Payment declined', trim(($status->errorCode ?? '').' '.($status->errorMessage ?? '')) ?: null);
        }

        return $order->fresh();
    }

    protected function issuePolicy(Order $order, PolicyStatus $status): Policy
    {
        $start = $order->policy_start ?? now()->startOfDay();
        $docs = [];
        foreach ($status->documents as $name => $content) {
            $docs[$name] = $this->storePolicyDocument($order, (string) $name, (string) $content);
        }

        return Policy::updateOrCreate(['order_id' => $order->id], [
            'insurer' => $order->insurer,
            'policy_no' => (string) $status->policyNo,
            'status' => 'issued',
            'starts_on' => $start,
            'ends_on' => $start->copy()->addYear()->subDay(),
            'sum_insured' => $order->quote->sum_insured,
            'total' => $status->total ?? $order->total,
            'documents' => $docs,
            'raw' => $status->raw,
        ]);
    }

    /** DIN returns document content; AWNIC returns document ids (kept as references). */
    protected function storePolicyDocument(Order $order, string $name, string $content): array
    {
        if (strlen($content) < 200) {
            return ['insurer_doc_id' => $content];
        }
        $decoded = base64_decode($content, true);
        $bytes = $decoded !== false ? $decoded : $content;
        $path = "orders/{$order->reference}/".Str::slug(pathinfo($name, PATHINFO_FILENAME)).'-'.Str::random(8).'.'.(pathinfo($name, PATHINFO_EXTENSION) ?: 'bin');
        Storage::disk(MotorQuotes::DISK)->put($path, $bytes);
        $order->documents()->create([
            'type' => 'policy_schedule', 'disk' => MotorQuotes::DISK, 'path' => $path,
            'original_name' => $name, 'mime' => 'application/octet-stream', 'size' => strlen($bytes), 'status' => 'received',
        ]);

        return ['path' => $path];
    }
}
