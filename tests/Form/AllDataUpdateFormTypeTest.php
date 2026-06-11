<?php

namespace App\Tests\Form;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AllDataUpdateFormTypeTest extends WebTestCase
{
    public function testUpdatePagePrefillsRegionForIncident11(): void
    {
        $client = static::createClient();

        $user = static::getContainer()->get('doctrine')->getManager()
            ->getRepository(User::class)
            ->findOneBy(['email' => 'abdoulhafidayeva@gmail.com']);
        self::assertInstanceOf(User::class, $user);

        $client->loginUser($user);
        $crawler = $client->request('GET', '/update/data/11');

        self::assertResponseIsSuccessful();

        $selectedRegion = $crawler->filter('select[id$="_regions"] option[selected]');
        self::assertGreaterThan(0, $selectedRegion->count(), 'Region option should be selected');
        self::assertStringContainsString('AFRIQUE CENTRALE', $selectedRegion->text());

        $selectedPays = $crawler->filter('select[id$="_pays"] option[selected]');
        self::assertGreaterThan(0, $selectedPays->count(), 'Pays option should be selected');
        self::assertStringContainsString('CENTRAFRIQUE', $selectedPays->text());
    }
}
