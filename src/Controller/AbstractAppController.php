<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractAppController extends AbstractController
{
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'translator' => '?'.TranslatorInterface::class,
        ]);
    }

    /**
     * @param array<string, string|int|float> $parameters
     */
    protected function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->container->get('translator')->trans($id, $parameters, $domain, $locale);
    }
}
