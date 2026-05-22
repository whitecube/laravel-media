<?php

namespace Whitecube\Media\Generators\Transformations;

use Whitecube\Media\Generators\Enums\Position;
use Intervention\Image\Image as InterventionImage;

class Fit extends ResizeTransformation
{
    public function __construct(
        protected int $width,
        protected int $height,
        protected Position $position,
    ) {}

    public function apply(mixed $image): mixed
    {
        return match (get_class($image)) {
            InterventionImage::class => $this->applyInterventionImage($image),
            default => throw new \InvalidArgumentException('Image object "'.get_class($image).'" is not supported for "fit" transformations.'),
        };
    }

    protected function applyInterventionImage(InterventionImage $image): InterventionImage
    {
        if (! ($alignment = $this->position->toInterventionAlignment())) {
            // TODO: manual positionning.
            return $image;
        }

        return $image->coverDown($this->width, $this->height, $alignment);
    }
}
