<?php

namespace App\DataTransferObjects;

class RepositoryItemDTO
{
    public function __construct(
        public readonly array $data
    ) {}
    public static function fromResponse(array $data): self
    {
        return new self(data: $data);
    }
}
