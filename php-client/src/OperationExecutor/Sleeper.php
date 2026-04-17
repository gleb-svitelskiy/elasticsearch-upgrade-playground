<?php

namespace App\OperationExecutor;

final readonly class Sleeper implements SleeperInterface
{
    public function sleep(float $seconds): void
    {
        usleep((int)($seconds * 1_000_000));
    }
}
