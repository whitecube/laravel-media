<?php

namespace Whitecube\Media\Generators;

use Whitecube\Media\Repositories\MediaInterface;

interface ConditionalVariant
{
    public function shouldApply(MediaInterface $media): bool;
}
