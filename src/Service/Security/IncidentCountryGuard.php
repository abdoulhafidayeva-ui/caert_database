<?php

namespace App\Service\Security;

use App\Entity\Pays;
use App\Entity\User;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class IncidentCountryGuard
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function isCountryRestricted(User $user): bool
    {
        $roles = $user->getRoles();

        return !in_array('ROLE_ADMIN', $roles, true)
            && !in_array('ROLE_SUPER_ADMIN', $roles, true);
    }

    public function getAssignedCountry(User $user): ?Pays
    {
        return $user->getPays();
    }

    public function assertCountryAllowed(User $user, ?Pays $pays): void
    {
        if (!$this->isCountryRestricted($user)) {
            return;
        }

        $assigned = $this->getAssignedCountry($user);
        if ($assigned === null) {
            throw new AccessDeniedException($this->translator->trans('security.no_country'));
        }

        if ($pays === null || $assigned->getId() !== $pays->getId()) {
            throw new AccessDeniedException($this->translator->trans('security.country_restricted'));
        }
    }
}
