<?php

namespace Whitecube\Media;

class File extends Media
{
    public function url(?string $variant = null): ?string
    {
        return $this->src($variant);
    }
}
