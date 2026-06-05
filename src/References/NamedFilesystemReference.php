<?php

namespace Whitecube\Media\References;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;

class NamedFilesystemReference extends FilesystemReference
{
    public function __construct(
        public readonly string $disk,
    ) {}

    protected function resolveReference(): Filesystem
    {
        return app(Factory::class)->disk($this->disk);
    }
}
