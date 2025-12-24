<?php

namespace App\DataTransferObjects;

class GithubReposRefreshResponseDTO
{
    public function __construct(
        public bool $ok = true,
        public ?string $message = null,
    ) {}
    public function toArray(): array
    {
        return [
            'ok' => $this->ok,
            'message' => $this->message,
        ];
    }


}
