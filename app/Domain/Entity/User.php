<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;

final class User
{
    public function __construct(
        public ?int $id,
        public string $username,
        public string $passwordHash,
        public DateTimeImmutable $createdAt,
    ) {}
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getUsername(): string
    {
        return $this->username;
    }
    public function getPassword(): string
    {
        return $this->passwordHash;
    }
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
