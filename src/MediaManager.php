<?php

namespace Whitecube\Media;

use Whitecube\Media\Image;
use Whitecube\Media\Attributes\Image as ImageAttribute;
use Whitecube\Media\Repositories\MediaInterface;
use Whitecube\Media\Repositories\MediaRepository;

class MediaManager
{
    public function makeImage(null|int|string $key, ImageAttribute $attribute): Image
    {
        $repository = $this->getRepository($attribute);

        $media = (is_null($key) || (is_string($key) && ! strlen($key)))
            ? null
            : $repository->find($key);

        return Image::make(
            source: $media,
            attribute: $attribute,
        );
    }

    protected function getRepository(?ImageAttribute $attribute): MediaRepository
    {
        $repository = $attribute?->getRepository() ?? $this->getDefaultRepository();

        return app($repository);
    }

    protected function getDefaultRepository(): string
    {
        return MediaRepository::class;
    }
}
