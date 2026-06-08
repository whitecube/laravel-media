<?php

namespace Whitecube\Media\Attributes;

use Whitecube\Media\Media;
use Whitecube\Media\MediaManager;
use Whitecube\Media\Contracts\HasMediaAttributes;
use Whitecube\Media\Generators\Variant;
use Whitecube\Media\References\FilesystemReference;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

abstract class MediaMutator extends Attribute
{
    protected Model|HasMediaAttributes $model;
    protected string $attribute;
    protected array $variants = [];
    protected ?string $default = null;
    protected ?string $repository = null;
    protected ?string $placeholder = null;
    protected ?FilesystemReference $disk = null;
    protected ?string $path = null;

    protected function __construct(Model|HasMediaAttributes $model, string $attribute, ?callable $get = null, ?callable $set = null)
    {
        $this->model = $model;
        $this->attribute = $attribute;
        $this->get = fn ($value, $attributes) => $this->getMedia($value, $attributes, $get);
        $this->set = fn ($value, $attributes) => $this->setMedia($value, $attributes, $set);
    }

    public static function make(?callable $get = null, ?callable $set = null): static
    {
        throw new \Exception('Media mutator should be created using '.static::class.'::attribute($model, $attribute).');
    }

    public static function attribute(Model|HasMediaAttributes $model, string $attribute, ?callable $get = null, ?callable $set = null): static
    {
        return new static($model, $attribute, $get, $set);
    }

    public function getModel(): Model|HasMediaAttributes
    {
        return $this->model;
    }

    public function getAttribute(): string
    {
        return $this->attribute;
    }

    public function disk(null|string|Filesystem|FilesystemReference $disk = null): self
    {
        $this->disk = $disk ? FilesystemReference::of($disk) : null;

        return $this;
    }

    public function getDisk(): ?FilesystemReference
    {
        return $this->disk;
    }

    public function path(?string $path = null): self
    {
        $this->path = trim($path, '/') ?: null;

        return $this;
    }

    public function directory(?string $path = null): self
    {
        return $this->path($path);
    }

    public function getPath(): ?string
    {
        return $this->path;
    }
    
    public function variant(string $classname): self
    {
        if (! is_a($classname, Variant::class, true)) {
            throw new \InvalidArgumentException('Provided variant "'.$classname.'" shoud implement '.Variant::class.'.');
        }

        $this->variants[] = $classname;
        
        return $this;
    }

    public function variants(array $classnames = []): self
    {
        foreach ($classnames as $classname) {
            $this->variant($classname);
        }

        return $this;
    }

    public function getVariants(): array
    {
        return $this->variants;
    }

    public function default(?string $key = null): self
    {
        $this->default = $key;

        return $this;
    }

    public function getDefault(): ?string
    {
        return $this->default;
    }

    public function repository(?string $classname = null): self
    {
        $this->repository = $classname;

        return $this;
    }

    public function getRepository(): ?string
    {
        return $this->repository;
    }

    public function placeholder(?string $src = null): self
    {
        $this->placeholder = $src;

        return $this;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    /**
     * Cast the given value.
     */
    protected function getMedia(mixed $value, array $attributes, ?callable $getter = null): mixed
    {
        if ($getter) {
            $value = call_user_func($getter, $value, $attributes);
        }

        if (is_a($value, Media::class)) {
            return $value;
        }

        return $this->getMediaValue($value);
    }

    abstract public function getMediaValue(mixed $value): Media;

    /**
     * Prepare the given value for storage.
     */
    public function setMedia(mixed $value, array $attributes, ?callable $setter = null): mixed
    {
        if ($setter) {
            $value = call_user_func($setter, $value, $attributes);
        }

        return $this->setMediaValue($value);
    }

    protected function setMediaValue(mixed $value): null|int|string
    {
        return app(MediaManager::class)->storeMedia($value, $this);
    }
}
