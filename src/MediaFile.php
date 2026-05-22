<?php

namespace Whitecube\Media;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;

class MediaFile
{
    const KEY_ORIGINAL = 'original';
    
    public function __construct(
        public readonly string $key,
        public readonly string $path,
        public readonly Filesystem $disk,
    ) {}

    public function fullPath(): string
    {
        return $this->disk->path($this->path);
    }

    public function url(): ?string
    {
        return $this->disk->url($this->path);
    }

    public function __get(string $attribute): mixed
    {
        if (! method_exists($this, $attribute)) {
            return null;
        }

        return $this->$attribute();
    }

    public function __serialize(): array
    {
        return [
            'key' => $this->key,
            'path' => $this->path,
            'disk' => $this->disk->getConfig(),
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->key = $data['key'];
        $this->path = $data['path'];
        $this->disk = app(Factory::class)->build($data['disk']);
    }
}
