<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

#[AsCommand(name: 'document:update')]
class UpdateCommand
{
    public function __invoke(): int
    {
        return Command::SUCCESS;
    }
}
