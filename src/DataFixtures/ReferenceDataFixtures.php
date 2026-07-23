<?php

namespace App\DataFixtures;

use App\Entity\Attaque;
use App\Entity\Cible;
use App\Entity\Espace;
use App\Entity\Materiaux;
use App\Entity\MaterielAttaque;
use App\Entity\MoyenAttaque;
use App\Entity\Perpetrateurs;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Référentiels métier (types d'attaque, cibles, etc.) — sans utilisateurs ni incidents.
 */
class ReferenceDataFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        if ($manager->getRepository(Attaque::class)->findOneBy([]) !== null) {
            return;
        }

        $references = [
            Attaque::class => ['Attaque armée', 'Attentat suicide', 'Enlèvement', 'Embuscade', 'Non renseigné'],
            Cible::class => [
                'Forces de sécurité',
                'Civils',
                'Infrastructure',
                'Convoi humanitaire',
                'Gouvernement',
                'Organisation internationale',
                'Affrontement entre groupes armés',
                'Non renseigné',
            ],
            Perpetrateurs::class => [
                'Groupe A',
                'Groupe B',
                'Non identifié',
                'Autres groupes',
                'ASWJ',
                'IS-Affiliates',
                'Al Shabaab',
                'Violent Extremist Group',
                'Boko Haram',
                'ISWAP',
                'ADF',
                'Mai-Mai',
                'JNIM',
                'ISGS',
                'Ansarul-Islam',
                'AQMI',
                'LRA',
                'Armed Separatists',
                'ISCAP',
                'Ambazonian Separatists',
            ],
            MoyenAttaque::class => [
                'Armes légères',
                'IED',
                'Véhicule',
                'Armes légères et IED',
                'Enlèvement',
                'Non renseigné',
            ],
            MaterielAttaque::class => ['Explosifs', 'Munitions', 'Engins artisanaux', 'Non renseigné'],
            Materiaux::class => ['Armes saisies', 'Munitions saisies', 'Aucun'],
            Espace::class => ['Urbain', 'Rural', 'Frontière', 'Non renseigné'],
        ];

        foreach ($references as $class => $labels) {
            foreach ($labels as $label) {
                $entity = new $class();
                $entity->setLibelle($label);
                $manager->persist($entity);
            }
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['reference', 'prod'];
    }
}
