<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class ApiKeyAuthenticator extends AbstractAuthenticator
{
    public function __construct(private string $apiToken)
    {
    }

    public function supports(Request $request): ?bool
    {
        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $token = $request->headers->get('X-Api-Key');

        if ($token === null || $this->apiToken === '' || !hash_equals($this->apiToken, $token)) {
            throw new AuthenticationException('Invalid API token.');
        }

        return new SelfValidatingPassport(
            new UserBadge('api_user', fn (string $userIdentifier): User => new User($userIdentifier, '', ['ROLE_API']))
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => 'Unauthorized request. Provide a valid X-Api-Key header.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
