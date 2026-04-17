<?php

declare(strict_types=1);

namespace App\Command;

use App\Config;
use App\ElasticsearchClientFactory;
use DateTimeInterface;
use Elasticsearch\Client;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand(name: 'document:index')]
class IndexCommand extends Command
{
    private Client $elasticsearchClient;

    private OutputInterface $output;

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->output = $output;
        $this->elasticsearchClient = ElasticsearchClientFactory::create(Config::getArray('ELASTICSEARCH_HOSTS'));
    }

    public function __invoke(
        #[Option] string $index = 'test',
        #[Option] int $numberOfShards = 3,
        #[Option] int $numberOfReplicas = 2,
        #[Option('Pause between iterations in microseconds')] int $iterationSleep = 500000,
        #[Option] int $maxNumberOfRequests = -1
    ): int {
        if (!$this->elasticsearchClient->indices()->exists(['index' => $index])) {
            $this->createIndex($index, $numberOfShards, $numberOfReplicas);
        }

        $numberOfRequests = 0;
        $infinite = $maxNumberOfRequests < 0;
        while ($infinite || $numberOfRequests < $maxNumberOfRequests) {
            $numberOfRequests++;
            $this->createDocument($index);
            if ($iterationSleep > 0) {
                usleep($iterationSleep);
            }
        }

        return Command::SUCCESS;
    }

    protected function createIndex(string $index, int $numberOfShards, int $numberOfReplicas): void
    {
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

    protected function createDocument($index): void
    {
        $timeStart = date_create_immutable();
        $this->output->write($timeStart->format('H:i:s.v '));

        try {
            $response = $this->elasticsearchClient->index([
                'index' => $index,
                'type' => '_doc',
                'body' => [
                    'name' => 'string',
                    'created_at' => $timeStart->format(DateTimeInterface::ATOM),
                ],
            ]);
            $elapsed = (float)(date_create_immutable()->diff($timeStart)->format('%s.%F'));

            if ($elapsed < 1) {
                $this->output->write("<fg=green>$elapsed</> ");
            } else {
                $this->output->write("<fg=red>$elapsed</> ");
            }
            $this->output->writeln(json_encode($response));
        } catch (Throwable $e) {
            $this->output->writeln("Error: " . $e->getMessage());
        }
    }
}
