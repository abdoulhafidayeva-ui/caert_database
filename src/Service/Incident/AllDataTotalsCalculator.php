<?php

namespace App\Service\Incident;

use App\Entity\AllData;

final class AllDataTotalsCalculator
{
    public function applyTotals(AllData $incident): void
    {
        $incident->setTotalDeces(
            (int) $incident->getMortCivil()
            + (int) $incident->getMortSecuriteMilitaire()
            + (int) $incident->getMortTerroriste()
        );

        $incident->setTotalDisparus(
            (int) $incident->getDisparuCivil()
            + (int) $incident->getDisparuSecuriteMilitaire()
            + (int) $incident->getDisparuTerroriste()
        );

        $incident->setTotalBlesses(
            (int) $incident->getBlesseCivil()
            + (int) $incident->getBlesseSecuriteMilitaire()
            + (int) $incident->getBlesseTerroriste()
        );
    }
}
