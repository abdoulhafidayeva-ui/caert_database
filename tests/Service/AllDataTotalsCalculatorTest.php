<?php

namespace App\Tests\Service;

use App\Entity\AllData;
use App\Service\Incident\AllDataTotalsCalculator;
use PHPUnit\Framework\TestCase;

class AllDataTotalsCalculatorTest extends TestCase
{
    public function testApplyTotals(): void
    {
        $incident = new AllData();
        $incident->setMortCivil(1);
        $incident->setMortSecuriteMilitaire(2);
        $incident->setMortTerroriste(3);
        $incident->setDisparuCivil(1);
        $incident->setDisparuSecuriteMilitaire(0);
        $incident->setDisparuTerroriste(2);
        $incident->setBlesseCivil(4);
        $incident->setBlesseSecuriteMilitaire(5);
        $incident->setBlesseTerroriste(6);

        (new AllDataTotalsCalculator())->applyTotals($incident);

        self::assertSame(6, $incident->getTotalDeces());
        self::assertSame(3, $incident->getTotalDisparus());
        self::assertSame(15, $incident->getTotalBlesses());
    }
}
