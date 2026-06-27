<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\OAuthFlow;
use ApiPlatform\OpenApi\Model\OAuthFlows;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\Model\SecurityScheme;
use ApiPlatform\OpenApi\OpenApi;

final class OpenApiDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated
    ) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);

        // Add security schemes + response schemas to components
        $components = $openApi->getComponents();
        $securitySchemes = $components->getSecuritySchemes() ?? new \ArrayObject();
        $schemas = $components->getSchemas() ?? new \ArrayObject();

        $securitySchemes['OAuth2PasswordGrant'] = new SecurityScheme(
            type: 'oauth2',
            description: 'OAuth2 Password Grant flow',
            flows: new OAuthFlows(
                password: new OAuthFlow(
                    tokenUrl: '/oauth2/token',
                    scopes: new \ArrayObject(),
                ),
            ),
        );

        $securitySchemes['BearerAuth'] = new SecurityScheme(
            type: 'http',
            scheme: 'bearer',
            bearerFormat: 'JWT',
            description: 'Bearer JWT token',
        );

        $schemas['TokenResponse'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'access_token' => ['type' => 'string'],
                'refresh_token' => ['type' => 'string'],
                'expires_in' => ['type' => 'integer'],
                'token_type' => ['type' => 'string'],
                'scope' => ['type' => 'string', 'nullable' => true],
            ],
        ]);

        $schemas['ErrorResponse'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'error' => ['type' => 'string'],
                'error_description' => ['type' => 'string'],
            ],
        ]);

        $components = $components
            ->withSecuritySchemes($securitySchemes)
            ->withSchemas($schemas);

        // Add /oauth2/token path
        $openApi->getPaths()->addPath('/oauth2/token', new PathItem(
            post: new Operation(
                summary: 'Obtain access + refresh tokens (password grant)',
                tags: ['Auth'],
                requestBody: new RequestBody(
                    description: 'Login credentials',
                    content: new \ArrayObject([
                        'application/x-www-form-urlencoded' => new MediaType(
                            schema: new \ArrayObject([
                                'type' => 'object',
                                'required' => ['grant_type', 'client_id', 'client_secret', 'username', 'password'],
                                'properties' => new \ArrayObject([
                                    'grant_type' => new \ArrayObject([
                                        'type' => 'string',
                                        'enum' => ['password'],
                                        'default' => 'password',
                                    ]),
                                    'client_id' => new \ArrayObject([
                                        'type' => 'string',
                                        'minLength' => 1,
                                    ]),
                                    'client_secret' => new \ArrayObject([
                                        'type' => 'string',
                                        'minLength' => 1,
                                    ]),
                                    'username' => new \ArrayObject([
                                        'type' => 'string',
                                        'minLength' => 1,
                                    ]),
                                    'password' => new \ArrayObject([
                                        'type' => 'string',
                                        'minLength' => 1,
                                        'format' => 'password',
                                    ]),
                                    'scope' => new \ArrayObject([
                                        'type' => 'string',
                                        'description' => 'The scope of the access request',
                                    ]),
                                ]),
                            ]),
                        ),
                    ]),
                ),
                responses: [
                    '200' => new Response(
                        description: 'Token response',
                        content: new \ArrayObject([
                            'application/json' => new MediaType(
                                schema: new \ArrayObject(['$ref' => '#/components/schemas/TokenResponse'])
                            ),
                        ]),
                    ),
                    '400' => new Response(
                        description: 'Invalid request',
                        content: new \ArrayObject([
                            'application/json' => new MediaType(
                                schema: new \ArrayObject(['$ref' => '#/components/schemas/ErrorResponse'])
                            ),
                        ]),
                    ),
                    '401' => new Response(
                        description: 'Invalid credentials',
                        content: new \ArrayObject([
                            'application/json' => new MediaType(
                                schema: new \ArrayObject(['$ref' => '#/components/schemas/ErrorResponse'])
                            ),
                        ]),
                    ),
                ],
                security: [],
            )
        ));

        return $openApi->withComponents($components);
    }
}
