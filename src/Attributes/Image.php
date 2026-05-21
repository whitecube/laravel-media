<?php

namespace Whitecube\Media\Attributes;

use Whitecube\Media\Image as Media;
use Whitecube\Media\MediaManager;
use Whitecube\Media\Generators\Variant;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Image extends Attribute
{
    protected array $variants = [];
    protected ?string $default = null;
    protected ?string $repository = null;
    protected ?string $placeholder = null;

    public function __construct(?callable $get = null, ?callable $set = null)
    {
        $this->get = fn ($value, $attributes) => $this->getMedia($value, $attributes, $get);
        $this->set = fn ($value, $attributes) => $this->setMedia($value, $attributes, $set);
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

    public function default(string $classname): self
    {
        $this->default = $classname;

        return $this;
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

        return app(MediaManager::class)->makeImage($value, $this);
    }

    /**
     * Prepare the given value for storage.
     */
    public function setMedia(mixed $value, array $attributes, ?callable $setter = null): mixed
    {
        if ($setter) {
            $value = call_user_func($setter, $value, $attributes);
        }

        return $value;
    }
}
