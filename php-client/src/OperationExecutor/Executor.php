<?php

declare(strict_types=1);

namespace App\OperationExecutor;

use Generator;
use Symfony\Component\Stopwatch\Stopwatch;

final readonly class Executor
{
    public function __construct(
        private Stopwatch $stopwatch,
        private SleeperInterface $sleeper,
    ) {
    }

    public function run(
        ?Generator $operationInputsProvider,
        callable $operation,
        ?int $iterationLimit,
        float $pauseBetweenOperations
    ): Generator {
        if ($iterationLimit !== null && $iterationLimit <= 0) {
            return;
        }

        $operationInputs = $operationInputsProvider ?? $this->infiniteIterator();
        $iterationCount = 0;
        foreach ($operationInputs as $operationArgument) {
            yield $this->execute($operation, $operationArgument);
            $iterationCount++;

            if ($iterationLimit !== null && $iterationCount >= $iterationLimit) {
                break;
            }

            if ($pauseBetweenOperations > 0) {
                $this->sleeper->sleep($pauseBetweenOperations);
            }
        }
    }

    private function execute(callable $operation, mixed $operationArgument): Result
    {
        $eventName = 'operation';
        $operationStart = date_create_immutable();
        $this->stopwatch->start($eventName);

        $operationResult = $operation($operationArgument);

        $operationDuration = $this->stopwatch->stop($eventName)->getEndTime();
        $operationEnd = date_create_immutable();
        $this->stopwatch->reset();
        return new Result($operationResult, $operationStart, $operationEnd, $operationDuration);
    }

    private function infiniteIterator(): Generator
    {
        while (true) {
            yield null;
        }
    }
}
