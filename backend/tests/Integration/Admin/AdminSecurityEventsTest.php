<?php

declare(strict_types=1);

namespace App\Tests\Integration\Admin;

use App\Auth\Domain\Entity\SecurityEvent;
use App\Auth\Infrastructure\Doctrine\DoctrineSecurityEventRepository;
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
class AdminSecurityEventsTest extends WebTestCase
{
    private const CLIENT_ID = 'test_admin_security_events';
    private const CLIENT_SECRET = 'test_secret_admin_security_events';

    private KernelBrowser $client;
    private UserRepositoryInterface $userRepository;
    private DoctrineSecurityEventRepository $eventRepository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        $container = static::getContainer();

        /** @var UserRepositoryInterface $userRepository */
        $userRepository = $container->get(UserRepositoryInterface::class);
        $this->userRepository = $userRepository;

        /** @var DoctrineSecurityEventRepository $eventRepository */
        $eventRepository = $container->get(DoctrineSecurityEventRepository::class);
        $this->eventRepository = $eventRepository;

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $this->em = $em;

        // Clean up any security events from previous test runs
        $this->em->createQuery('DELETE FROM App\Auth\Domain\Entity\SecurityEvent')->execute();

        $clientManager = $container->get(ClientManagerInterface::class);
        if ($clientManager->find(self::CLIENT_ID) === null) {
            $oauthClient = new Client('AdminSecurityEventsTest', self::CLIENT_ID, self::CLIENT_SECRET);
            $oauthClient->setGrants(new Grant('password'), new Grant('refresh_token'));
            $oauthClient->setScopes(new Scope('email'), new Scope('profile'));
            $clientManager->save($oauthClient);
        }
    }

    #[Test]
    public function admin_can_view_all_security_events(): void
    {
        $adminEmail = 'admin_test_' . uniqid() . '@example.com';
        $userEmail = 'user_test_' . uniqid() . '@example.com';
        $password = 'T3st!P@ss#Admin42';

        // Register users via API
        $this->registerUser($adminEmail, $password, 'Admin', 'User');
        $this->registerUser($userEmail, $password, 'Regular', 'User');

        // Promote first user to admin
        $adminUser = $this->userRepository->findByEmail($adminEmail);
        $this->assertNotNull($adminUser);
        $this->promoteToAdmin($adminUser);

        // Create security events for both users
        $regularUser = $this->userRepository->findByEmail($userEmail);
        $this->assertNotNull($regularUser);

        $this->createSecurityEvent($adminUser, '127.0.0.1', 'Admin Browser');
        $this->createSecurityEvent($regularUser, '192.168.1.1', 'User Browser');
        $this->createSecurityEvent($regularUser, '192.168.1.2', 'User Mobile');

        // Obtain admin token first (creates a loginSuccess event)
        $adminToken = $this->obtainToken($adminEmail, $password);
        // Login event + 3 explicit events = 4 total
        $this->client->request('GET', '/api/v1/admin/security/events', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
        ]);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('member', $data);
        $this->assertCount(4, $data['member'], 'Admin should see all 4 security events (3 created + 1 login event)');
        $this->assertArrayHasKey('totalItems', $data);
        $this->assertSame(4, $data['totalItems']);
    }

    #[Test]
    public function regular_user_gets_403_on_admin_endpoint(): void
    {
        $email = 'regular_test_' . uniqid() . '@example.com';
        $password = 'T3st!P@ss#Regular42';

        $this->registerUser($email, $password, 'Regular', 'User');
        $token = $this->obtainToken($email, $password);

        $this->client->request('GET', '/api/v1/admin/security/events', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    #[Test]
    public function admin_endpoint_supports_pagination(): void
    {
        $adminEmail = 'admin_pagination_' . uniqid() . '@example.com';
        $password = 'T3st!P@ss#Admin42';

        $this->registerUser($adminEmail, $password, 'Admin', 'Pagination');
        $adminUser = $this->userRepository->findByEmail($adminEmail);
        $this->assertNotNull($adminUser);
        $this->promoteToAdmin($adminUser);

        // Create 5 events
        for ($i = 0; $i < 5; $i++) {
            $this->createSecurityEvent($adminUser, '10.0.0.' . $i, 'Agent ' . $i);
        }

        $adminToken = $this->obtainToken($adminEmail, $password);

        // First page: 2 items
        $this->client->request('GET', '/api/v1/admin/security/events', ['page' => 1, 'itemsPerPage' => 2], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
        ]);
        $page1 = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $page1['member']);
        $this->assertSame(6, $page1['totalItems'], 'Total items should be 6 (5 created + 1 login event)');

        // Second page: 2 items
        $this->client->request('GET', '/api/v1/admin/security/events', ['page' => 2, 'itemsPerPage' => 2], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
        ]);
        $page2 = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $page2['member']);

        // Third page: 2 items (login event fills last page)
        $this->client->request('GET', '/api/v1/admin/security/events', ['page' => 3, 'itemsPerPage' => 2], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken,
        ]);
        $page3 = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $page3['member']);
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

    private function promoteToAdmin(User $user): void
    {
        $ref = new \ReflectionProperty(User::class, 'roles');
        $ref->setAccessible(true);
        $ref->setValue($user, [Role::ROLE_ADMIN->value]);
        $this->em->flush();
    }

    private function createSecurityEvent(User $user, string $ip, string $userAgent): void
    {
        $event = SecurityEvent::loginSuccess(
            userId: (string) $user->getId(),
            emailAttempted: null,
            ip: $ip,
            userAgent: $userAgent,
        );
        $this->eventRepository->save($event);
        $this->em->flush();
    }
}
