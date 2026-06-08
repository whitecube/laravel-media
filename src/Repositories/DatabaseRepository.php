<?php

namespace Whitecube\Media\Repositories;

use Whitecube\Media\Attributes\MediaMutator;
use Whitecube\Media\References\FilesystemReference;
use Whitecube\Media\Repositories\MediaInterface;
use Whitecube\Media\Repositories\MediaRepository;

class DatabaseRepository implements MediaRepository
{
    static public function make(?MediaMutator $mutator): static
    {
        return new static();
    }

    public function find(int|string $key): ?MediaInterface
    {
        return null;
    }

    public function getDisk(?MediaInterface $media = null): ?FilesystemReference
    {
        return null;
    }

    public function getPath(?MediaInterface $media = null): ?string
    {
        return null;
    }
}
