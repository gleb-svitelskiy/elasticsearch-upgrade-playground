<?php

declare(strict_types=1);

namespace App\OperationExecutor;

use DateTimeImmutable;

final readonly class Result
{
    public function __construct(
        public mixed $result,
        public DateTimeImmutable $start,
        public DateTimeImmutable $end,
        public float $durationMilliseconds
    ) {
    }

    public function startTime(): string
    {
        return $this->start->format('H:i:s.v');
    }

    public function duration(): string
    {
        return number_format($this->durationMilliseconds / 1_000, 3, '.', '');
    }
}