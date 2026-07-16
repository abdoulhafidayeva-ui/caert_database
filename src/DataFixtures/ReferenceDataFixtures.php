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
        if ($manager->getRepository(Attaque::class)->count([]) > 0) {
            return;
        }

        $references = [
            Attaque::class => ['Attaque armée', 'Attentat suicide', 'Enlèvement', 'Embuscade'],
            Cible::class => ['Forces de sécurité', 'Civils', 'Infrastructure', 'Convoi humanitaire'],
            Perpetrateurs::class => ['Groupe A', 'Groupe B', 'Non identifié'],
            MoyenAttaque::class => ['Armes légères', 'IED', 'Véhicule'],
            MaterielAttaque::class => ['Explosifs', 'Munitions', 'Engins artisanaux'],
            Materiaux::class => ['Armes saisies', 'Munitions saisies', 'Aucun'],
            Espace::class => ['Urbain', 'Rural', 'Frontière'],
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
