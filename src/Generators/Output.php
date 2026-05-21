<?php

namespace Whitecube\Media\Generators;

use Whitecube\Media\Variant as VariantContainer;
use Whitecube\Media\Generators\Enums\Format;
use Whitecube\Media\Generators\Enums\CropPosition;
use Whitecube\Media\Generators\Transformations\Fit;
use Whitecube\Media\Generators\Transformations\CropTransformation;

class Output
{
    protected function __construct(
        public readonly Format $format,
        public readonly ?string $prefix = null,
        public readonly ?string $suffix = null,
        public readonly bool $rename = false,
        public readonly ?CropTransformation $crop = null,
    ) {}

    static public function make(Format $format): static
    {
        return new static(format: $format);
    }

    public function prefix(?string $prefix = null): static
    {
        return $this->duplicate(['prefix' => $prefix]);
    }

    public function suffix(?string $suffix = null): static
    {
        return $this->duplicate(['suffix' => $suffix]);
    }

    public function useUniqueFilename(bool $rename = true): static
    {
        return $this->duplicate(['rename' => $rename]);
    }

    public function fit(int $width, int $height, ?CropPosition $position = null): static
    {
        return $this->useCrop(new Fit(
            width: $width,
            height: $height,
            position: $position ?? CropPosition::Center
        ));
    }

    public function useCrop(?CropTransformation $crop = null): static
    {
        return $this->duplicate(['crop' => $crop]);
    }

    protected function duplicate(array $changes = []): static
    {
        return new static(
            format: array_key_exists('format', $changes) ? $changes['format'] : $this->format,
            prefix: array_key_exists('prefix', $changes) ? $changes['prefix'] : $this->prefix,
            suffix: array_key_exists('suffix', $changes) ? $changes['suffix'] : $this->suffix,
            rename: array_key_exists('rename', $changes) ? $changes['rename'] : $this->rename,
            crop: array_key_exists('crop', $changes) ? $changes['crop'] : $this->crop,
        );
    }

    public function getFilename(VariantContainer $original, bool $preventUniqueFilename = false): string
    {
        if ($this->rename && $preventUniqueFilename) {
            throw new \Exception('Output: filename should not be renamed with unique string.');
        }

        $filename = ($this->rename)
            ? bin2hex(random_bytes(32))
            : pathinfo($original->path, PATHINFO_FILENAME);

        if (strlen($this->prefix)) {
            $filename = $this->prefix.'_'.$filename;
        }

        if (strlen($this->suffix)) {
            $filename = $filename.'_'.$this->suffix;
        }

        return $filename;
    }

    public function getFile(VariantContainer $original, bool $preventUniqueFilename = false): string
    {
        return $this->getFilename($original, $preventUniqueFilename).'.'.$this->format->extension();
    }
}
