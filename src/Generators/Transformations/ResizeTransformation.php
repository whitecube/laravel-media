<?php

namespace Whitecube\Media\Generators\Transformations;

abstract class ResizeTransformation
{
    abstract public function apply(mixed $image): mixed;
}
