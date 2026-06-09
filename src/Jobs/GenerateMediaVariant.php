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

        $key = is_a($this->model, HasMediaAttributes::class)
            ? $this->model->getMediaKey($this->attribute)
            : $this->model->getRawOriginal($this->attribute);

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

        $key = is_a($this->model, HasMediaAttributes::class)
            ? $this->model->getMediaKey($this->attribute)
            : $this->model->getRawOriginal($this->attribute);

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
