<?php

namespace Whitecube\Media\Jobs;

use Whitecube\Media\MediaManager;
use Whitecube\Media\Generators\Variant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;

class GenerateMediaVariant implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Model $model,
        public string $attribute,
        public string|Variant $generator,
    ) {}

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
        $media = $repository->find($this->model->getRawOriginal($this->attribute));

        if (! $media) {
            return;
        }

        if (is_string($this->generator)) {
            $this->generator = app($this->generator, [$this->model]);
        }

        $file = $this->generator->generate(
            $media->getGeneratorOutputConfig($this->generator),
        );

        // TODO : update media data with re-generated path & timestamp
    }
}
