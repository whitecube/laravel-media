<?php

namespace Whitecube\Media\Attributes;

use Whitecube\Media\File as Media;
use Whitecube\Media\MediaManager;

class File extends MediaMutator
{
    public function getMediaValue(mixed $value): Media
    {
        return app(MediaManager::class)->makeFile($value, $this);
    }
}
