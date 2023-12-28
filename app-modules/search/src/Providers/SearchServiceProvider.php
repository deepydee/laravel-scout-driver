<?php

namespace Modules\Search\Providers;

use Elastic\Elasticsearch\ClientBuilder;
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
        $this->app->singleton('elasticsearch', function() {
            return ClientBuilder::create()
                ->setHosts(config('scout.elasticsearch.hosts'))
                ->build();
        });

        resolve(EngineManager::class)->extend('elasticsearch', function () {
            return new ElasticSearchEngine(
                app('elasticsearch')
            );
        });
    }
}
