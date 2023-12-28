<?php

namespace Modules\Search\Console\Commands\Scout;

use Exception;
use Illuminate\Console\Command;

class ElasticSearchIndex extends Command
{
    protected $client;

    public function __construct()
    {
        parent::__construct();

        $this->client = app('elasticsearch');
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scout:elasticsearch:create {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an Elasticsearch index';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!class_exists($model = $this->argument('model'))) {
            return $this->error("{$model} could not be resolved.");
        }

        $model = new $model();

        try {
            $this->client->indices()->create([
                'index' => $model->searchableAs(),
                'body' => [
                    'settings' => [
                        'index' => [
                            'analysis' => [
                                'filter' => $this->filters(),
                                'analyzer' => $this->analyzers(),
                            ],
                        ],
                    ],
                ],
            ]);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Generate an array of filters.
     *
     * @return array The filters array.
     */
    private function filters(): array
    {
        return [
            'words_splitter' => [
                'type' => 'word_delimiter',
                'preserve_original' => true,
                'catenate_all' => true,
            ],
        ];
    }

    /**
     * Returns an array of analyzers.
     *
     * @return array
     */
    private function analyzers(): array
    {
        return [
            'default' => [
                'type' => 'custom',
                'tokenizer' => 'standard',
                'char_filter' => ['html_strip'],
                'filter' => ['lowercase', 'words_splitter'],
            ],
        ];
    }
}
