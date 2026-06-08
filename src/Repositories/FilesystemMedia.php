<?php

namespace Whitecube\Media\Repositories;

use Whitecube\Media\MediaFile;
use Whitecube\Media\Generators\Variant;
use Whitecube\Media\Generators\Output;
use Whitecube\Media\Repositories\MediaInterface;
use Whitecube\Media\Repositories\MediaRepository;

class FilesystemMedia implements MediaInterface
{
    public function __construct(
        protected string $key,
        public readonly string $path,
        protected MediaRepository $repository,
    ) {}

    public function key(): int|string
    {
        return $this->key;
    }

    public function original(): MediaFile
    {
        return new MediaFile(
            key: MediaFile::KEY_ORIGINAL,
            path: $this->path,
            disk: $this->repository->getDisk($this),
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
                $stack[] = new MediaFile(
                    key: $generator->key(),
                    path: $media->path,
                    disk: $this->repository->getDisk($this),
                );
            }

            return $stack;
        }, []);
    }

    public function getGeneratorOutputConfig(Variant $generator): Output
    {
        // The filesystem does not store custom variant transformation data.
        // We'll return the generator's original output configuration.
        
        $output = $generator->output()
            ->disk($this->repository->getDisk($this))
            ->path($this->repository->getPath($this));

        return Output::config(
            output: $output,
            original: $this->original(),
            key: $generator->key(),
        );
    }
}
