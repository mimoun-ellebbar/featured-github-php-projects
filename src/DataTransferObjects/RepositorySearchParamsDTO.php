<?php

namespace App\DataTransferObjects;

use App\Enums\RepositoryOrderEnum;
use App\Enums\RepositorySortByEnum;

class RepositorySearchParamsDTO
{
    public function __construct(
        public ?string               $q = null,
        public ?string               $language = null,
        public ?bool                 $archived = null,
        public ?RepositorySortByEnum $sort = null,
        public RepositoryOrderEnum   $order = RepositoryOrderEnum::DESC,
        public int                   $page = 1,
        public int                   $perPage = 30,
    ) {}

    public static function fromArray(array $input): self
    {
        $sort = RepositorySortByEnum::tryFrom((string) ($input['sort'] ?? ''));
        $order = RepositoryOrderEnum::tryFrom($input['order'] ?? 'desc');
        return new self(
            q: self::nullIfEmpty($input['q'] ?? null),
            language: self::nullIfEmpty($input['language'] ?? null),
            sort: $sort ?: null,
            order: $order,
            page: max(1, (int) ($input['page'] ?? 1)),
            perPage: min(100, max(1, (int) ($input['per_page'] ?? $input['perPage'] ?? 30))),
        );
    }

    private static function nullIfEmpty(mixed $v): ?string
    {
        $v = is_string($v) ? trim($v) : (is_bool($v) ? json_encode($v) : null);
        return ($v === null || $v === '') ? null : (string) $v;
    }

    public function buildQuery(): array
    {
        $parts = [];

        if ($this->q) {
            $parts[] = $this->q;
        }
        if ($this->language) {
            $parts[] = 'language:' . $this->escapeQualifier($this->language);
        }
        // you can add more parts/filters over here
        // --
        $query = [
            'q' => trim(implode('+', $parts)),
            'order' => $this->order->value,
            'page' => $this->page,
            'per_page' => $this->perPage,
        ];

        if ($this->sort) {
            $query['sort'] = $this->sort->value;
        }

        return $query;
    }

    private function escapeQualifier(string $value): string
    {
        $value = trim($value);
        return str_contains($value, ' ') ? '"' . str_replace('"', '\"', $value) . '"' : $value;
    }
}
