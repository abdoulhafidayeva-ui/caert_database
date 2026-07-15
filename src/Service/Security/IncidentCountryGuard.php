<?php

namespace App\Service\Security;

use App\Entity\Pays;
use App\Entity\User;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class IncidentCountryGuard
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly UserDataScope $dataScope,
    ) {
    }

    public function isCountryRestricted(User $user): bool
    {
        return UserProfile::isCountryRestricted($user);
    }

    public function getAssignedCountry(User $user): ?Pays
    {
        return $user->getPays();
    }

    public function assertWriteAllowed(User $user, ?Pays $pays): void
    {
        if (UserProfile::hasFullDataAccess($user)) {
            return;
        }

        if (UserProfile::isStaff($user)) {
            if (!$this->dataScope->paysInAssignedRegion($user, $pays)) {
                throw new AccessDeniedException($this->translator->trans('security.region_restricted'));
            }

            return;
        }

        $this->assertCountryAllowed($user, $pays);
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
