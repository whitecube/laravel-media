<?php

namespace Whitecube\Media\Repositories;

use Whitecube\Media\Attributes\Image;
use Whitecube\Media\Repositories\MediaInterface;
use Illuminate\Contracts\Filesystem\Filesystem;

interface MediaRepository
{
    static public function make(?Image $mutator): static;
    public function find(int|string $key): ?MediaInterface;
    public function getDisk(?MediaInterface $media = null): null|string|Filesystem;
    public function getDirectory(?MediaInterface $media = null): ?string;
}
