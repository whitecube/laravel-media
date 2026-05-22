<?php

namespace Whitecube\Media;

use Whitecube\Media\MediaManager;
use Whitecube\Media\Repositories\MediaRepository;
use Whitecube\Media\Repositories\DatabaseRepository;
use Illuminate\Support\ServiceProvider;

class MediaServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\RegenerateVariants::class,
            ]);
        }
    }

    public function register()
    {
        $this->app->bind(MediaRepository::class, fn ($app) => $app->make(DatabaseRepository::class));
        $this->app->singleton(MediaManager::class, fn ($app) => new MediaManager());
    }
}
