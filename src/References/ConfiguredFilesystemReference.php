<?php

namespace Whitecube\Media\References;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;

class ConfiguredFilesystemReference extends FilesystemReference
{
    public function __construct(
        public readonly array $config,
    ) {}

    protected function resolveReference(): Filesystem
    {
        return app(Factory::class)->build($this->config);
    }
}
