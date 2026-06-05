<?php

namespace Whitecube\Media\Repositories;

use Whitecube\Media\Attributes\Image;
use Whitecube\Media\References\FilesystemReference;
use Whitecube\Media\Repositories\MediaInterface;
use Whitecube\Media\Repositories\MediaRepository;

class DatabaseRepository implements MediaRepository
{
    static public function make(?Image $mutator): static
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

    public function getDirectory(?MediaInterface $media = null): ?string
    {
        return null;
    }
}
