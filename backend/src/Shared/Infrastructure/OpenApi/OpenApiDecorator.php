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

        // Add custom paths
        $paths = $openApi->getPaths();

        // /api/v1/auth/logout
        $paths->addPath('/api/v1/auth/logout', new PathItem(
            post: new Operation(
                summary: 'Log out (invalidate refresh token)',
                tags: ['Auth'],
                requestBody: new RequestBody(
                    description: 'Refresh token to invalidate',
                    content: new \ArrayObject([
                        'application/json' => new MediaType(
                            schema: new \ArrayObject([
                                'type' => 'object',
                                'required' => ['refresh_token'],
                                'properties' => new \ArrayObject([
                                    'refresh_token' => new \ArrayObject([
                                        'type' => 'string',
                                        'description' => 'Refresh token to invalidate',
                                    ]),
                                ]),
                            ]),
                        ),
                    ]),
                ),
                security: [new \ArrayObject(['BearerAuth' => []])],
                responses: [
                    '204' => new Response(description: 'No content'),
                    '400' => new Response(
                        description: 'Invalid or missing refresh token',
                        content: new \ArrayObject([
                            'application/json' => new MediaType(
                                schema: new \ArrayObject(['$ref' => '#/components/schemas/ErrorResponse'])
                            ),
                        ]),
                    ),
                    '403' => new Response(
                        description: 'Forbidden — token belongs to another user',
                        content: new \ArrayObject([
                            'application/json' => new MediaType(
                                schema: new \ArrayObject(['$ref' => '#/components/schemas/ErrorResponse'])
                            ),
                        ]),
                    ),
                ],
            )
        ));

        // /api/v1/auth/logout/all
        $paths->addPath('/api/v1/auth/logout/all', new PathItem(
            post: new Operation(
                summary: 'Log out from all devices',
                tags: ['Auth'],
                security: [new \ArrayObject(['BearerAuth' => []])],
                responses: [
                    '204' => new Response(description: 'No content'),
                    '400' => new Response(
                        description: 'Bad request',
                        content: new \ArrayObject([
                            'application/json' => new MediaType(
                                schema: new \ArrayObject(['$ref' => '#/components/schemas/ErrorResponse'])
                            ),
                        ]),
                    ),
                    '403' => new Response(
                        description: 'Forbidden',
                        content: new \ArrayObject([
                            'application/json' => new MediaType(
                                schema: new \ArrayObject(['$ref' => '#/components/schemas/ErrorResponse'])
                            ),
                        ]),
                    ),
                ],
            )
        ));

        // /api/v1/auth/sessions/{id}/revoke
        $paths->addPath('/api/v1/auth/sessions/{id}/revoke', new PathItem(
            delete: new Operation(
                summary: 'Revoke a specific session',
                tags: ['Auth'],
                security: [new \ArrayObject(['BearerAuth' => []])],
                parameters: [new \ArrayObject([
                    'name' => 'id',
                    'in' => 'path',
                    'required' => true,
                    'schema' => new \ArrayObject([
                        'type' => 'string',
                        'format' => 'uuid',
                    ]),
                ])],
                responses: [
                    '204' => new Response(description: 'No content'),
                    '403' => new Response(
                        description: 'Forbidden',
                        content: new \ArrayObject([
                            'application/json' => new MediaType(
                                schema: new \ArrayObject(['$ref' => '#/components/schemas/ErrorResponse'])
                            ),
                        ]),
                    ),
                    '404' => new Response(
                        description: 'Not found',
                        content: new \ArrayObject([
                            'application/json' => new MediaType(
                                schema: new \ArrayObject(['$ref' => '#/components/schemas/ErrorResponse'])
                            ),
                        ]),
                    ),
                ],
            )
        ));

        // /api/email/verify
        $paths->addPath('/api/email/verify', new PathItem(
            get: new Operation(
                summary: 'Verify email address',
                tags: ['Email'],
                parameters: [
                    new \ArrayObject([
                        'name' => 'token',
                        'in' => 'query',
                        'required' => true,
                        'schema' => new \ArrayObject([
                            'type' => 'string',
                        ]),
                    ]),
                    new \ArrayObject([
                        'name' => 'expires',
                        'in' => 'query',
                        'required' => true,
                        'schema' => new \ArrayObject([
                            'type' => 'string',
                        ]),
                    ]),
                    new \ArrayObject([
                        'name' => 'signature',
                        'in' => 'query',
                        'required' => true,
                        'schema' => new \ArrayObject([
                            'type' => 'string',
                        ]),
                    ]),
                ],
                responses: [
                    '204' => new Response(description: 'No content'),
                    '400' => new Response(
                        description: 'Bad request',
                        content: new \ArrayObject([
                            'application/json' => new MediaType(
                                schema: new \ArrayObject(['$ref' => '#/components/schemas/ErrorResponse'])
                            ),
                        ]),
                    ),
                ],
                security: [], // Public endpoint
            )
        ));

        // /api/email/resend-verification
        $paths->addPath('/api/email/resend-verification', new PathItem(
            post: new Operation(
                summary: 'Resend email verification',
                tags: ['Email'],
                security: [new \ArrayObject(['BearerAuth' => []])],
                responses: [
                    '204' => new Response(description: 'No content'),
                    '400' => new Response(
                        description: 'Bad request',
                        content: new \ArrayObject([
                            'application/json' => new MediaType(
                                schema: new \ArrayObject(['$ref' => '#/components/schemas/ErrorResponse'])
                            ),
                        ]),
                    ),
                ],
            )
        ));

        // /api/auth/password-reset/request
        $paths->addPath('/api/auth/password-reset/request', new PathItem(
            post: new Operation(
                summary: 'Request password reset',
                tags: ['PasswordReset'],
                requestBody: new RequestBody(
                    description: 'Email to send reset link',
                    content: new \ArrayObject([
                        'application/json' => new MediaType(
                            schema: new \ArrayObject([
                                'type' => 'object',
                                'required' => ['email'],
                                'properties' => new \ArrayObject([
                                    'email' => new \ArrayObject([
                                        'type' => 'string',
                                        'format' => 'email',
                                    ]),
                                ]),
                            ]),
                        ),
                    ]),
                ),
                responses: [
                    '202' => new Response(
                        description: 'Accepted',
                        content: new \ArrayObject([
                            'application/json' => new MediaType(
                                schema: new \ArrayObject([
                                    'type' => 'object',
                                    'properties' => new \ArrayObject([
                                        'message' => new \ArrayObject([
                                            'type' => 'string',
                                            'example' => 'If this email exists, a reset link has been sent',
                                        ]),
                                    ]),
                                ]),
                            ),
                        ]),
                    ),
                ],
                security: [], // Public endpoint
            )
        ));

        // /api/auth/password-reset/reset
        $paths->addPath('/api/auth/password-reset/reset', new PathItem(
            post: new Operation(
                summary: 'Reset password with token',
                tags: ['PasswordReset'],
                requestBody: new RequestBody(
                    description: 'Reset token and new password',
                    content: new \ArrayObject([
                        'application/json' => new MediaType(
                            schema: new \ArrayObject([
                                'type' => 'object',
                                'required' => ['token', 'password'],
                                'properties' => new \ArrayObject([
                                    'token' => new \ArrayObject([
                                        'type' => 'string',
                                        'description' => 'Password reset token received by email',
                                    ]),
                                    'password' => new \ArrayObject([
                                        'type' => 'string',
                                        'minLength' => 8,
                                        'description' => 'New password (min 8 characters)',
                                    ]),
                                ]),
                            ]),
                        ),
                    ]),
                ),
                responses: [
                    '204' => new Response(description: 'Password reset successfully'),
                    '400' => new Response(
                        description: 'Invalid or expired token',
                        content: new \ArrayObject([
                            'application/json' => new MediaType(
                                schema: new \ArrayObject(['$ref' => '#/components/schemas/ErrorResponse'])
                            ),
                        ]),
                    ),
                    '422' => new Response(
                        description: 'Password policy violation',
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

        $openApi = $openApi->withPaths($paths);

        return $openApi;
    }
}
