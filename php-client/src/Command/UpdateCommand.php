<?php

declare(strict_types=1);

namespace App\Command;

use App\Elasticsearch\ConnectionPoolStrategy;
use DateTimeInterface;
use Generator;
use stdClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;

#[AsCommand(name: 'document:update')]
final class UpdateCommand extends Command
{
    protected ConnectionPoolStrategy $elasticsearchConnectionPoolStrategy;

    public function __invoke(
        #[Option] string $index = 'test',
        #[Option] ?int $iterations = null,
        #[Option] float $pauseBetweenOperations = 1,
        #[Option] ConnectionPoolStrategy $connectionPoolStrategy = ConnectionPoolStrategy::STATIC_WITH_PING,
    ): int {
        $this->elasticsearchConnectionPoolStrategy = $connectionPoolStrategy;
        return $this->runOperationExecutor(
            fn($documentId) => $this->updateDocument($index, $documentId),
            $this->getDocumentIds($index),
            $iterations,
            $pauseBetweenOperations,
        );
    }

    private function getDocumentIds(string $index): Generator
    {
        $batchSize = 1_000;
        $searchParams = [
            'index' => $index,
            'body' => [
                'query' => ['match_all' => new stdClass()],
                'sort' => ['created_at' => 'asc'],
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

    private function updateDocument(string $index, string $documentId): array
    {
        return $this->elasticsearchClient->update([
            'index' => $index,
            'type' => '_doc',
            'id' => $documentId,
            'body' => [
                'doc' => [
                    'created_at' => date_create_immutable()->format(DateTimeInterface::RFC3339_EXTENDED),
                ]
            ],
        ]);
    }

    protected function summaryOutputFields(): array
    {
        return ['result', '_shards'];
    }
}
