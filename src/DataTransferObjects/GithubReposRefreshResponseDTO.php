<?php

namespace App\DataTransferObjects;

/**
 * DTO Response for refresh action process
 * contain confirmation and details about failure if any
 */
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
