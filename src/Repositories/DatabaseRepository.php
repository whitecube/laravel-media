<?php

namespace Whitecube\Media\Repositories;

use Whitecube\Media\Repositories\MediaInterface;
use Whitecube\Media\Repositories\MediaRepository;

class DatabaseRepository implements MediaRepository
{
    public function find(int|string $key): ?MediaInterface
    {
        return null;
    }
}
