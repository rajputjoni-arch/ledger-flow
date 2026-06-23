<?php

namespace App\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class ApiKeyAuthenticator extends AbstractAuthenticator
{
    public function __construct(private string $apiToken, private LoggerInterface $logger)
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
            $this->logger->warning('API key authentication failed.', [
                'client_ip' => $request->getClientIp(),
                'has_token' => $token !== null,
            ]);

            throw new AuthenticationException('Invalid API token.');
        }

        $this->logger->info('API key authentication succeeded.', [
            'client_ip' => $request->getClientIp(),
            'user' => 'api_user',
        ]);

        return new SelfValidatingPassport(
            new UserBadge('api_user', fn (string $userIdentifier): InMemoryUser => new InMemoryUser($userIdentifier, '', ['ROLE_API']))
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
