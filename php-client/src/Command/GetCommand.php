<?php

declare(strict_types=1);

namespace App\Command;

use App\Elasticsearch\ConnectionPoolStrategy;
use Generator;
use stdClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;

#[AsCommand(name: 'document:get')]
final class GetCommand extends Command
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
            fn($documentId) => $this->getDocument($index, $documentId),
            $this->getDocumentIds($index),
            $iterations,
            $pauseBetweenOperations,
        );
    }

    private function getDocumentIds(string $index): Generator
    {
        $batchSize = 2_000;
        $searchParams = [
            'index' => $index,
            'body' => [
                'query' => ['match_all' => new stdClass()],
                'sort' => ['geonameid' => 'asc'],
                'size' => $batchSize,
                '_source' => false,
            ],
        ];
        do {
            $documents = $this->elasticsearchClient->search($searchParams)['hits']['hits'];
            foreach ($documents as $document) {
                yield $document['_id'];
            }
            if (isset($document)) {
                $searchParams['body']['search_after'] = $document['sort'];
            }
        } while (!empty($documents));
    }

    private function getDocument(string $index, mixed $id): array
    {
        return $this->elasticsearchClient->get([
            'index' => $index,
            'type' => '_doc',
            'id' => $id,
        ]);
    }

    protected function summaryOutputFields(): array
    {
        return ['_index', '_id', '_version', 'found'];
    }
}
