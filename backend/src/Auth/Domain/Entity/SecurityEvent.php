<?php

declare(strict_types=1);

namespace App\Auth\Domain\Entity;

use App\Auth\Domain\Enum\SecurityEventType;
use App\Auth\Infrastructure\Doctrine\DoctrineSecurityEventRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineSecurityEventRepository::class)]
#[ORM\Table(name: 'security_events')]
#[ORM\Index(name: 'idx_security_events_user_id_created_at', columns: ['user_id', 'created_at'])]
#[ORM\Index(name: 'idx_security_events_ip_created_at', columns: ['ip', 'created_at'])]
class SecurityEvent
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private string $id;

    #[ORM\Column(type: 'string', enumType: SecurityEventType::class)]
    private SecurityEventType $eventType;

    #[ORM\Column(type: UuidType::NAME, nullable: true)]
    private ?string $userId;

    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    private ?string $emailAttempted;

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    private ?string $reason;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ip;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $userAgent;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    private function __construct(
        SecurityEventType $eventType,
        ?string $userId,
        ?string $emailAttempted,
        ?string $reason,
        ?string $ip,
        ?string $userAgent,
    ) {
        $this->id = (string) Uuid::v7();
        $this->eventType = $eventType;
        $this->userId = $userId;
        $this->emailAttempted = $emailAttempted;
        $this->reason = $reason;
        $this->ip = $ip;
        $this->userAgent = $userAgent;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function loginSuccess(
        string $userId,
        ?string $emailAttempted,
        ?string $ip,
        ?string $userAgent,
    ): self {
        return new self(
            eventType: SecurityEventType::LOGIN_SUCCESS,
            userId: $userId,
            emailAttempted: $emailAttempted,
            reason: null,
            ip: $ip,
            userAgent: $userAgent,
        );
    }

    public static function loginFailed(
        string $reason,
        ?string $emailAttempted,
        ?string $ip,
        ?string $userAgent,
        ?string $userId = null,
    ): self {
        return new self(
            eventType: SecurityEventType::LOGIN_FAILED,
            userId: $userId,
            emailAttempted: $emailAttempted,
            reason: $reason,
            ip: $ip,
            userAgent: $userAgent,
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEventType(): SecurityEventType
    {
        return $this->eventType;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getEmailAttempted(): ?string
    {
        return $this->emailAttempted;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
