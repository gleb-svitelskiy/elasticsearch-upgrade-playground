<?php

declare(strict_types=1);

namespace App\Command;

use App\Config;
use App\Elasticsearch\ClientFactory;
use App\Elasticsearch\ConnectionPoolStrategy;
use App\OperationExecutor\Executor;
use App\OperationExecutor\Result;
use App\OperationExecutor\Sleeper;
use Elasticsearch\Client;
use Generator;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;

abstract class Command extends SymfonyCommand
{
    private Executor $operationExecutor;
    protected OutputInterface $output;
    abstract protected ConnectionPoolStrategy $elasticsearchConnectionPoolStrategy {
        get;
    }
    private ?Client $elasticsearchClientInstance = null;
    protected Client $elasticsearchClient {
        get {
            return $this->elasticsearchClientInstance ??= ClientFactory::create(
                Config::getArray('ELASTICSEARCH_HOSTS'),
                $this->elasticsearchConnectionPoolStrategy
            );
        }
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->operationExecutor = new Executor(new Stopwatch, new Sleeper);
        $this->output = $output;
    }

    protected function runOperationExecutor(
        callable $operation,
        ?Generator $operationInputsProvider = null,
        ?int $iterationLimit = null,
        float $pauseBetweenOperations = 0.5,
        float $slowOperationThreshold = 1.0
    ): int {
        $generator = $this->operationExecutor->run(
            $operationInputsProvider,
            $operation,
            $iterationLimit,
            $pauseBetweenOperations,
        );

        foreach ($generator as $operationResult) {
            $this->writeLogToOutput($operationResult, $slowOperationThreshold);
        }

        return self::SUCCESS;
    }

    private function writeLogToOutput(Result $operationResult, float $slowOperationThreshold): void
    {
        $this->output->write($operationResult->startTime() . ' ');

        $duration = $operationResult->duration();
        $isSlow = $duration >= $slowOperationThreshold;
        $textColor = $isSlow ? 'red' : 'green';
        $this->output->write("<fg=$textColor>$duration</> ");

        $outputData = $operationResult->result;
        if (!$isSlow && !empty($this->summaryOutputFields())) {
            $outputData = array_intersect_key(
                $operationResult->result,
                array_fill_keys($this->summaryOutputFields(), null)
            );
        }
        $this->output->writeln(json_encode($outputData));
    }

    abstract protected function summaryOutputFields(): array;
}
