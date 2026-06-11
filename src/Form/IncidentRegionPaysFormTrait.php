<?php

namespace App\Form;

use App\Entity\Pays;
use App\Entity\Region;

trait IncidentRegionPaysFormTrait
{
    private function resolveRegionFromSubmit(mixed $regionValue): ?Region
    {
        if ($regionValue === null || $regionValue === '') {
            return null;
        }

        $region = $this->regionRepository->find((int) $regionValue);
        if ($region === null) {
            return null;
        }

        return $this->regionRepository->findCanonicalByLibelle($region->getLibelle()) ?? $region;
    }

    private function resolveCanonicalPaysFromSubmit(mixed $paysValue, ?Region $region): ?Pays
    {
        if ($paysValue === null || $paysValue === '' || $region === null || $region->getLibelle() === null) {
            return null;
        }

        $pays = $this->paysRepository->find((int) $paysValue);
        if ($pays === null) {
            return null;
        }

        return $this->paysRepository->findCanonicalByLibelleForRegion(
            $pays->getLibelle(),
            $region->getLibelle()
        ) ?? $pays;
    }
}
