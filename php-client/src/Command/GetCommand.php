<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: 'document:get')]
class GetCommand
{
    public function __invoke(): int
    {
        return Command::SUCCESS;
    }
}
