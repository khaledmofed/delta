<?php

namespace App\Insurers\Data;

/** A customer document as sent to insurers. Content is loaded lazily. */
final class DocumentFile
{
    /** @param \Closure():string|null $contentLoader returns raw bytes */
    public function __construct(
        public readonly string $type,        // eid_front, eid_back, licence_front, licence_back, mulkiya_front, mulkiya_back, bank_lpo
        public readonly string $fileName,
        public readonly ?\Closure $contentLoader = null,
        public readonly ?string $base64 = null,
    ) {}

    public static function fromArray(array $a): self
    {
        return new self($a['type'], $a['file_name'], $a['loader'] ?? null, $a['base64'] ?? null);
    }

    public function base64(): string
    {
        if ($this->base64 !== null) {
            return $this->base64;
        }

        return base64_encode(($this->contentLoader)());
    }

    public function extension(): string
    {
        $ext = strtolower(pathinfo($this->fileName, PATHINFO_EXTENSION));

        return $ext === 'jpeg' ? 'jpg' : ($ext ?: 'png');
    }
}
