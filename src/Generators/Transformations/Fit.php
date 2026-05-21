<?php

namespace Whitecube\Media\Generators\Transformations;

use Whitecube\Media\Generators\Enums\CropPosition;

class Fit extends CropTransformation
{
    public function __construct(
        protected int $width,
        protected int $height,
        protected CropPosition $position,
    ) {}
}
