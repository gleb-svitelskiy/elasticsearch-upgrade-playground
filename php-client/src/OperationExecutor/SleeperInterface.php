<?php

namespace App\OperationExecutor;

interface SleeperInterface
{
    public function sleep(float $seconds): void;
}
