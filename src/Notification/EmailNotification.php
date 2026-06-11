<?php

namespace App\Notification;

use App\Entity\User;
use App\Service\Parametrage;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailNotification
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Parametrage $parametrage,
        private readonly string $defaultUri,
    ) {
    }

    public function notify(User $user): void
    {
        $this->send(
            $user,
            'notification/email.html.twig',
            'Création de votre compte CAERT',
            $this->buildUrl('app_add_password', ['token' => $user->getToken()]),
        );
    }

    public function verify(User $user): void
    {
        $this->send(
            $user,
            'notification/verify.html.twig',
            'Vérification de votre compte CAERT',
            $this->buildUrl('app_account_verif', ['token' => $user->getToken()]),
        );
    }

    public function sendCode(User $user): void
    {
        $this->send(
            $user,
            'notification/code.html.twig',
            'Réinitialisation de votre mot de passe CAERT',
            null,
        );
    }

    public function sendNewPassword(User $user, string $plainPassword): void
    {
        $this->send(
            $user,
            'notification/new_password.html.twig',
            'Votre nouveau mot de passe CAERT',
            null,
            [
                'plainPassword' => $plainPassword,
                'loginUrl' => $this->buildUrl('app_login', []),
            ],
        );
    }

    private function send(User $user, string $template, string $subject, ?string $actionUrl, array $extraContext = []): void
    {
        $appParam = $this->parametrage->object();
        $appName = $appParam?->getName() ?? 'CAERT';
        $fromEmail = $appParam?->getEmail() ?? 'noreply@caert.local';

        $email = (new TemplatedEmail())
            ->from(new Address($fromEmail, $appName))
            ->to($user->getEmail())
            ->subject($subject)
            ->htmlTemplate($template)
            ->context(array_merge([
                'user' => $user,
                'appName' => $appName,
                'actionUrl' => $actionUrl,
            ], $extraContext));

        $this->mailer->send($email);
    }

    private function buildUrl(string $route, array $parameters): string
    {
        $appParam = $this->parametrage->object();
        $baseUrl = $appParam?->getSiteUrl() ?: $this->defaultUri;

        $this->urlGenerator->getContext()->setHost(
            parse_url($baseUrl, PHP_URL_HOST) ?: '127.0.0.1'
        );
        $this->urlGenerator->getContext()->setScheme(
            parse_url($baseUrl, PHP_URL_SCHEME) ?: 'http'
        );
        $port = parse_url($baseUrl, PHP_URL_PORT);
        if ($port) {
            $this->urlGenerator->getContext()->setHttpPort((int) $port);
            $this->urlGenerator->getContext()->setHttpsPort((int) $port);
        }

        return $this->urlGenerator->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
