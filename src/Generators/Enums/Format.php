<?php

namespace Whitecube\Media\Generators\Enums;

enum Format: string
{
    case Webp = 'webp';

    public function extension(): string
    {
        return match ($this) {
            default => $this->value,
        };
    }
}
