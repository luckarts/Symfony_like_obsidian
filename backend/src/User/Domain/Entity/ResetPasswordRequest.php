<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\User\Infrastructure\Doctrine\ResetPasswordRequestRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ResetPasswordRequestRepository::class)]
#[ORM\Table(name: 'password_reset_requests')]
#[ORM\Index(name: 'idx_password_reset_token_hash', columns: ['token_hash'])]
#[ORM\Index(name: 'idx_password_reset_user_id', columns: ['user_id'])]
class ResetPasswordRequest
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'string', length: 64)]
    private string $tokenHash;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $consumedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        User $user,
        string $tokenHash,
        \DateTimeImmutable $expiresAt,
    ) {
        $this->id = (string) Uuid::v7();
        $this->user = $user;
        $this->tokenHash = $tokenHash;
        $this->expiresAt = $expiresAt;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getConsumedAt(): ?\DateTimeImmutable
    {
        return $this->consumedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isExpired(): bool
    {
        return new \DateTimeImmutable() > $this->expiresAt;
    }

    public function isConsumed(): bool
    {
        return null !== $this->consumedAt;
    }

    public function consume(): void
    {
        $this->consumedAt = new \DateTimeImmutable();
    }
}
