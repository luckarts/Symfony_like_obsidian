<?php

declare(strict_types=1);

namespace App\User\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\User\Infrastructure\State\Processor\RegisterProcessor;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    shortName: 'Register',
    operations: [
        new Post(
            uriTemplate: '/users',
            processor: RegisterProcessor::class,
        ),
    ],
    routePrefix: '/api',
)]
class RegisterUserRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    public string $password = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $firstName = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $lastName = '';
}