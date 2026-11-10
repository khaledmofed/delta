<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/** Customer documents live on a private disk; never in /public. */
class Document extends Model
{
    protected $fillable = ['type', 'insurer_code', 'disk', 'path', 'original_name', 'mime', 'size', 'status'];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function base64(): string
    {
        return base64_encode(Storage::disk($this->disk)->get($this->path));
    }

    public function extension(): string
    {
        return strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION) ?: 'png');
    }
}
