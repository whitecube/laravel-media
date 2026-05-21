<?php

namespace Whitecube\Media\Generators;

use Whitecube\Media\Generators\Output;

interface Variant
{
    public function key(): string;
    public function output(): Output;
}
