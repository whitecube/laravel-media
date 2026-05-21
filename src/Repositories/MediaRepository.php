<?php

namespace Whitecube\Media\Repositories;

use Whitecube\Media\Repositories\MediaInterface;

interface MediaRepository
{
    public function find(int|string $key): ?MediaInterface;
}
