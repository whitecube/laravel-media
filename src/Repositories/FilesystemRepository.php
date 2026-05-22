<?php

namespace Whitecube\Media\Repositories;

use Whitecube\Media\MediaManager;
use Whitecube\Media\Attributes\Image;
use Whitecube\Media\Repositories\MediaInterface;
use Whitecube\Media\Repositories\MediaRepository;
use Illuminate\Contracts\Filesystem\Filesystem;

class FilesystemRepository implements MediaRepository
{
    public function __construct(
        protected Filesystem $disk,
        protected ?string $directory,
    ) {}

    static public function make(?Image $mutator): static
    {
        return new static(
            disk: app(MediaManager::class)->getDiskInstance($mutator),
            directory: $mutator?->getDirectory(),
        );
    }

    public function find(int|string $key): ?MediaInterface
    {
        $path = $this->directory ? $this->directory.'/'.ltrim($key,'/') : $key;

        if (! $this->disk->exists($path)) {
            return null;
        }

        $media = new FilesystemMedia(
            key: $key,
            path: $path,
            repository: $this,
        );

        return $media;
    }

    public function getDisk(?MediaInterface $media = null): null|string|Filesystem
    {
        return $this->disk;
    }

    public function getDirectory(?MediaInterface $media = null): ?string
    {
        return $this->directory;
    }
}
