<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\Locale\SupportedLocales;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LocaleController extends AbstractController
{
    #[Route(path: '/locale/{locale}', name: 'app_locale_switch', methods: ['POST'])]
    public function switch(string $locale, Request $request, EntityManagerInterface $em): Response
    {
        if (!\in_array($locale, SupportedLocales::ACTIVE, true)) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('switch_locale', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'flash.invalid_csrf');

            return $this->redirectBack($request);
        }

        $request->getSession()->set('_locale', $locale);

        $user = $this->getUser();
        if ($user instanceof User) {
            $user->setLocale($locale);
            $em->flush();
        }

        return $this->redirectBack($request);
    }

    private function redirectBack(Request $request): Response
    {
        $referer = $request->headers->get('Referer');

        return $this->redirect($referer ?: $this->generateUrl('app_home'));
    }
}
