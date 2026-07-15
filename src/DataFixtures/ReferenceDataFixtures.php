<?php

namespace App\DataFixtures;

use App\Entity\Attaque;
use App\Entity\Cible;
use App\Entity\Espace;
use App\Entity\Materiaux;
use App\Entity\MaterielAttaque;
use App\Entity\MoyenAttaque;
use App\Entity\Perpetrateurs;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ReferenceDataFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        if ($manager->getRepository(Attaque::class)->count([]) > 0) {
            return;
        }

        /** @var User $admin */
        $admin = $manager->getRepository(User::class)->findOneBy(['email' => AppFixtures::SUPER_ADMIN_EMAIL]);
        if ($admin === null) {
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
                if (method_exists($entity, 'setUser')) {
                    $entity->setUser($admin);
                }
                $manager->persist($entity);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [AppFixtures::class];
    }
}
