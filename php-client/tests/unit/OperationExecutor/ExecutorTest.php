<?php

declare(strict_types=1);

namespace Tests\Unit\OperationExecutor;

use App\OperationExecutor\Executor;
use App\OperationExecutor\Result;
use App\OperationExecutor\Sleeper;
use App\OperationExecutor\SleeperInterface;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\Stopwatch;

final class ExecutorTest extends TestCase
{
    private Executor $executor;

    protected function setUp(): void
    {
        $this->executor = new Executor(new Stopwatch, new Sleeper);
    }

    public function testExecutesOperationForEachInput(): void
    {
        /** @var $results Result[] */
        $results = iterator_to_array(
            $this->executor->run(
                operationInputsProvider: $this->createGenerator([1, 2, 3]),
                operation: fn($x) => $x * 2,
                iterationLimit: null,
                pauseBetweenOperations: 0,
            )
        );

        $this->assertCount(3, $results);
        $this->assertSame(2, $results[0]->result);
        $this->assertSame(4, $results[1]->result);
        $this->assertSame(6, $results[2]->result);
    }

    public function testDoesNotExecuteOperationWhenInputProviderIsEmpty(): void
    {
        $called = false;
        $results = iterator_to_array(
            $this->executor->run(
                operationInputsProvider: $this->createGenerator([]),
                operation: function () use (&$called) {
                    $called = true;
                    return 'should not happen';
                },
                iterationLimit: null,
                pauseBetweenOperations: 0,
            )
        );

        $this->assertFalse($called, 'Operation should not be executed');
        $this->assertEmpty($results);
    }

    public function testDoesNotExecuteOperationWhenNoIterationsAreAllowed(): void
    {
        $called = false;
        $results = iterator_to_array(
            $this->executor->run(
                operationInputsProvider: null,
                operation: function () use (&$called) {
                    $called = true;
                    return 'should not happen';
                },
                iterationLimit: 0,
                pauseBetweenOperations: 0
            )
        );

        $this->assertFalse($called, 'Operation should not be executed');
        $this->assertEmpty($results);
    }

    public function testStopsWhenMoreInputsThanIterationLimit(): void
    {
        $results = iterator_count(
            $this->executor->run(
                operationInputsProvider: range(1, 10) |> $this->createGenerator(...),
                operation: fn($x) => $x,
                iterationLimit: 2,
                pauseBetweenOperations: 0,
            )
        );

        $this->assertSame(2, $results);
    }

    public static function respectsIterationLimitProvider(): array
    {
        return [
            '-1 ' => ['iterationLimit' => -1, 'expectedOperationRuns' => 0],
            '0 ' => ['iterationLimit' => 0, 'expectedOperationRuns' => 0],
            '1 ' => ['iterationLimit' => 1, 'expectedOperationRuns' => 1],
            '5 ' => ['iterationLimit' => 5, 'expectedOperationRuns' => 5],
        ];
    }

    #[DataProvider('respectsIterationLimitProvider')]
    public function testRespectsIterationLimit(int $iterationLimit, int $expectedOperationRuns): void
    {
        $results = iterator_count(
            $this->executor->run(
                operationInputsProvider: null,
                operation: fn($x) => $x,
                iterationLimit: $iterationLimit,
                pauseBetweenOperations: 0
            )
        );

        $this->assertSame($expectedOperationRuns, $results);
    }

    public function testRunsWithoutIterationLimit(): void
    {
        $results = iterator_count(
            $this->executor->run(
                operationInputsProvider: range(1, 10) |> $this->createGenerator(...),
                operation: fn($x) => $x,
                iterationLimit: null,
                pauseBetweenOperations: 0
            )
        );

        $this->assertSame(10, $results);
    }

    public static function sleepsBetweenOperationsProvider(): array
    {
        return [
            'pause disabled' => ['iterationLimit' => 3, 'pauseBetweenOperations' => 0, 'expectedPauseCalls' => 0],
            'zero iterations' => ['iterationLimit' => 0, 'pauseBetweenOperations' => 1.1, 'expectedPauseCalls' => 0],
            'one iteration' => ['iterationLimit' => 1, 'pauseBetweenOperations' => 1.2, 'expectedPauseCalls' => 0],
            'two iterations' => ['iterationLimit' => 2, 'pauseBetweenOperations' => 1.3, 'expectedPauseCalls' => 1],
            'three iterations' => ['iterationLimit' => 3, 'pauseBetweenOperations' => 1.4, 'expectedPauseCalls' => 2],
        ];
    }

    #[DataProvider('sleepsBetweenOperationsProvider')]
    public function testSleepsBetweenOperations(
        int $iterationLimit,
        float $pauseBetweenOperations,
        int $expectedPauseCalls
    ): void {
        $sleeper = $this->createMock(SleeperInterface::class);
        $sleeper
            ->expects($this->exactly($expectedPauseCalls))
            ->method('sleep')
            ->with($pauseBetweenOperations);
        $executor = new Executor(new Stopwatch, $sleeper);

        $results = iterator_count(
            $executor->run(
                operationInputsProvider: null,
                operation: fn($x) => $x,
                iterationLimit: $iterationLimit,
                pauseBetweenOperations: $pauseBetweenOperations,
            )
        );
        $this->assertSame($iterationLimit, $results);
    }

    private function createGenerator(array $items): Generator
    {
        foreach ($items as $item) {
            yield $item;
        }
    }
}