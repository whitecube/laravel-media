<?php

namespace Whitecube\Media\Generators;

use Whitecube\Media\MediaFile;
use Whitecube\Media\Generators\Enums\Format;
use Whitecube\Media\Generators\Enums\Position;
use Whitecube\Media\Generators\Transformations\Fit;
use Whitecube\Media\Generators\Transformations\ResizeTransformation;
use Intervention\Image\Image as InterventionImage;
use Illuminate\Contracts\Filesystem\Filesystem;

class Output
{
    protected function __construct(
        public readonly Format $format,
        public readonly ?string $prefix = null,
        public readonly ?string $suffix = null,
        public readonly bool $rename = false,
        public readonly ?string $directory = null,
        public readonly null|string|Filesystem $disk = null,
        public readonly ?ResizeTransformation $resize = null,
        public readonly ?MediaFile $original = null,
        public readonly ?string $key = null,
    ) {}

    static public function make(Format $format): static
    {
        return new static(format: $format);
    }

    static public function config(Output $output, MediaFile $original, string $key): static
    {
        return static::duplicate($output, [
            'original' => $original,
            'key' => $key,
        ]);
    }

    static protected function duplicate(Output $instance, array $changes = []): static
    {
        return new static(
            format: array_key_exists('format', $changes) ? $changes['format'] : $instance->format,
            prefix: array_key_exists('prefix', $changes) ? $changes['prefix'] : $instance->prefix,
            suffix: array_key_exists('suffix', $changes) ? $changes['suffix'] : $instance->suffix,
            rename: array_key_exists('rename', $changes) ? $changes['rename'] : $instance->rename,
            directory: array_key_exists('directory', $changes) ? $changes['directory'] : $instance->directory,
            disk: array_key_exists('disk', $changes) ? $changes['disk'] : $instance->disk,
            resize: array_key_exists('resize', $changes) ? $changes['resize'] : $instance->resize,
            original: array_key_exists('original', $changes) ? $changes['original'] : $instance->original,
            key: array_key_exists('key', $changes) ? $changes['key'] : $instance->key,
        );
    }

    public function disk(null|string|Filesystem $disk = null): static
    {
        return static::duplicate($this, ['disk' => $disk]);
    }

    public function directory(?string $directory = null): static
    {
        return static::duplicate($this, ['directory' => $directory]);
    }

    public function prefix(?string $prefix = null): static
    {
        return static::duplicate($this, ['prefix' => $prefix]);
    }

    public function suffix(?string $suffix = null): static
    {
        return static::duplicate($this, ['suffix' => $suffix]);
    }

    public function useUniqueFilename(bool $rename = true): static
    {
        return static::duplicate($this, ['rename' => $rename]);
    }

    public function fit(int $width, int $height, ?Position $position = null): static
    {
        return $this->useResize(new Fit(
            width: $width,
            height: $height,
            position: $position ?? Position::Center
        ));
    }

    public function useResize(?ResizeTransformation $resize = null): static
    {
        return static::duplicate($this, ['resize' => $resize]);
    }

    public function getFilename(MediaFile $original, bool $preventUniqueFilename = false): string
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

    public function getFile(MediaFile $original, bool $preventUniqueFilename = false): string
    {
        return $this->getFilename($original, $preventUniqueFilename).'.'.$this->format->extension();
    }

    public function store(mixed $image, int $quality = 100): MediaFile
    {
        if (! $this->original || ! $this->key) {
            throw new \Exception('Cannot store variant using unconfigured Output instance.');
        }

        $encoded = $this->encodeImageForStorage($image, $quality);
        $path = ($this->directory ? $this->directory.'/' : '')
            .$this->getFile($this->original);

        $this->original->disk->put($path, $encoded);

        return new MediaFile(
            key: $this->key,
            path: $path,
            disk: $this->original->disk,
        );
    }

    protected function encodeImageForStorage(mixed $image, int $quality): mixed
    {
        return match (get_class($image)) {
            InterventionImage::class => $image->encodeUsingFileExtension($this->format->extension(), quality: $quality),
            default => $image,
        };
    }
}
