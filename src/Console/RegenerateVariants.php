<?php

namespace Whitecube\Media\Console;

use Whitecube\Media\MediaManager;
use Whitecube\Media\Attributes\Image;
use Whitecube\Media\Jobs\GenerateMediaVariant;
use Illuminate\Console\Command;

class RegenerateVariants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:regenerate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate all registered media variants.';

    protected MediaManager $manager;

    protected array $cache = [];

    /**
     * Execute the console command.
     */
    public function handle(MediaManager $manager)
    {
        $this->manager = $manager;

        foreach ($this->manager->getRegisteredModels() as $classname) {
            $this->handleModel($classname);
        }

        if (! $this->cache) {
            $this->info('No medias regenerated.');
            return;
        }

        $total = array_reduce($this->cache, fn(int $count, array $variants) => $count + count($variants), 0);
        $this->info('Regenerated '.$total.' variants for '.count($this->cache).' images.');
        $this->info('Done.');
    }

    protected function handleModel(string $classname): void
    {
        $attributes = $this->manager->getRegisteredModelMediaMutators($classname);

        $this->info($classname.': '.count($attributes).' attribute(s)');

        if(! $attributes) {
            return;
        }

        if(! ($count = $classname::count())) {
            $this->info('No models to handle.');
            return;
        }

        $progress = $this->output->createProgressBar($count);
        $progress->start();

        $classname::chunk(50, fn ($collection) => $collection->each(function($model) use ($attributes, $progress) {
            foreach ($attributes as $attribute => $mutator) {
                $this->handleModelMedia($model, $attribute, $mutator);
            }
            $progress->advance();
        }));

        $progress->finish();
        $this->newLine();
    }

    protected function handleModelMedia($model, string $attribute, Image $mutator): void
    {
        $repository = $this->manager->getRepository($mutator);
        $key = $model->getRawOriginal($attribute);

        if (is_null($key) || (is_string($key) && ! strlen($key)) || ! ($media = $repository->find($key))) {
            return;
        }

        $original = $media->original();

        if (! isset($this->cache[$original->fullPath()])) {
            $this->cache[$original->fullPath()] = [];
        }

        foreach ($mutator->getVariants() as $generator) {
            if (in_array($generator, $this->cache[$original->fullPath()])) continue;
            GenerateMediaVariant::dispatchSync($model, $attribute, $generator);
            $this->cache[$original->fullPath()][] = $generator;
        }
    }
}
