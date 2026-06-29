<?php

declare(strict_types=1);

namespace App\Tests\Integration\Admin;

use App\User\Domain\Contract\UserRepositoryInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\Role;
use Doctrine\ORM\EntityManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

#[Group('integration')]
#[Group('admin')]
class AdminUsersTest extends WebTestCase
{
    private const CLIENT_ID = 'test_admin_users';
    private const CLIENT_SECRET = 'test_secret_admin_users';

    private KernelBrowser $client;
    private UserRepositoryInterface $userRepository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        $container = static::getContainer();

        /** @var UserRepositoryInterface $userRepository */
        $userRepository = $container->get(UserRepositoryInterface::class);
        $this->userRepository = $userRepository;

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $this->em = $em;

        // Clean up users from previous test runs
        $this->em->createQuery('DELETE FROM App\User\Domain\Entity\User')->execute();

        $clientManager = $container->get(ClientManagerInterface::class);
        if ($clientManager->find(self::CLIENT_ID) === null) {
            $oauthClient = new Client('AdminUsersTest', self::CLIENT_ID, self::CLIENT_SECRET);
            $oauthClient->setGrants(new Grant('password'), new Grant('refresh_token'));
            $oauthClient->setScopes(new Scope('email'), new Scope('profile'));
            $clientManager->save($oauthClient);
        }
    }

    #[Test]
    public function admin_can_list_all_users(): void
    {
        [$adminEmail, $password] = $this->createUserAndPromoteToAdmin();
        $this->registerUser('user_a_' . uniqid() . '@example.com', $password, 'Alice', 'Dupont');
        $this->registerUser('user_b_' . uniqid() . '@example.com', $password, 'Bob', 'Martin');

        $adminToken = $this->obtainToken($adminEmail, $password);

        $this->client->request('GET', '/api/v1/admin/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
        ]);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('member', $data);
        $this->assertCount(3, $data['member'], 'Admin should see all 3 users');
        $this->assertArrayHasKey('totalItems', $data);
        $this->assertSame(3, $data['totalItems']);

        $user = $data['member'][0];
        $this->assertArrayHasKey('id', $user);
        $this->assertArrayHasKey('firstName', $user);
        $this->assertArrayHasKey('lastName', $user);
        $this->assertArrayHasKey('roles', $user);
        $this->assertArrayHasKey('isVerified', $user);
        $this->assertArrayHasKey('createdAt', $user);
        // PII not exposed
        $this->assertArrayNotHasKey('email', $user);
        $this->assertArrayNotHasKey('updatedAt', $user);
    }

    #[Test]
    public function admin_can_change_user_role(): void
    {
        [$adminEmail, $password] = $this->createUserAndPromoteToAdmin();
        $targetEmail = 'target_role_' . uniqid() . '@example.com';
        $this->registerUser($targetEmail, $password, 'Target', 'User');
        $targetUser = $this->userRepository->findByEmail($targetEmail);
        $this->assertNotNull($targetUser);

        $adminToken = $this->obtainToken($adminEmail, $password);

        $this->client->request(
            'PATCH',
            '/api/v1/admin/users/' . $targetUser->getId() . '/role',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['role' => 'ROLE_ADMIN']),
        );

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        // Verify role was changed
        $this->em->clear();
        $updatedUser = $this->userRepository->findById((string) $targetUser->getId());
        $this->assertNotNull($updatedUser);
        $this->assertContains('ROLE_ADMIN', $updatedUser->getRoles());
    }

    #[Test]
    public function regular_user_gets_403_on_admin_endpoints(): void
    {
        $email = 'regular_forbidden_' . uniqid() . '@example.com';
        $password = 'T3st!P@ss#Regular42';

        $this->registerUser($email, $password, 'Regular', 'User');
        $token = $this->obtainToken($email, $password);

        // Test list endpoint
        $this->client->request('GET', '/api/v1/admin/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        $this->assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function admin_list_users_supports_pagination(): void
    {
        [$adminEmail, $password] = $this->createUserAndPromoteToAdmin();

        // Register 5 additional users
        for ($i = 0; $i < 5; $i++) {
            $this->registerUser('page_user_' . $i . '_' . uniqid() . '@example.com', $password, 'Page', 'User' . $i);
        }

        $adminToken = $this->obtainToken($adminEmail, $password);

        // First page: 2 items
        $this->client->request('GET', '/api/v1/admin/users', ['page' => 1, 'itemsPerPage' => 2], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
        ]);
        $page1 = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $page1['member']);
        $this->assertSame(6, $page1['totalItems'], 'Total items should be 6 (admin + 5 created users)');

        // Second page: 2 items
        $this->client->request('GET', '/api/v1/admin/users', ['page' => 2, 'itemsPerPage' => 2], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
        ]);
        $page2 = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $page2['member']);

        // Third page: 2 items
        $this->client->request('GET', '/api/v1/admin/users', ['page' => 3, 'itemsPerPage' => 2], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
        ]);
        $page3 = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $page3['member']);
    }

    #[Test]
    public function admin_gets_404_for_nonexistent_user_role_change(): void
    {
        [$adminEmail, $password] = $this->createUserAndPromoteToAdmin();
        $adminToken = $this->obtainToken($adminEmail, $password);

        $this->client->request(
            'PATCH',
            '/api/v1/admin/users/nonexistent-id/role',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['role' => 'ROLE_ADMIN']),
        );

        $this->assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function admin_gets_422_for_invalid_role(): void
    {
        [$adminEmail, $password] = $this->createUserAndPromoteToAdmin();
        $targetEmail = 'invalid_role_' . uniqid() . '@example.com';
        $this->registerUser($targetEmail, $password, 'Target', 'User');
        $targetUser = $this->userRepository->findByEmail($targetEmail);
        $this->assertNotNull($targetUser);

        $adminToken = $this->obtainToken($adminEmail, $password);

        $this->client->request(
            'PATCH',
            '/api/v1/admin/users/' . $targetUser->getId() . '/role',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken, 'CONTENT_TYPE' => 'application/json'],
            json_encode(['role' => 'ROLE_INVALID']),
        );

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
    }

    /** @return array{string, string} */
    private function createUserAndPromoteToAdmin(): array
    {
        $email = 'admin_test_' . uniqid() . '@example.com';
        $password = 'T3st!P@ss#Admin42';
        $this->registerUser($email, $password, 'Admin', 'Test');

        $adminUser = $this->userRepository->findByEmail($email);
        $this->assertNotNull($adminUser);
        $adminUser->setRoles([Role::ROLE_ADMIN->value]);
        $this->em->flush();

        return [$email, $password];
    }

    private function registerUser(string $email, string $password, string $firstName, string $lastName): void
    {
        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/ld+json'],
            json_encode([
                'email' => $email,
                'password' => $password,
                'firstName' => $firstName,
                'lastName' => $lastName,
            ]),
        );

        if ($this->client->getResponse()->getStatusCode() !== 201) {
            throw new \RuntimeException(sprintf(
                'User registration failed: %d %s',
                $this->client->getResponse()->getStatusCode(),
                $this->client->getResponse()->getContent(),
            ));
        }
    }

    private function obtainToken(string $email, string $password): string
    {
        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'password',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'username' => $email,
            'password' => $password,
            'scope' => 'email profile',
        ]);

        $response = $this->client->getResponse();
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException(sprintf(
                'Token request failed: %d %s',
                $response->getStatusCode(),
                $response->getContent(),
            ));
        }

        $data = json_decode($response->getContent(), true);

        return $data['access_token'];
    }
}
