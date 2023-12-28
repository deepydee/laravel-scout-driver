<?php

declare(strict_types=1);

namespace Modules\Search\Engine;

use Elastic\Elasticsearch\Client;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;

class ElasticSearchEngine extends Engine
{
    public function __construct(protected Client $client) {}

    /**
     * Update the given model in the index.
     *
     * @param \Illuminate\Database\Eloquent\Collection $models
     * @return void
     */
    public function update($models)
    {
        $models->each(function ($model) {
            $params = $this->getRequestBody($model, [
                'id' => $model->getKey(),
                'body' => $model->toSearchableArray(),
            ]);

            $this->client->index($params);
        });
    }

    /**
     * Remove the given model from the index.
     *
     * @param \Illuminate\Database\Eloquent\Collection $models
     * @return void
     */
    public function delete($models)
    {
        $models->each(function ($model) {
            $params = $this->getRequestBody($model, [
                'id' => $model->getKey(),
            ]);

            $this->client->delete($params);
        });
    }

    /**
     * Perform the given search on the engine.
     *
     * @param Builder $builder
     */
    public function search(Builder $builder)
    {
        return $this->performSearch($builder);
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @param  array  $options
     * @return mixed
     */
    protected function performSearch(Builder $builder, array $options = [])
    {
        $params = array_merge_recursive($this->getRequestBody($builder->model), [
            'body' => [
                'from' => 0,
                'size' => 5000,
                'query' => [
                    'multi_match' => [
                        'query' => $builder->query,
                        'fields' => $builder->model::SEARCHABLE_FIELDS,
                        'type' => 'phrase_prefix',
                    ]
                ],
            ],
        ]);

        $options = array_merge_recursive($params, $options);

        return $this->client->search($options);
    }

    /**
     * Perform the given search on the engine.
     *
     * @param Builder $builder
     * @param int $perPage
     * @param int $page
     */
    public function paginate(Builder $builder, $perPage, $page) {}

    /**
     * Pluck and return the primary keys of the given results.
     *
     * @param mixed $results
     * @return \Illuminate\Support\Collection
     */
    public function mapIds($results)
    {
        // return collect($results['hits'])->pluck('objectID')->values();
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param Builder $builder
     * @param mixed $results
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function map(Builder $builder, $results, $model)
    {
        $hits = $results->asObject()->hits->hits;

        if (empty($hits)) {
            return $model->newCollection();
        }

        $ids = collect($hits)
            ->pluck('_id')
            ->values()
            ->all();

        return $model->getScoutModelsByIds($builder, $ids);
    }

    /**
     * Map the given results to instances of the given model via a lazy collection.
     *
     * @param Builder $builder
     * @param mixed $results
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Support\LazyCollection
     */
    public function lazyMap(Builder $builder, $results, $model) {}

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param mixed $results
     * @return int
     */
    public function getTotalCount($results) {}

    /**
     * Flush all of the model's records from the engine.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function flush($model) {}

    /**
     * Create a search index.
     *
     * @param string $name
     * @param array $options
     */
    public function createIndex($name, array $options = []) {}

    /**
     * Delete a search index.
     *
     * @param string $name
     */
    public function deleteIndex($name) {}

    /**
     * @param mixed $model The model to retrieve the searchable index for.
     * @param array $options Additional options to include in the request body.
     * @return array The generated request body.
     */
    protected function getRequestBody($model, array $options = []): array
    {
        return array_merge_recursive([
            'index' => $model->searchableAs(),
        ], $options);
    }
}
