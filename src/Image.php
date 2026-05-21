<?php

namespace Whitecube\Media;

use Whitecube\Media\Variant;
use Whitecube\Media\Attributes\Image as ImageAttribute;
use Whitecube\Media\Repositories\MediaInterface;
use Whitecube\Media\Repositories\MediaRepository;

class Image
{
    public function __construct(
        public readonly null|int|string $key,
        protected ?Variant $original = null,
        protected array $variants = [],
        protected ?string $placeholder = null,
    ) {}

    static public function make(?MediaInterface $source, ImageAttribute $attribute): static
    {
        return new static(
            key: $source?->key(),
            original: $source?->original(),
            variants: $source?->variants($attribute->getVariants()) ?: [],
        );
    }

    public function isEmpty(): bool
    {
        return is_null($this->key);
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    public function src(?string $variant = null): ?string
    {
        // TODO : get requested variant
        // TODO : get default variant

        return $this->original?->url
            ?? $this->placeholder;
    }

    public function srcset(): ?string
    {
        // TODO.
        return null;
    }

    public function sizes(): ?string
    {
        // TODO.
        return null;
    }

    public function alt(?string $fallback = null): ?string
    {
        // TODO : get the source alt.
        return $fallback;
    }

    public function withPlaceholder(?string $placeholder = null): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }
}
