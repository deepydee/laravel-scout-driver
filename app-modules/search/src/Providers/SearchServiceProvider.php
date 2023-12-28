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
        $this->app->singletonIf('elasticsearch', function () {
            return ClientBuilder::create()
                ->setHosts(config('scout.elasticsearch.hosts'))
                ->setBasicAuthentication(config('scout.elasticsearch.user'), config('scout.elasticsearch.password'))
                ->setCABundle(storage_path().'/http_ca.crt')
                ->build();
        });

        resolve(EngineManager::class)->extend('elasticsearch', function () {
            return new ElasticSearchEngine(
                app('elasticsearch')
            );
        });
    }
}
