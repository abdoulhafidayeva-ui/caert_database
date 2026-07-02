<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\Locale\LocaleResolver;
use App\Service\Locale\SupportedLocales;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LocaleResolver $localeResolver,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $locale = SupportedLocales::FR;

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $locale = $this->localeResolver->resolveForUser($user);
        } elseif ($request->hasSession()) {
            $sessionLocale = $request->getSession()->get('_locale');
            $locale = SupportedLocales::normalize(\is_string($sessionLocale) ? $sessionLocale : null);
        }

        $request->setLocale($locale);
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        if (!$user->getLocale()) {
            $user->setLocale($this->localeResolver->resolveFromCountry($user->getPays()));
            $this->em->flush();
        }

        $locale = $this->localeResolver->resolveForUser($user);
        $request = $this->requestStack->getCurrentRequest();
        if ($request && $request->hasSession()) {
            $request->getSession()->set('_locale', $locale);
        }
    }
}
