<?php

namespace Whitecube\Media\Repositories;

use Whitecube\Media\Attributes\Image;
use Whitecube\Media\References\FilesystemReference;
use Whitecube\Media\Repositories\MediaInterface;

interface MediaRepository
{
    static public function make(?Image $mutator): static;
    public function find(int|string $key): ?MediaInterface;
    public function getDisk(?MediaInterface $media = null): ?FilesystemReference;
    public function getDirectory(?MediaInterface $media = null): ?string;
}
