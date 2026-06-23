<?php

declare(strict_types=1);

namespace App\Tests\E2E\Security;

use App\Auth\Domain\Entity\SecurityEvent;
use App\Auth\Domain\Enum\SecurityEventType;
use App\Auth\Infrastructure\Doctrine\DoctrineSecurityEventRepository;
use App\User\Domain\Contract\UserRepositoryInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

#[Group('e2e')]
#[Group('auth')]
class SecurityEventsEndpointTest extends WebTestCase
{
    private const CLIENT_ID = 'test_client';
    private const CLIENT_SECRET = 'test_secret';

    private KernelBrowser $client;
    private DoctrineSecurityEventRepository $securityEventRepository;
    private UserRepositoryInterface $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        /** @var ClientManagerInterface $clientManager */
        $clientManager = static::getContainer()->get(ClientManagerInterface::class);
        $oauthClient = new Client('Test Client', self::CLIENT_ID, self::CLIENT_SECRET);
        $oauthClient->setGrants(new Grant('password'), new Grant('refresh_token'));
        $oauthClient->setScopes(new Scope('email'), new Scope('profile'));
        $clientManager->save($oauthClient);

        /** @var DoctrineSecurityEventRepository $repository */
        $repository = static::getContainer()->get(DoctrineSecurityEventRepository::class);
        $this->securityEventRepository = $repository;

        /** @var UserRepositoryInterface $userRepository */
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $this->userRepository = $userRepository;
    }

    private function registerUser(string $email, string $password): void
    {
        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/ld+json'],
            json_encode(['email' => $email, 'password' => $password, 'firstName' => 'Jane', 'lastName' => 'Doe'])
        );
        $this->assertSame(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
    }

    private function getAccessToken(string $email, string $password): string
    {
        $this->client->request('POST', '/oauth2/token', [
            'grant_type' => 'password',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'username' => $email,
            'password' => $password,
            'scope' => 'email',
        ]);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('access_token', $content);
        return $content['access_token'];
    }

    #[Test]
    public function own_events_returned(): void
    {
        $email = 'user_a_' . uniqid() . '@example.com';
        $password = 'T3st!P@ss#Api42';

        $this->registerUser($email, $password);
        $token = $this->getAccessToken($email, $password);

        $this->client->request(
            'GET',
            '/api/v1/security/events',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ]
        );
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('member', $content);
        $members = $content['member'];
        $this->assertNotEmpty($members);
        $firstEvent = $members[0];
        $this->assertArrayHasKey('eventType', $firstEvent);
        $this->assertArrayHasKey('ip', $firstEvent);
        $this->assertArrayHasKey('userAgent', $firstEvent);
        $this->assertArrayHasKey('createdAt', $firstEvent);
        $this->assertArrayNotHasKey('emailAttempted', $firstEvent);
        // Optionally assert that eventType is LOGIN_SUCCESS
        $this->assertSame(SecurityEventType::LOGIN_SUCCESS->value, $firstEvent['eventType']);
    }

    #[Test]
    public function other_user_events_excluded(): void
    {
        $emailA = 'user_a_' . uniqid() . '@example.com';
        $emailB = 'user_b_' . uniqid() . '@example.com';
        $password = 'T3st!P@ss#Api42';

        $this->registerUser($emailA, $password);
        $this->registerUser($emailB, $password);

        $tokenA = $this->getAccessToken($emailA, $password);
        $tokenB = $this->getAccessToken($emailB, $password);

        $userA = $this->userRepository->findByEmail($emailA);
        $userB = $this->userRepository->findByEmail($emailB);
        $this->assertNotNull($userA);
        $this->assertNotNull($userB);

        // User A creates two security events via direct persistence (to avoid extra logins)
        $event1 = SecurityEvent::loginSuccess(
            $userA->getId(),
            null,
            '1.2.3.4',
            'agentA'
        );
        $this->securityEventRepository->save($event1, true);

        $event2 = SecurityEvent::loginFailed(
            'bad_password',
            null,
            '1.2.3.5',
            'agentB'
        );
        $this->securityEventRepository->save($event2, true);

        // User B creates one event
        $event3 = SecurityEvent::loginSuccess(
            $userB->getId(),
            null,
            '5.6.7.8',
            'agentC'
        );
        $this->securityEventRepository->save($event3, true);

        // Now call endpoint as User A
        $this->client->request(
            'GET',
            '/api/v1/security/events',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $tokenA,
            ]
        );
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('member', $content);
        $members = $content['member'];
        // Expect exactly 2 events (those belonging to user A)
        $this->assertCount(2, $members);
        // Ensure none have emailAttempted
        foreach ($members as $event) {
            $this->assertArrayNotHasKey('emailAttempted', $event);
        }
    }

    #[Test]
    public function unauthenticated_returns_401(): void
    {
        $this->client->request(
            'GET',
            '/api/v1/security/events',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT' => 'application/ld+json',
            ]
        );
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    #[Test]
    public function pagination_default_20(): void
    {
        $email = 'user_pagination_' . uniqid() . '@example.com';
        $password = 'T3st!P@ss#Api42';

        $this->registerUser($email, $password);
        $token = $this->getAccessToken($email, $password);

        $user = $this->userRepository->findByEmail($email);
        $this->assertNotNull($user);

        // Persist 24 events directly (+ 1 from getAccessToken auth = 25 total)
        for ($i = 0; $i < 24; $i++) {
            $event = SecurityEvent::loginSuccess(
                $user->getId(),
                null,
                sprintf('10.0.0.%d', $i),
                'agent-' . $i
            );
            $this->securityEventRepository->save($event, true);
        }

        $this->client->request(
            'GET',
            '/api/v1/security/events',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ]
        );
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('member', $content);
        $this->assertArrayHasKey('totalItems', $content);
        $this->assertArrayHasKey('view', $content);
        $this->assertCount(20, $content['member']); // default itemsPerPage = 20
        $this->assertSame(25, $content['totalItems']);
        // Check pagination links exist
        $this->assertArrayHasKey('@id', $content['view']);
        $this->assertArrayHasKey('next', $content['view']);
        // Note: hydra:prev may not exist for first page
    }

    #[Test]
    public function client_can_set_items_per_page(): void
    {
        $email = 'user_page_' . uniqid() . '@example.com';
        $password = 'T3st!P@ss#Api42';

        $this->registerUser($email, $password);
        $token = $this->getAccessToken($email, $password);

        $user = $this->userRepository->findByEmail($email);
        $this->assertNotNull($user);

        // Persist 9 events (+ 1 from getAccessToken auth = 10 total)
        for ($i = 0; $i < 9; $i++) {
            $event = SecurityEvent::loginSuccess(
                $user->getId(),
                null,
                sprintf('10.0.0.%d', $i),
                'agent-' . $i
            );
            $this->securityEventRepository->save($event, true);
        }

        $this->client->request(
            'GET',
            '/api/v1/security/events?itemsPerPage=3',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ]
        );
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('member', $content);
        $this->assertCount(3, $content['member']);
        $this->assertSame(10, $content['totalItems']);
    }

    #[Test]
    public function items_per_page_capped_at_100(): void
    {
        $email = 'user_cap_' . uniqid() . '@example.com';
        $password = 'T3st!P@ss#Api42';

        $this->registerUser($email, $password);
        $token = $this->getAccessToken($email, $password);

        $user = $this->userRepository->findByEmail($email);
        $this->assertNotNull($user);

        // Persist 149 events (+ 1 from getAccessToken auth = 150 total)
        for ($i = 0; $i < 149; $i++) {
            $event = SecurityEvent::loginSuccess(
                $user->getId(),
                null,
                sprintf('10.0.0.%d', $i),
                'agent-' . $i
            );
            $this->securityEventRepository->save($event, true);
        }

        $this->client->request(
            'GET',
            '/api/v1/security/events?itemsPerPage=200',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ]
        );
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('member', $content);
        $this->assertCount(100, $content['member']); // capped at 100
        $this->assertSame(150, $content['totalItems']);
    }
}