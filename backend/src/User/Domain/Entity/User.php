<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\Event\HasDomainEventsInterface;
use App\User\Application\Trait\RecordsDomainEvents;
use App\User\Domain\Enum\Role;
use App\User\Domain\Event\UserRegisteredEvent;
use App\User\Infrastructure\Doctrine\DoctrineUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineUserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'uniq_users_email', columns: ['email'])]
#[ORM\HasLifecycleCallbacks]
class User implements PasswordAuthenticatedUserInterface, HasDomainEventsInterface
{
    use RecordsDomainEvents;
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME)]
    private ?string $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string')]
    private string $password;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(type: 'string', length: 100)]
    private string $firstName;

    #[ORM\Column(type: 'string', length: 100)]
    private string $lastName;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isVerified = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function register(
        string $email,
        string $hashedPassword,
        string $firstName,
        string $lastName,
    ): self {
        $user = new self();
        $user->id = (string) Uuid::v7();
        $user->email = $email;
        $user->password = $hashedPassword;
        $user->firstName = $firstName;
        $user->lastName = $lastName;
        $user->roles = [Role::ROLE_USER->value];

        $user->recordEvent(new UserRegisteredEvent(
            userId: (string) $user->id,
            email: $user->email,
            registeredAt: new \DateTimeImmutable(),
        ));

        return $user;
    }
    
    public function getId(): ?string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $hashedPassword): void
    {
        $this->password = $hashedPassword;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        if (!in_array(Role::ROLE_USER->value, $roles, true)) {
            $roles[] = Role::ROLE_USER->value;
        }

        /** @var list<string> $roles */
        return $roles;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function updateProfile(string $firstName, string $lastName): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function verify(): void
    {
        $this->isVerified = true;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
