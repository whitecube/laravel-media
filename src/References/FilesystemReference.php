<?php

namespace Whitecube\Media\References;

use ReflectionClass;
use ReflectionProperty;
use Illuminate\Contracts\Filesystem\Filesystem;

abstract class FilesystemReference
{
    protected ?Filesystem $resolved = null;

    static public function of(string|Filesystem|FilesystemReference $disk): static
    {
        if (is_string($disk)) {
            return new NamedFilesystemReference($disk);
        }

        if (is_a($disk, static::class)) {
            return $disk;
        }

        $instance = new ConfiguredFilesystemReference($disk->getConfig());
        $instance->setResolved($disk);

        return $instance;
    }

    public function resolve(): Filesystem
    {
        if (is_null($this->resolved)) {
            $this->setResolved($this->resolveReference());
        }

        return $this->resolved;
    }

    public function setResolved(Filesystem $resolved): self
    {
        $this->resolved = $resolved;

        return $this;
    }

    abstract protected function resolveReference(): Filesystem;

    public function __serialize(): array
    {
        return array_reduce(
            (new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC),
            function (array $data, ReflectionProperty $property) {
                if ($property->isStatic() || ! $property->isInitialized($this)) {
                    return $data;
                }

                $data[$property->getName()] = $property->getValue($this);

                return $data;
            },
            [],
        );
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $property => $value) {
            $this->$property = $value;
        }
    }
}
