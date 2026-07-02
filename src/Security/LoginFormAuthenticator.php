<?php

namespace App\Security;

use App\Controller\SecurityController;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class LoginFormAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'app_login'
            && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $email = (string) $request->request->get('email', '');
        $password = (string) $request->request->get('password', '');

        $request->getSession()->set(SecurityController::LAST_EMAIL, $email);

        return new Passport(
            new UserBadge($email, function (string $userIdentifier): User {
                $user = $this->userRepository->findOneByEmail($userIdentifier);
                if (!$user) {
                    throw new CustomUserMessageAuthenticationException('auth.invalid_credentials');
                }

                return $user;
            }),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('login_form', (string) $request->request->get('csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $messageKey = 'auth.invalid_credentials';
        if ($exception instanceof CustomUserMessageAuthenticationException
            || $exception instanceof CustomUserMessageAccountStatusException) {
            $messageKey = $exception->getMessage();
        }

        $request->getSession()->getFlashBag()->add('warning', $messageKey);

        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
