<?php

namespace App\DataTransferObjects;

use App\Entity\GithubRepository;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * it’s a presentation-focused DTO that keeps display logic out of the GitHub Repository entity,
 * preserving clean separation of concerns.
 */
class RepositoryItemDTO
{
    public function __construct(
        public readonly int                $repo_id,
        public readonly string             $name,
        public readonly string             $url,
        public readonly ?string             $description,
        public readonly int                $stars_count,
        public readonly ?DateTimeImmutable $pushed_at,
        public readonly ?DateTimeImmutable $updated_at,
        public readonly DateTimeImmutable  $created_at,
    ) {}

    public static function fromObject(GithubRepository $repo): self
    {
        return new self(
            repo_id: $repo->getId(),
            name: $repo->getName(),
            url: $repo->getUrl(),
            description: $repo->getDescription(),
            stars_count: $repo->getStarsCount(),
            pushed_at: $repo->getLastPushedAt(),
            updated_at: $repo->getUpdatedAt(),
            created_at: $repo->getCreatedAt(),
        );
    }

    public function toArray(bool $extended_detail = false): array
    {
        $default = [
            'id' => $this->repo_id,
            'name' => $this->name,
            'url' => $this->url,
        ];
        if ($extended_detail) {
            $default = array_merge($default, [
                'description' => $this->description,
                'stars_count' => $this->stars_count,
                'last_pushed_at' => $this->pushed_at?->format(DateTimeInterface::ATOM),
                'updated_at' => $this->updated_at?->format(DateTimeInterface::ATOM),
                'created_at' => $this->created_at->format(DateTimeInterface::ATOM),
                'url' => $this->url,
            ]);
        }
        return $default;
    }

    /**
     * @throws \Exception
     */
    public static function fromResponse(array $data): self
    {
        return new self(
            repo_id: $data['id'],
            name: $data['name'],
            url: $data['html_url'],
            description: $data['description'],
            stars_count: $data['stargazers_count'],
            pushed_at: new DateTimeImmutable($data['pushed_at']),
            updated_at: new DateTimeImmutable($data['updated_at']),
            created_at: new DateTimeImmutable($data['created_at']),
        );
    }

    public function asTableRow(): array
    {
        return [
            'id' => $this->repo_id,
            'name' => $this->name,
            'description' => $this->description,
            'url' => $this->url,
            'stars_count' => $this->stars_count,
            'created_at' => $this->formatDatetime($this->created_at),
            'updated_at' => $this->formatDatetime($this->updated_at),
            'last_pushed_at' => $this->formatDatetime($this->pushed_at),
        ];


    }


    private function formatDatetime(?DateTimeImmutable $iso_time, string $timezone = 'UTC', string $output_format = 'Y-m-d H:i:s'): ?string
    {
        if (!$iso_time) {
            return null;
        }

        try {
            return $iso_time
                ->setTimezone(new DateTimeZone($timezone))
                ->format($output_format);
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
