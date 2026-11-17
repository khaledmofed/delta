<?php

namespace App\Support;

/**
 * Asset URL with a version (file modification time), so browsers load the new CSS/JS right after a deploy
 * instead of a cached copy: asset('assets/css/site.css') -> .../assets/css/site.css?v=1763380000
 */
final class Asset
{
    public static function v(string $path): string
    {
        $file = public_path($path);

        return asset($path).(is_file($file) ? '?v='.filemtime($file) : '');
    }
}
