<?php

namespace Whitecube\Media;

class Variant
{
    const KEY_ORIGINAL = 'original';
    
    public function __construct(
        public readonly string $key,
        public readonly string $path,
        public readonly string $url,
    ) {}
}
