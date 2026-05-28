<?php

namespace Whitecube\Media;

use ReflectionMethod;
use Whitecube\Media\Image;
use Whitecube\Media\Attributes\Image as ImageAttribute;
use Whitecube\Media\Contracts\HasMediaAttributes;
use Whitecube\Media\Repositories\MediaInterface;
use Whitecube\Media\Repositories\MediaRepository;
use Whitecube\Media\Jobs\GenerateMediaVariant;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MediaManager
{
    protected array $register = [];
    protected array $observed = [];

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

    public function getRegisteredModelMediaMutators(string|Model|HasMediaAttributes $model): array
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

    public function getModelMediaMutator(string|Model|HasMediaAttributes $model, string $attribute): ?ImageAttribute
    {
        $classname = is_string($model) ? $model : get_class($model);
        $model = is_string($model) ? new $model : $model;

        if (is_a($model, HasMediaAttributes::class)) {
            return $model->getMediaAttribute($attribute);
        }

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

        return Image::make(
            source: $this->getMedia($key, $repository),
            attribute: $attribute,
        );
    }

    public function storeImage(mixed $value, ImageAttribute $attribute): null|int|string
    {
        if (is_a($value, Image::class)) {
            $this->observeSavedEvent($value->key, $attribute);
            return $value->key;
        }

        if (! is_string($value) || ! strlen($value)) {
            return null;
        }

        // TODO TMP : this is only enough for FilesystemRepository
        if (($directory = trim($attribute->getDirectory(),'/')) && strpos(ltrim($value,'/'), $directory) === 0) {
            $value = ltrim(substr(ltrim($value,'/'), strlen($directory)),'/');
        }

        $this->observeSavedEvent($value, $attribute);
        
        return $value;
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

    public function observeSavedEvent(null|int|string $key, ImageAttribute $mutator): void
    {
        if (is_null($key)) {
            return;
        }

        $model = $mutator->getModel();
        $attribute = $mutator->getAttribute();
        $classname = get_class($model);

        if (! isset($this->observed[$classname])) {
            $this->observed[$classname] = [];
        }

        if (! isset($this->observed[$classname][$attribute])) {
            $this->observed[$classname][$attribute] = [];
        }

        if (in_array($key, $this->observed[$classname][$attribute])) {
            return;
        }

        $classname::saved(fn (Model $model) => $this->runSavedEvent($model, $attribute));

        $this->observed[$classname][$attribute][] = $key;
    }

    public function runSavedEvent(Model $model, string $attribute): void
    {
        $classname = get_class($model);
        $key = $model->getRawOriginal($attribute);

        if (is_null($key) || (is_string($key) && ! strlen($key)) || ! in_array($key, $this->observed[$classname][$attribute] ?? [])) {
            return;
        }

        $mutator = $this->getModelMediaMutator($model, $attribute);

        if (! $mutator) {
            unset($this->observed[$classname][$attribute]);
            return;
        }

        foreach ($mutator->getVariants() as $generator) {
            GenerateMediaVariant::dispatch($model, $attribute, $generator);
        }

        $this->unobserve($classname, $attribute, $key);
    }

    protected function unobserve(string $classname, string $attribute, int|string $key): void
    {
        $index = array_search($key, $this->observed[$classname][$attribute] ?? []);

        if ($index === false) {
            return;
        }

        unset($this->observed[$classname][$attribute][$index]);

        if (! $this->observed[$classname][$attribute]) {
            unset($this->observed[$classname][$attribute]);
        }

        if (! $this->observed[$classname]) {
            unset($this->observed[$classname]);
        }
    }
}
