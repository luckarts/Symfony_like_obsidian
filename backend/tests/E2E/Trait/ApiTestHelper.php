<?php

declare(strict_types=1);

namespace App\Tests\E2E\Trait;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use App\User\Application\Service\UserRegistrationService;
use App\User\Domain\Entity\User;
use App\User\Application\Command\RegisterUserCommand;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

trait ApiTestHelper
{
   protected KernelBrowser $client;
    
    private const DEFAULT_PASSWORD = 'T3st!P@ss#Api42';
    private const CLIENT_ID = 'test_client';
    private const CLIENT_SECRET = 'test_secret';
    private bool $oauthClientRegistered = false;

    /**
     * Cette méthode sera appelée manuellement ou via l'alias dans le setUp() du test
     */
    protected function setUpApiTestHelper(): void
    {
        if ($this->oauthClientRegistered) {
            return;
        }

        /** @var ClientManagerInterface $clientManager */
        $clientManager = static::getContainer()->get(ClientManagerInterface::class);

        $oauthClient = new Client('Test Client', self::CLIENT_ID, self::CLIENT_SECRET);
        $oauthClient->setGrants(new Grant('password'), new Grant('refresh_token'));
        $oauthClient->setScopes(new Scope('email'), new Scope('profile'));

        $clientManager->save($oauthClient);
        $this->oauthClientRegistered = true;
    }
    /*
        createUser + getOAuth2Token
        return token
    */
    protected function authenticate(
        string $email = 'test@example.com',
        string $password = self::DEFAULT_PASSWORD,
        string $firstName = 'John',
        string $lastName = 'Doe',
    ): string {
        $this->createUser($email, $password, $firstName, $lastName);
        return $this->getOAuth2Token($email, $password);
    }

    /*
        create User without http
    */
    protected function createUser(
        string $email = 'test@example.com',
        string $password = self::DEFAULT_PASSWORD,
        string $firstName = 'John',
        string $lastName = 'Doe',
    ): User {
        /** @var UserRegistrationService $registrationService */
        $registrationService = static::getContainer()->get(UserRegistrationService::class);

        $command = new RegisterUserCommand(
            email: $email,
            password: $password,
            firstName: $firstName,
            lastName: $lastName,
        );
        return $registrationService->register($command);
    }

    protected function getOAuth2Token(
        string $email = 'test@test.com',
        string $password = self::DEFAULT_PASSWORD,
    ): string {
        $this->setUpApiTestHelper();

        $this->client->request('POST', '/oauth2/token', [
            'grant_type'    => 'password',
            'client_id'     => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'username'      => $email,
            'password'      => $password,
            'scope'         => 'email',
        ]);

        $response = $this->client->getResponse();

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException(sprintf(
                'OAuth2 token request failed (%d): %s',
                $response->getStatusCode(),
                $response->getContent(),
            ));
        }

        /** @var array{access_token: string} $data */
        $data = json_decode($response->getContent(), true);
        return $data['access_token'];
    }
    /**
     * @param array<string, mixed> $data
     */
    protected function apiRequest(
        string $method,
        string $uri,
        ?string $token = null,
        array $data = [],
        string $contentType = 'application/ld+json',
    ): Response {
        $headers = ['CONTENT_TYPE' => $contentType];

        if ($token !== null) {
            $headers['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }

        $body = $data !== [] ? json_encode($data) : null;

        $this->client->request($method, $uri, [], [], $headers, $body);

        return $this->client->getResponse();
    }

}
