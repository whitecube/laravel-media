<?php

namespace Whitecube\Media\Repositories;

use Whitecube\Media\MediaManager;
use Whitecube\Media\Attributes\MediaMutator;
use Whitecube\Media\References\FilesystemReference;
use Whitecube\Media\Repositories\MediaInterface;
use Whitecube\Media\Repositories\MediaRepository;

class FilesystemRepository implements MediaRepository
{
    public function __construct(
        protected FilesystemReference $disk,
        protected ?string $path,
    ) {}

    static public function make(?MediaMutator $mutator): static
    {
        return new static(
            disk: app(MediaManager::class)->getDiskReference($mutator),
            path: $mutator?->getPath(),
        );
    }

    public function find(int|string $key): ?MediaInterface
    {
        $path = $this->path ? $this->path.'/'.ltrim($key,'/') : $key;
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

    public function getPath(?MediaInterface $media = null): ?string
    {
        return $this->path;
    }
}
