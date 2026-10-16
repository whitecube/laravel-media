<?php

namespace Whitecube\Media\Jobs;

use Whitecube\Media\MediaManager;
use Whitecube\Media\Contracts\HasMediaAttributes;
use Whitecube\Media\Generators\Variant;
use Whitecube\Media\Generators\ConditionalVariant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;

class GenerateMediaVariant implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    /**
     * Discard the job when its model was deleted before the job could be processed.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Model|HasMediaAttributes $model,
        public string $attribute,
        public string|Variant $generator,
    ) {}

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        $generator = is_string($this->generator) ? $this->generator : get_class($this->generator);

        $key = app(MediaManager::class)->getMediaKey($this->model, $this->attribute);

        return $generator.':'.$key;
    }

    /**
     * Execute the job.
     */
    public function handle(MediaManager $manager): void
    {
        $mutator = $manager->getModelMediaMutator(
            model: $this->model,
            attribute: $this->attribute,
        );

        if (! $mutator) {
            return;
        }

        $repository = $manager->getRepository($mutator);

        $key = $manager->getMediaKey($this->model, $this->attribute);

        if (is_null($key) || (is_string($key) && ! strlen($key))) {
            return;
        }

        $media = $repository->find($key);

        if (! $media) {
            return;
        }

        if (is_string($this->generator)) {
            $this->generator = app($this->generator, [$this->model]);
        }

        if ($this->generator instanceof ConditionalVariant && ! $this->generator->shouldApply($media)) {
            return;
        }

        $file = $this->generator->generate(
            $media->getGeneratorOutputConfig($this->generator),
        );

        // TODO : update media data with re-generated path & timestamp
    }
}
