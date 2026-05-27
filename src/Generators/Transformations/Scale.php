<?php

namespace Whitecube\Media\Generators\Transformations;

use Intervention\Image\Image as InterventionImage;

class Scale extends ResizeTransformation
{
    public function __construct(
        protected ?int $width,
        protected ?int $height,
    ) {}

    public function apply(mixed $image): mixed
    {
        return match (get_class($image)) {
            InterventionImage::class => $this->applyInterventionImage($image),
            default => throw new \InvalidArgumentException('Image object "'.get_class($image).'" is not supported for "scale" transformations.'),
        };
    }

    protected function applyInterventionImage(InterventionImage $image): InterventionImage
    {
        return $image->scaleDown(
            width: $this->width,
            height: $this->height,
        );
    }
}
