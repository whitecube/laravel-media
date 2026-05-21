<?php

namespace Whitecube\Media\Repositories;

use Whitecube\Media\Variant;
use Whitecube\Media\Repositories\MediaInterface;
use Whitecube\Media\Repositories\MediaRepository;

class FilesystemMedia implements MediaInterface
{
    public function __construct(
        protected string $key,
        public readonly string $path,
        public readonly string $url,
        protected MediaRepository $repository,
    ) {}

    public function key(): int|string
    {
        return $this->key;
    }

    public function original(): Variant
    {
        return new Variant(
            key: Variant::KEY_ORIGINAL,
            path: $this->path,
            url: $this->url,
        );
    }

    public function variants(?array $classnames = null): array
    {
        if (! $classnames) {
            return [];
        }

        return array_reduce($classnames, function(array $stack, string $classname) {
            $generator = app($classname);
            $output = $generator->output();
            $key = str_replace(basename($this->key), $output->getFile($this->original(), true), $this->key);
            
            if ($media = $this->repository->find($key)) {
                $stack[] = new Variant(
                    key: $generator->key(),
                    path: $media->path,
                    url: $media->url,
                );
            }

            return $stack;
        }, []);
    }
}
