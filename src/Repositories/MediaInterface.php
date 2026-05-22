<?php

namespace Whitecube\Media\Repositories;

use Whitecube\Media\MediaFile;
use Whitecube\Media\Generators\Variant;
use Whitecube\Media\Generators\Output;

interface MediaInterface
{
    public function key(): int|string;
    public function original(): MediaFile;
    public function variants(?array $classnames = null): array;
    public function getGeneratorOutputConfig(Variant $generator): Output;
}
