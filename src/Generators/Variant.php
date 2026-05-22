<?php

namespace Whitecube\Media\Generators;

use Whitecube\Media\MediaFile;
use Whitecube\Media\Generators\Output;

interface Variant
{
    public function key(): string;
    public function output(): Output;
    public function generate(Output $output): MediaFile;
}
