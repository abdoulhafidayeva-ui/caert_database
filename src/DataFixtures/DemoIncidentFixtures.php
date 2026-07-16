<?php

namespace App\DataFixtures;

use App\Entity\AllData;
use App\Entity\Attaque;
use App\Entity\Cible;
use App\Entity\Espace;
use App\Entity\Materiaux;
use App\Entity\MaterielAttaque;
use App\Entity\MoyenAttaque;
use App\Entity\Pays;
use App\Entity\Perpetrateurs;
use App\Entity\User;
use App\Repository\AllDataRepository;
use App\Service\Incident\AllDataTotalsCalculator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Incidents de démonstration — groupe demo uniquement.
 */
class DemoIncidentFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    public const DEMO_MARKER = '[DEMO]';

    public static function getGroups(): array
    {
        return ['demo'];
    }

    public function __construct(
        private readonly AllDataTotalsCalculator $totalsCalculator,
        private readonly AllDataRepository $allDataRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $existing = $this->allDataRepository->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.remarque LIKE :marker')
            ->setParameter('marker', self::DEMO_MARKER . '%')
            ->getQuery()
            ->getSingleScalarResult();

        if ((int) $existing > 0) {
            return;
        }

        $superAdmin = $manager->getRepository(User::class)->findOneBy(['email' => AppFixtures::SUPER_ADMIN_EMAIL]);
        $staff = $manager->getRepository(User::class)->findOneBy(['email' => AppFixtures::STAFF_EMAIL]);
        $focal = $manager->getRepository(User::class)->findOneBy(['email' => AppFixtures::FOCAL_EMAIL]);

        if ($superAdmin === null) {
            return;
        }

        $entryUser = $staff ?? $superAdmin;
        $refs = $this->loadReferences($manager);

        $scenarios = [
            ['pays' => 'MALI', 'localite' => 'Gao', 'date' => '-4 months', 'published' => true, 'user' => $entryUser, 'mortCivil' => 3, 'mortMil' => 1, 'mortTer' => 2, 'details' => 'Attaque convoi sécurité'],
            ['pays' => 'NIGERIA', 'localite' => 'Maiduguri', 'date' => '-3 months', 'published' => true, 'user' => $entryUser, 'mortCivil' => 8, 'mortMil' => 2, 'mortTer' => 5, 'details' => 'Attentat marché'],
            ['pays' => 'BURKINA FASO', 'localite' => 'Ouahigouya', 'date' => '-2 months', 'published' => true, 'user' => $entryUser, 'mortCivil' => 4, 'mortMil' => 0, 'mortTer' => 1, 'details' => 'Embuscade route nationale'],
            ['pays' => 'NIGER', 'localite' => 'Tillabéri', 'date' => '-5 months', 'published' => true, 'user' => $entryUser, 'mortCivil' => 2, 'mortMil' => 3, 'mortTer' => 0, 'details' => 'Affrontement frontière'],
            ['pays' => 'KENYA', 'localite' => 'Mandera', 'date' => '-1 month', 'published' => true, 'user' => $entryUser, 'mortCivil' => 1, 'mortMil' => 1, 'mortTer' => 2, 'details' => 'Attaque poste frontière'],
            ['pays' => 'SENEGAL', 'localite' => 'Dakar', 'date' => '-2 weeks', 'published' => null, 'user' => $focal ?? $entryUser, 'mortCivil' => 0, 'mortMil' => 0, 'mortTer' => 0, 'details' => 'Incident hors Mali — en attente validation staff'],
            ['pays' => 'MALI', 'localite' => 'Mopti', 'date' => '-1 week', 'published' => null, 'user' => $focal ?? $entryUser, 'mortCivil' => 2, 'mortMil' => 0, 'mortTer' => 1, 'details' => 'Attaque village — saisie point focal Mali'],
            ['pays' => 'NIGERIA', 'localite' => 'Jos', 'date' => '-3 weeks', 'published' => null, 'user' => $focal ?? $entryUser, 'mortCivil' => 5, 'mortMil' => 1, 'mortTer' => 0, 'details' => 'Rapport point focal — lecture seule pour autres pays'],
            ['pays' => 'GHANA', 'localite' => 'Tamale', 'date' => '-6 months', 'published' => false, 'user' => $entryUser, 'mortCivil' => 1, 'mortMil' => 0, 'mortTer' => 0, 'details' => 'Données incohérentes', 'rejet' => 'Sources non vérifiables — resoumettre avec pièces jointes'],
            ['pays' => 'CAMEROUN', 'localite' => 'Maroua', 'date' => '-7 months', 'published' => false, 'user' => $entryUser, 'mortCivil' => 0, 'mortMil' => 2, 'mortTer' => 1, 'details' => 'Doublon signalé', 'rejet' => 'Doublon avec incident #12 — fusionner les rapports'],
        ];

        foreach ($scenarios as $scenario) {
            $pays = $manager->getRepository(Pays::class)->findOneBy(['libelle' => $scenario['pays']]);
            if ($pays === null) {
                continue;
            }

            $incident = new AllData();
            $incident->setDetails($scenario['details']);
            $incident->setLocalite($scenario['localite']);
            $incident->setDateAttaque(new \DateTime($scenario['date']));
            $incident->setPays($pays);
            $incident->setUser($scenario['user']);
            $incident->setAttaque($refs['attaque']);
            $incident->setCible($refs['cible']);
            $incident->setPerpetrateur($refs['perpetrateur']);
            $incident->setMoyenAttaque($refs['moyen']);
            $incident->setMaterielAttaque($refs['materielAttaque']);
            $incident->setMaterieaux($refs['materiaux']);
            $incident->setEspace($refs['espace']);
            $incident->setMortCivil($scenario['mortCivil']);
            $incident->setMortSecuriteMilitaire($scenario['mortMil']);
            $incident->setMortTerroriste($scenario['mortTer']);
            $incident->setDisparuCivil(0);
            $incident->setDisparuSecuriteMilitaire(0);
            $incident->setDisparuTerroriste(0);
            $incident->setBlesseCivil(1);
            $incident->setBlesseSecuriteMilitaire(0);
            $incident->setBlesseTerroriste(0);
            $incident->setOtages(0);
            $incident->setLiberes(0);
            $incident->setTerroristeArretes(0);
            $incident->setAutres('—');
            $incident->setRemarque(self::DEMO_MARKER . ' Donnée de démonstration CAERT');
            $incident->setIsPublished($scenario['published']);
            if (isset($scenario['rejet'])) {
                $incident->setObjetRejet($scenario['rejet']);
            }

            $this->totalsCalculator->applyTotals($incident);
            $manager->persist($incident);
        }

        $manager->flush();
    }

    /** @return array{attaque: Attaque, cible: Cible, perpetrateur: Perpetrateurs, moyen: MoyenAttaque, materielAttaque: MaterielAttaque, materiaux: Materiaux, espace: Espace} */
    private function loadReferences(ObjectManager $manager): array
    {
        $get = static fn (string $class) => $manager->getRepository($class)->findOneBy([]);

        return [
            'attaque' => $get(Attaque::class),
            'cible' => $get(Cible::class),
            'perpetrateur' => $get(Perpetrateurs::class),
            'moyen' => $get(MoyenAttaque::class),
            'materielAttaque' => $get(MaterielAttaque::class),
            'materiaux' => $get(Materiaux::class),
            'espace' => $get(Espace::class),
        ];
    }

    public function getDependencies(): array
    {
        return [
            AppFixtures::class,
            RegionFixtures::class,
            RegionSuiteFixtures::class,
            ReferenceDataFixtures::class,
        ];
    }
}
