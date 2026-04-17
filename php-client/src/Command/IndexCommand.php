<?php

declare(strict_types=1);

namespace App\Command;

use App\Elasticsearch\ConnectionPoolStrategy;
use DateTimeInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;

#[AsCommand(name: 'document:index')]
final class IndexCommand extends Command
{
    protected ConnectionPoolStrategy $elasticsearchConnectionPoolStrategy;

    public function __invoke(
        #[Option] string $index = 'test',
        #[Option] int $numberOfShards = 3,
        #[Option] int $numberOfReplicas = 2,
        #[Option] ?int $iterations = null,
        #[Option] float $pauseBetweenOperations = 1,
        #[Option] ConnectionPoolStrategy $connectionPoolStrategy = ConnectionPoolStrategy::STATIC_WITH_PING,
    ): int {
        $this->elasticsearchConnectionPoolStrategy = $connectionPoolStrategy;

        $this->createIndexIfNotExists($index, $numberOfShards, $numberOfReplicas);

        return $this->runOperationExecutor(
            fn() => $this->createDocument($index),
            iterationLimit: $iterations,
            pauseBetweenOperations: $pauseBetweenOperations,
        );
    }

    private function createIndexIfNotExists(string $index, int $numberOfShards, int $numberOfReplicas): void
    {
        if ($this->elasticsearchClient->indices()->exists(['index' => $index])) {
            return;
        }

        $this->elasticsearchClient->indices()->create([
            'index' => $index,
            'body' => [
                'settings' => [
                    'number_of_shards' => $numberOfShards,
                    'number_of_replicas' => $numberOfReplicas,
                ]
            ]
        ]);
    }

    private function createDocument(string $index): array
    {
        return $this->elasticsearchClient->index([
            'index' => $index,
            'type' => '_doc',
            'body' => [
                'name' => 'string',
                'created_at' => date_create_immutable()->format(DateTimeInterface::RFC3339_EXTENDED),
            ],
        ]);
    }

    protected function summaryOutputFields(): array
    {
        return ['result', '_shards'];
    }
}
