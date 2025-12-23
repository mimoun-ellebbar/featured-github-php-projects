<?php

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
final readonly class PullRepositoriesMessage
{
    public function __construct(
        public array $query
    ) {}
}
