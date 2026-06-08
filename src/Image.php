<?php

namespace Whitecube\Media;

class Image extends Media
{
    public function srcset(): ?string
    {
        // TODO.
        return null;
    }

    public function sizes(): ?string
    {
        // TODO.
        return null;
    }

    public function alt(?string $fallback = null): ?string
    {
        // TODO : get the source alt.
        return $fallback;
    }
}
