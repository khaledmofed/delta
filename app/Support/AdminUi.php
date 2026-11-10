<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

/** Admin view helpers — same status tones and labels as app-common.js / delta-data.js in the prototype. */
final class AdminUi
{
    public const TONES = [
        'POLICY_ISSUED' => 'ok', 'PAYMENT_SUCCESS' => 'ok', 'QUOTES_RECEIVED' => 'info', 'QUOTE_SELECTED' => 'info', 'QUOTE_REQUESTED' => 'info', 'QUOTING' => 'info',
        'CUSTOMER_DETAILS_PENDING' => 'warn', 'DOCUMENTS_PENDING' => 'warn', 'PAYMENT_PENDING' => 'warn', 'POLICY_REQUESTED' => 'info', 'INSURANCE_PROCESSING' => 'info',
        'PAYMENT_FAILED' => 'bad', 'DOCUMENTS_REJECTED' => 'bad', 'QUOTE_EXPIRED' => 'mute', 'INSURANCE_REJECTED' => 'bad', 'POLICY_FAILED' => 'bad', 'CANCELLED' => 'mute', 'REFUNDED' => 'mute', 'NO_QUOTES' => 'bad',
        'active' => 'ok', 'success' => 'ok', 'issued' => 'ok', 'received' => 'ok', 'won' => 'ok', 'healthy' => 'ok', 'uploaded' => 'ok',
        'pending' => 'warn', 'new' => 'info', 'contacted' => 'warn', 'quoted' => 'info', 'degraded' => 'warn', 'sample' => 'warn',
        'failed' => 'bad', 'error' => 'bad', 'exception' => 'bad', 'down' => 'bad', 'lost' => 'mute', 'not_connected' => 'mute', 'off' => 'mute', 'expired' => 'mute', 'no_traffic' => 'mute',
    ];

    public const LABELS = [
        'QUOTE_REQUESTED' => 'Quote requested', 'QUOTING' => 'Quoting', 'QUOTES_RECEIVED' => 'Quotes received', 'NO_QUOTES' => 'No quotes',
        'QUOTE_SELECTED' => 'Quote selected', 'CUSTOMER_DETAILS_PENDING' => 'Customer details pending', 'DOCUMENTS_PENDING' => 'Documents pending',
        'PAYMENT_PENDING' => 'Payment pending', 'PAYMENT_SUCCESS' => 'Payment successful', 'POLICY_REQUESTED' => 'Policy requested',
        'INSURANCE_PROCESSING' => 'Insurer processing', 'POLICY_ISSUED' => 'Policy issued',
    ];

    public static function label(?string $s): string
    {
        if (! $s) {
            return '—';
        }

        return self::LABELS[$s] ?? ucfirst(strtolower(str_replace('_', ' ', $s)));
    }

    public static function pill(?string $s, ?string $text = null): HtmlString
    {
        return new HtmlString('<span class="pill '.(self::TONES[$s] ?? 'mute').'">'.e($text ?? self::label($s)).'</span>');
    }

    /** "12 Nov 2026, 14:05" — DELTA.util.fmtDate(d, true) */
    public static function dt(mixed $d): string
    {
        return $d ? Carbon::parse($d)->timezone(config('app.timezone'))->format('d M Y, H:i') : '—';
    }

    public static function money(float|string|null $v): string
    {
        return 'AED '.number_format((float) $v, 2);
    }

    public static function insurerName(string $code): string
    {
        return config("insurers.providers.{$code}.name", strtoupper($code));
    }

    /** Insurer logo + name ("who" block) — A.prov() in app-common.js. */
    public static function who(string $code): HtmlString
    {
        $name = e(self::insurerName($code));
        $src = e(asset("images/insurers/{$code}.png"));

        return new HtmlString('<span class="who"><span class="ins-logo" title="'.$name.'"><img src="'.$src.'" alt="'.$name.'" data-pid="'.e($code).'" onerror="DELTA.util&&DELTA.util.logoFallback(this)" /></span><span>'.$name.'</span></span>');
    }

    public static function logo(string $code, bool $large = false): HtmlString
    {
        $name = e(self::insurerName($code));

        return new HtmlString('<span class="ins-logo'.($large ? ' ins-logo--lg' : '').'" title="'.$name.'"><img src="'.e(asset("images/insurers/{$code}.png")).'" alt="'.$name.'" data-pid="'.e($code).'" onerror="DELTA.util&&DELTA.util.logoFallback(this)" /></span>');
    }

    /** Masked Emirates ID for staff screens (784-1986-•••••••-1). */
    public static function maskEid(?string $eid): string
    {
        $eid = preg_replace('/\D/', '', (string) $eid);

        return strlen($eid) === 15 ? substr($eid, 0, 3).'-'.substr($eid, 3, 4).'-•••••••-'.substr($eid, -1) : '—';
    }

    public static function json(mixed $v): string
    {
        return json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
    }
}
