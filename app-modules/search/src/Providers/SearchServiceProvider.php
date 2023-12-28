<?php

namespace Modules\Search\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use Modules\Search\Engine\ElasticSearchEngine;

class SearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {

    }

    public function boot(): void
    {
        resolve(EngineManager::class)->extend('elasticsearch', function () {
            return new ElasticSearchEngine();
        });
    }
}
