<?php

namespace Whitecube\Media\Repositories;

use Whitecube\Media\Attributes\MediaMutator;
use Whitecube\Media\References\FilesystemReference;
use Whitecube\Media\Repositories\MediaInterface;

interface MediaRepository
{
    static public function make(?MediaMutator $mutator): static;
    public function find(int|string $key): ?MediaInterface;
    public function getDisk(?MediaInterface $media = null): ?FilesystemReference;
    public function getPath(?MediaInterface $media = null): ?string;
}
