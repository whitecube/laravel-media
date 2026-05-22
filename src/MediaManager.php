<?php

namespace Whitecube\Media;

use ReflectionMethod;
use Whitecube\Media\Image;
use Whitecube\Media\Attributes\Image as ImageAttribute;
use Whitecube\Media\Repositories\MediaInterface;
use Whitecube\Media\Repositories\MediaRepository;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MediaManager
{
    protected array $register = [];

    public function registerModelAttributes(string $classname, array $attributes): self
    {
        if (! array_key_exists($classname, $this->register)) {
            $this->register[$classname] = [];
        }

        $this->register[$classname] = [
            ...$this->register[$classname], ...$attributes,
        ];

        return $this;
    }

    public function getRegisteredModels(): array
    {
        return array_keys($this->register);
    }

    public function getRegisteredModelMediaMutators(string|Model $model): array
    {
        $classname = is_string($model) ? $model : get_class($model);

        if (! ($attributes = $this->register[$classname] ?? null)) {
            return [];
        }

        return array_reduce($attributes, function(array $stack, string $attribute) use ($classname) {
            if ($mutator = $this->getModelMediaMutator($classname, $attribute)) {
                $stack[$attribute] = $mutator;
            }
            return $stack;
        }, []);
    }

    public function getModelMediaMutator(string|Model $model, string $attribute): ?ImageAttribute
    {
        $classname = is_string($model) ? $model : get_class($model);
        $model = is_string($model) ? new $model : $model;

        try {
            $method = new ReflectionMethod($classname, Str::camel($attribute));
            return $method->invoke($model);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function makeImage(null|int|string $key, ImageAttribute $attribute): Image
    {
        $repository = $this->getRepository($attribute);

        // $disk = $this->getDiskInstance($attribute, $repository);
        // $directory = $this->getDirectory($attribute, $repository);

        return Image::make(
            source: $this->getMedia($key, $repository),
            attribute: $attribute,
        );
    }

    public function getMedia(null|int|string $key, MediaRepository $repository): ?MediaInterface
    {
        if (is_null($key) || (is_string($key) && ! strlen($key))) {
            return null;
        }

        return $repository->find($key);
    }

    public function getRepository(?ImageAttribute $attribute): MediaRepository
    {
        $repository = $attribute?->getRepository() ?? $this->getDefaultRepository();

        if (is_a($repository, MediaRepository::class)) {
            return $repository;
        }

        return $repository::make($attribute);
    }

    protected function getDefaultRepository(): string
    {
        return MediaRepository::class;
    }

    public function getDiskInstance(?ImageAttribute $attribute = null, ?MediaRepository $repository = null): Filesystem
    {
        $disk = $attribute?->getDisk()
            ?? $repository?->getDisk()
            ?? config('filesystems.default');

        if (! is_a($disk, Filesystem::class)) {
            $disk = app(Factory::class)->disk($disk);
        }

        return $disk;
    }
}
