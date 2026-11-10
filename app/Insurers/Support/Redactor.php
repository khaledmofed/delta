<?php

namespace App\Insurers\Support;

/** Removes secrets / large blobs before anything is logged. */
final class Redactor
{
    public const SECRET_KEYS = ['password', 'authorization', 'api-key', 'api_key', 'apikey'];

    public const BLOB_KEYS = ['base64', 'doccontent', 'documentcontent', 'documents'];

    public static function redact(mixed $value, ?string $key = null): mixed
    {
        $k = strtolower((string) $key);
        if (in_array($k, self::SECRET_KEYS, true)) {
            return '***';
        }
        if (in_array($k, self::BLOB_KEYS, true) && is_string($value)) {
            return '<'.strlen($value).' chars>';
        }
        if ($k === 'data' && is_string($value) && strlen($value) > 120) {
            return '<token/blob '.strlen($value).' chars>';   // DIN returns the JWT in `data`
        }
        if (is_array($value)) {
            $out = [];
            foreach ($value as $kk => $vv) {
                $out[$kk] = self::redact($vv, is_string($kk) ? $kk : $key);
            }

            return $out;
        }

        return $value;
    }
}
