<?php

namespace Whitecube\Media\Contracts;

use Whitecube\Media\Attributes\Image;

interface HasMediaAttributes
{
    public function getMediaAttribute(string $attribute): ?Image;
    public function getMediaKey(string $attribute): null|int|string;
    public function getMediaCount(): int|array;
    public function getMediaQuery(string $model);
}
