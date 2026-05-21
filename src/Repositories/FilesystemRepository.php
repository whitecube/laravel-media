<?php

namespace Whitecube\Media\Repositories;

use Whitecube\Media\Repositories\MediaInterface;
use Whitecube\Media\Repositories\MediaRepository;
use Illuminate\Contracts\Filesystem\Factory;

class FilesystemRepository implements MediaRepository
{
    public function __construct(
        protected Factory $storage,
    ) {}

    public function find(int|string $key): ?MediaInterface
    {
        $disk = $this->storage->disk('public');

        if (! $disk->exists($key)) {
            return null;
        }

        $media = new FilesystemMedia(
            key: $key,
            path: $disk->path($key),
            url: $disk->url($key),
            repository: $this,
        );

        return $media;
    }
}
