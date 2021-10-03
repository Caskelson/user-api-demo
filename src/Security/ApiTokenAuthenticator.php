<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-AUTH-TOKEN');
    }

    public function authenticate(Request $request): PassportInterface
    {
        $email = $request->headers->get('Email');
        $apiToken = $request->headers->get('X-AUTH-TOKEN');

        if (null === $apiToken) {
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        if (null === $email) {
            throw new CustomUserMessageAuthenticationException('No email provided');
        }

        $passport = new Passport(
            new UserBadge($email), 
            new CustomCredentials(
                    function ($credentials, User $user) {
                        foreach ($user->getApiTokens()->getValues() as $token) {
                            if ($credentials === $token->getToken())
                                return true;
                        }
                        return false;
                    },
                    $apiToken)
        );

        return $passport;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'type' => 'authentication_error',
            'title' => 'There was an authentication error',
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}
