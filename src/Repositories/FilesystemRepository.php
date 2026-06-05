<?php

namespace Whitecube\Media\Repositories;

use Whitecube\Media\MediaManager;
use Whitecube\Media\Attributes\Image;
use Whitecube\Media\References\FilesystemReference;
use Whitecube\Media\Repositories\MediaInterface;
use Whitecube\Media\Repositories\MediaRepository;

class FilesystemRepository implements MediaRepository
{
    public function __construct(
        protected FilesystemReference $disk,
        protected ?string $directory,
    ) {}

    static public function make(?Image $mutator): static
    {
        return new static(
            disk: app(MediaManager::class)->getDiskReference($mutator),
            directory: $mutator?->getDirectory(),
        );
    }

    public function find(int|string $key): ?MediaInterface
    {
        $path = $this->directory ? $this->directory.'/'.ltrim($key,'/') : $key;
        $disk = $this->disk->resolve();

        if (! $disk->exists($path)) {
            return null;
        }

        $media = new FilesystemMedia(
            key: $key,
            path: $path,
            repository: $this,
        );

        return $media;
    }

    public function getDisk(?MediaInterface $media = null): ?FilesystemReference
    {
        return $this->disk;
    }

    public function getDirectory(?MediaInterface $media = null): ?string
    {
        return $this->directory;
    }
}
