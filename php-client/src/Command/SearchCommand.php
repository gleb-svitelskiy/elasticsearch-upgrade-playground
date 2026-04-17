<?php

declare(strict_types=1);

namespace App\Command;

use App\Elasticsearch\ConnectionPoolStrategy;
use Generator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;

#[AsCommand(name: 'document:search')]
final class SearchCommand extends Command
{
    protected ConnectionPoolStrategy $elasticsearchConnectionPoolStrategy;

    public function __invoke(
        #[Option] string $index = 'geonames',
        #[Option] ?int $iterations = null,
        #[Option] float $pauseBetweenOperations = 1,
        #[Option] ConnectionPoolStrategy $connectionPoolStrategy = ConnectionPoolStrategy::STATIC_WITH_PING,
    ): int {
        $this->elasticsearchConnectionPoolStrategy = $connectionPoolStrategy;
        return $this->runOperationExecutor(
            fn($params) => $this->searchDocuments($index, ...$params),
            $this->getSearchParams($index),
            $iterations,
            $pauseBetweenOperations,
        );
    }

    private function getSearchParams(string $index): Generator
    {
        $geonameidMin = (int)$this->elasticsearchClient->search([
            'index' => $index,
            'body' => [
                'size' => 0,
                'aggregations' => ['geonameid_min' => ['min' => ['field' => 'geonameid']]],
            ],
        ])['aggregations']['geonameid_min']['value'];
        $geonameidMax = (int)$this->elasticsearchClient->search([
            'index' => $index,
            'body' => [
                'size' => 0,
                'aggregations' => ['geonameid_max' => ['max' => ['field' => 'geonameid']]],
            ],
        ])['aggregations']['geonameid_max']['value'];

        $step = 100;
        for ($min = $geonameidMin; $min <= $geonameidMax; $min += $step) {
            $max = min($min + $step - 1, $geonameidMax);
            yield ['min' => $min, 'max' => $max];
        }
    }

    private function searchDocuments(string $index, int $min, int $max): array
    {
        return $this->elasticsearchClient->search([
            'index' => $index,
            'filter_path' => 'took,timed_out,_shards,hits.total',
            'body' => [
                'query' => ['range' => ['geonameid' => ['gte' => $min, 'lte' => $max]]],
            ]
        ]);
    }

    protected function summaryOutputFields(): array
    {
        return [];
    }
}
