<?php

namespace Whitecube\Media;

use Whitecube\Media\MediaFile;
use Whitecube\Media\Attributes\MediaMutator;
use Whitecube\Media\Repositories\MediaInterface;

abstract class Media
{
    public function __construct(
        public readonly null|int|string $key,
        public readonly ?MediaFile $original = null,
        protected array $variants = [],
        protected ?string $default = null,
        protected ?string $placeholder = null,
    ) {}

    static public function make(?MediaInterface $source, MediaMutator $attribute): static
    {
        $classname = match (get_class($attribute)) {
            \Whitecube\Media\Attributes\File::class => File::class,
            \Whitecube\Media\Attributes\Image::class => Image::class,
        };

        return new $classname(
            key: $source?->key(),
            original: $source?->original(),
            variants: $source?->variants($attribute->getVariants()) ?: [],
            default: $attribute->getDefault(),
            placeholder: $attribute->getPlaceholder(),
        );
    }

    public function isEmpty(): bool
    {
        return is_null($this->key) || ! $this->original?->exists();
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    public function src(?string $variant = null): ?string
    {
        return $this->variant($variant ?: $this->default)?->url()
            ?? $this->original?->url()
            ?? $this->placeholder;
    }

    public function variant(?string $key): ?MediaFile
    {
        if (! $key) {
            return null;
        }

        if ($key === $this->original?->key) {
            return $this->original;
        }

        foreach ($this->variants as $file) {
            if ($file->key === $key) return $file;
        }

        return null;
    }

    public function withPlaceholder(?string $placeholder = null): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function __toString(): string
    {
        return $this->src() ?: '';
    }

    public function delete(): void
    {
        $this->deleteVariants();
        $this->original?->delete();
    }

    public function deleteVariants(): void
    {
        foreach ($this->variants as $file) {
            $file->delete();
        }

        $this->variants = [];
    }
}
