<?php

namespace Whitecube\Media\Console;

use Whitecube\Media\MediaManager;
use Whitecube\Media\Contracts\HasMediaAttributes;
use Whitecube\Media\Attributes\Image;
use Whitecube\Media\Jobs\GenerateMediaVariant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

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

        // In case of HasMediaAttributes "models", multiple queries could be necessary.
        // We'll only keep those with positive counts.
        if (is_array($count = $this->getModelCount($classname))) {
            [$count, $queries] = array_reduce(
                array_keys($count),
                fn(array $stack, string $key) => ($value = $count[$key]) ? [$stack[0] + $value, [...$stack[1], $key]] : $stack,
                [0, []],
            );
        } else {
            $queries = [$classname];
        }

        if(! $count) {
            $this->info('No models to handle.');
            return;
        }

        $progress = $this->output->createProgressBar($count);
        $progress->start();

        foreach($queries as $queryKey) {
            $this->getModelQuery($classname, $queryKey)->chunk(50, fn ($collection) => $collection->each(function($model) use ($attributes, $progress, $classname) {
                $this->getModelItems($model, $classname)->each(function($item) use ($attributes) {
                    foreach ($attributes as $attribute => $mutator) {
                        $this->handleModelMedia($item, $attribute, $mutator);
                    }
                });
                $progress->advance();
            }));
        }

        $progress->finish();
        $this->newLine();
    }

    protected function handleModelMedia(Model|HasMediaAttributes $model, string $attribute, Image $mutator): void
    {
        $repository = $this->manager->getRepository($mutator);
        $key = is_a($model, HasMediaAttributes::class)
            ? $model->getMediaKey($attribute)
            : $model->getRawOriginal($attribute);

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

    protected function getModelCount(string $classname): int|array
    {
        if (is_a($classname, HasMediaAttributes::class, true)) {
            return (new $classname)->getMediaCount();
        }

        return $classname::count();
    }

    protected function getModelQuery(string $classname, string $queryKey)
    {
        if (is_a($classname, HasMediaAttributes::class, true)) {
            return (new $classname)->getMediaQuery($queryKey);
        }

        return $classname::query();
    }

    protected function getModelItems(Model $model, string $classname): Collection
    {
        if (is_a($classname, HasMediaAttributes::class, true) && method_exists(($handler = new $classname), 'getMediaItems')) {
            return $handler->getMediaItems($model);
        }

        return collect([$model]);
    }
}
