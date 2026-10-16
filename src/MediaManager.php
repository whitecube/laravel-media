<?php

namespace Whitecube\Media;

use ReflectionMethod;
use Whitecube\Media\File;
use Whitecube\Media\Image;
use Whitecube\Media\Attributes\File as FileAttribute;
use Whitecube\Media\Attributes\Image as ImageAttribute;
use Whitecube\Media\Attributes\MediaMutator;
use Whitecube\Media\Contracts\HasMediaAttributes;
use Whitecube\Media\Generators\Variant;
use Whitecube\Media\References\FilesystemReference;
use Whitecube\Media\Repositories\MediaInterface;
use Whitecube\Media\Repositories\MediaRepository;
use Whitecube\Media\Jobs\GenerateMediaVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MediaManager
{
    protected array $register = [];
    protected array $observed = [];
    protected array $listening = [];

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

    public function getModelMediaMutator(string|Model|HasMediaAttributes $model, string $attribute): ?MediaMutator
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

    public function makeFile(null|int|string $key, FileAttribute $mutator): File
    {
        return $this->makeMedia($key, $mutator, File::class);
    }

    public function makeImage(null|int|string $key, ImageAttribute $mutator): Image
    {
        return $this->makeMedia($key, $mutator, Image::class);
    }

    public function makeMedia(null|int|string $key, MediaMutator $mutator, string $classname): Media
    {
        $repository = $this->getRepository($mutator);

        return $classname::make(
            source: $this->getMediaData($key, $repository),
            attribute: $mutator,
        );
    }

    public function storeMedia(mixed $value, MediaMutator $mutator): null|int|string
    {
        if (is_a($value, File::class)) {
            $this->observeSavedEvent($value->key, $mutator);
            return $value->key;
        }

        if (! is_string($value) || ! strlen($value)) {
            return null;
        }

        // TODO TMP : this is only enough for FilesystemRepository
        if (($path = $mutator->getPath()) && strpos(ltrim($value,'/'), $path) === 0) {
            $value = ltrim(substr(ltrim($value,'/'), strlen($path)),'/');
        }

        $this->observeSavedEvent($value, $mutator);
        
        return $value;
    }

    public function getMediaData(null|int|string $key, MediaRepository $repository): ?MediaInterface
    {
        if (is_null($key) || (is_string($key) && ! strlen($key))) {
            return null;
        }

        return $repository->find($key);
    }

    public function getRepository(?MediaMutator $mutator): MediaRepository
    {
        $repository = $mutator?->getRepository() ?? $this->getDefaultRepository();

        if (is_a($repository, MediaRepository::class)) {
            return $repository;
        }

        return $repository::make($mutator);
    }

    protected function getDefaultRepository(): string
    {
        return MediaRepository::class;
    }

    public function getDiskReference(?MediaMutator $mutator = null, ?MediaRepository $repository = null): FilesystemReference
    {
        $disk = $mutator?->getDisk()
            ?? $repository?->getDisk()
            ?? config('filesystems.default');

        return FilesystemReference::of($disk);
    }

    public function observeSavedEvent(null|int|string $key, MediaMutator $mutator): void
    {
        if (is_null($key)) {
            return;
        }

        $model = $mutator->getModel();
        $attribute = $mutator->getAttribute();
        $classname = get_class($model);

        if (in_array($key, $this->observed[$classname][$attribute] ?? [])) {
            return;
        }

        $this->listenToSavedEvent($classname, $attribute);

        $this->observed[$classname][$attribute][] = $key;
    }

    /**
     * Register the model's "saved" listener for given attribute. It is only
     * registered once since it handles all the observed keys, which prevents
     * listeners from piling up in long-running processes (queue workers, Octane,...).
     */
    protected function listenToSavedEvent(string $classname, string $attribute): void
    {
        if (isset($this->listening[$classname][$attribute])) {
            return;
        }

        $classname::saved(fn (Model $model) => $this->runSavedEvent($model, $attribute));

        $this->listening[$classname][$attribute] = true;
    }

    public function runSavedEvent(Model|HasMediaAttributes $model, string $attribute): void
    {
        $classname = get_class($model);
        $key = $this->getMediaKey($model, $attribute);

        if (is_null($key) || (is_string($key) && ! strlen($key)) || ! in_array($key, $this->observed[$classname][$attribute] ?? [])) {
            return;
        }

        $this->unobserve($classname, $attribute, $key);

        $mutator = $this->getModelMediaMutator($model, $attribute);

        if (! $mutator) {
            return;
        }

        $generators = $this->hasChangedMedia($model, $attribute)
            ? $mutator->getVariants()
            : $this->getOutdatedVariants($key, $mutator);

        foreach ($generators as $generator) {
            GenerateMediaVariant::dispatch($model, $attribute, $generator);
        }
    }

    /**
     * Get the media key currently stored in the model's attribute.
     *
     * The model's current attributes are used instead of its "original" ones since
     * the "saved" event is fired before Eloquent syncs them: the original value is
     * still null for new models, or the previous key when the media was replaced.
     */
    public function getMediaKey(Model|HasMediaAttributes $model, string $attribute): null|int|string
    {
        if (is_a($model, HasMediaAttributes::class)) {
            return $model->getMediaKey($attribute);
        }

        return $model->getAttributes()[$attribute] ?? null;
    }

    /**
     * Check if the model's media has been assigned a new key in the save operation
     * that is being performed. Models implementing HasMediaAttributes may store
     * their medias elsewhere: in this case, the media is considered unchanged.
     */
    protected function hasChangedMedia(Model|HasMediaAttributes $model, string $attribute): bool
    {
        if (! is_a($model, Model::class) || ! array_key_exists($attribute, $model->getAttributes())) {
            return false;
        }

        return $model->isDirty($attribute);
    }

    /**
     * Get the variants of an unchanged media that are missing or older
     * than the original file (eg. when it was overwritten).
     */
    protected function getOutdatedVariants(int|string $key, MediaMutator $mutator): array
    {
        $generators = $mutator->getVariants();

        if (! $generators || ! ($media = $this->getRepository($mutator)->find($key))) {
            return [];
        }

        $original = $media->original();
        $variants = $media->variants($generators);

        return array_values(array_filter($generators, function (string|Variant $generator) use ($original, $variants) {
            $key = (is_string($generator) ? app($generator) : $generator)->key();

            foreach ($variants as $file) {
                if ($file->key === $key) {
                    return $this->isOlderThan($file, $original);
                }
            }

            return true;
        }));
    }

    /**
     * Check if given file was modified before the reference file. Files
     * are considered outdated when their disk cannot tell (as before).
     */
    protected function isOlderThan(MediaFile $file, MediaFile $reference): bool
    {
        try {
            return $file->lastModified() < $reference->lastModified();
        } catch (\Throwable) {
            return true;
        }
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
