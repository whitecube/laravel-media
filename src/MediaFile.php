<?php

namespace Whitecube\Media;

use Whitecube\Media\References\FilesystemReference;

class MediaFile
{
    const KEY_ORIGINAL = 'original';
    
    public function __construct(
        public readonly string $key,
        public readonly string $path,
        public readonly FilesystemReference $disk,
    ) {}

    public function fullPath(): string
    {
        return $this->disk->resolve()->path($this->path);
    }

    public function url(): ?string
    {
        return $this->disk->resolve()->url($this->path);
    }

    public function __get(string $attribute): mixed
    {
        if (! method_exists($this, $attribute)) {
            return null;
        }

        return $this->$attribute();
    }
}
