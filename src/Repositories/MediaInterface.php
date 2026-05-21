<?php

namespace Whitecube\Media\Repositories;

use Whitecube\Media\Variant;

interface MediaInterface
{
    public function key(): int|string;
    public function original(): Variant;
    public function variants(?array $classnames = null): array;
}
