<?php

declare(strict_types=1);

namespace App\Tests\Integration\User;

use App\User\Domain\Entity\User;
use App\User\Infrastructure\Doctrine\DoctrineUserRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/*
Pour vérifier que la requête SQL est correcte et que l'index en DB existe.
*/
#[Group('integration')]
#[Group('user')]
class UniqueEmailTest extends KernelTestCase
{
    private DoctrineUserRepository $repository;
    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();
        /** @var DoctrineUserRepository $repository */
        $repository = static::getContainer()->get(DoctrineUserRepository::class);
        $this->repository = $repository;
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->connection = $em->getConnection();
        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->connection->isTransactionActive()) {
            $this->connection->rollBack();
        }

        parent::tearDown();
    }

    #[Test]
    public function exists_by_email_returns_true_after_save(): void
    {
        $user = User::register('exists@integration-test.example.com', 'hashed', 'John', 'Doe');
        $this->repository->save($user);

        $this->assertTrue($this->repository->existsByEmail('exists@integration-test.example.com'));
        $this->assertFalse($this->repository->existsByEmail('other@integration-test.example.com'));
    }

    #[Test]
    public function save_throws_on_duplicate_email(): void
    {
        $user1 = User::register('dup@integration-test.example.com', 'hashed1', 'Alice', 'A');
        $this->repository->save($user1);

        $this->expectException(UniqueConstraintViolationException::class);

        $user2 = User::register('dup@integration-test.example.com', 'hashed2', 'Bob', 'B');
        $this->repository->save($user2);
    }
}