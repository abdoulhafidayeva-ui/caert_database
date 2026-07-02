<?php

namespace App\Repository;

use App\Entity\AllData;
use App\Entity\Region;
use App\Service\Gis\CountryCentroidProvider;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AllData>
 */
class AllDataRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly CountryCentroidProvider $countryCentroidProvider,
    ) {
        parent::__construct($registry, AllData::class);
    }

    public function getCountTotalAttackInjuredDeath(string $type): int|float|null
    {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.isPublished = :published')->setParameter('published', true);

        if ($type === 'attack') {
            $qb->select('COUNT(a.id) as total');
        } elseif ($type === 'injured') {
            $qb->select('SUM(a.totalBlesses) as total');
        } else {
            $qb->select('SUM(a.totalDeces) as total');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getCountTotalTargetsAttacks(string $type): int|float|null
    {
        $qb = $this->createQueryBuilder('a');
        $select = match ($type) {
            'civil' => 'SUM(a.blesseCivil)',
            'securiteMilitaire' => 'SUM(a.blesseSecuriteMilitaire)',
            default => 'SUM(a.blesseTerroriste)',
        };

        return $qb->select($select . ' as total')
            ->where('a.isPublished = :published')
            ->setParameter('published', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getCountTotalDeathsperCategory(string $type): int|float|null
    {
        $qb = $this->createQueryBuilder('a');
        $select = match ($type) {
            'civil' => 'SUM(a.mortCivil)',
            'securiteMilitaire' => 'SUM(a.mortSecuriteMilitaire)',
            default => 'SUM(a.mortTerroriste)',
        };

        return $qb->select($select . ' as total')
            ->where('a.isPublished = :published')
            ->setParameter('published', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getCountTotalForSearch(string $type, Region $region, string $month, int $limitDay): int|float|null
    {
        $monthStart = \DateTimeImmutable::createFromFormat('Y-m-d', $month.'-01');
        if ($monthStart === false) {
            return 0;
        }

        $dateStart = DateTime::createFromImmutable($monthStart->setTime(0, 0, 0));
        $dateEnd = DateTime::createFromImmutable(
            $monthStart->modify('last day of this month')->setTime(23, 59, 59)
        );

        $regionLibelle = $region->getLibelle();
        if ($regionLibelle === null || $regionLibelle === '') {
            return 0;
        }

        $qb = $this->createQueryBuilder('a');
        $qb->innerJoin('a.pays', 'p')
            ->innerJoin('p.region', 'r')
            ->where('r.libelle = :regionLibelle')
            ->andWhere('a.dateAttaque >= :start')
            ->andWhere('a.dateAttaque <= :end')
            ->andWhere('a.isPublished = :published')
            ->setParameter('regionLibelle', $regionLibelle)
            ->setParameter('start', $dateStart)
            ->setParameter('end', $dateEnd)
            ->setParameter('published', true);

        $normalizedType = strtolower($type);
        if ($normalizedType === 'attaque') {
            $qb->select('COUNT(a.id) as total');
        } elseif ($normalizedType === 'perpetrateurs') {
            $qb->select('SUM(a.mortTerroriste) as total');
        } elseif ($normalizedType === 'civil') {
            $qb->select('SUM(a.mortCivil) as total');
        } elseif ($normalizedType === 'securitemilitaire') {
            $qb->select('SUM(a.mortSecuriteMilitaire) as total');
        } else {
            $qb->select('SUM(a.mortTerroriste) as total');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return AllData[]
     */
    public function findPendingReview(int $limit = 50): array
    {
        return $this->createPendingReviewQueryBuilder()
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function createPendingReviewQueryBuilder(): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.isPublished IS NULL')
            ->andWhere('a.objetRejet IS NULL OR a.objetRejet = \'\'')
            ->orderBy('a.createdAt', 'DESC');
    }

    public function countPendingReview(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.isPublished IS NULL')
            ->andWhere('a.objetRejet IS NULL OR a.objetRejet = \'\'')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array{published: int, pending: int, rejected: int, deaths: int, injured: int, total: int}
     */
    public function getExecutiveSummary(): array
    {
        $row = $this->createQueryBuilder('a')
            ->select(
                'COUNT(a.id) AS total',
                'SUM(CASE WHEN a.isPublished = true THEN 1 ELSE 0 END) AS published',
                'SUM(CASE WHEN a.isPublished IS NULL AND (a.objetRejet IS NULL OR a.objetRejet = \'\') THEN 1 ELSE 0 END) AS pending',
                'SUM(CASE WHEN a.isPublished = false AND a.objetRejet IS NOT NULL AND a.objetRejet <> \'\' THEN 1 ELSE 0 END) AS rejected',
                'SUM(CASE WHEN a.isPublished = true THEN a.totalDeces ELSE 0 END) AS deaths',
                'SUM(CASE WHEN a.isPublished = true THEN a.totalBlesses ELSE 0 END) AS injured'
            )
            ->getQuery()
            ->getSingleResult();

        return [
            'published' => (int) ($row['published'] ?? 0),
            'pending' => (int) ($row['pending'] ?? 0),
            'rejected' => (int) ($row['rejected'] ?? 0),
            'deaths' => (int) ($row['deaths'] ?? 0),
            'injured' => (int) ($row['injured'] ?? 0),
            'total' => (int) ($row['total'] ?? 0),
        ];
    }

    /**
     * Top countries by published incident volume (executive dashboard).
     *
     * @return list<array{country: string, incidentCount: int, deaths: int}>
     */
    public function getTopCountriesByIncidents(int $limit = 10): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('p.libelle AS country, COUNT(a.id) AS incidentCount, SUM(a.totalDeces) AS deaths')
            ->innerJoin('a.pays', 'p')
            ->andWhere('a.isPublished = true')
            ->groupBy('p.libelle')
            ->orderBy('incidentCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): array => [
            'country' => (string) $row['country'],
            'incidentCount' => (int) $row['incidentCount'],
            'deaths' => (int) ($row['deaths'] ?? 0),
        ], $rows);
    }

    /**
     * Published incidents for map detail panels (optional filter by country label).
     *
     * @return list<array<string, mixed>>
     */
    public function findMapIncidentDetails(?string $country = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select(
                'a.id',
                'a.dateAttaque',
                'a.totalDeces',
                'a.totalBlesses',
                'a.localite',
                'p.libelle AS country',
                'atk.libelle AS attackType',
                'cib.libelle AS target',
                'per.libelle AS perpetrator'
            )
            ->innerJoin('a.pays', 'p')
            ->leftJoin('a.attaque', 'atk')
            ->leftJoin('a.cible', 'cib')
            ->leftJoin('a.perpetrateur', 'per')
            ->andWhere('a.isPublished = true')
            ->andWhere('a.dateAttaque IS NOT NULL')
            ->orderBy('a.dateAttaque', 'DESC');

        if ($country !== null && $country !== '') {
            $qb->andWhere('p.libelle = :country')
                ->setParameter('country', $country);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @return list<array{id: int, lat: float, lng: float, label: string, deaths: int, localite: string, country: string}>
     */
    public function findMapPointsByCountry(): array
    {
        $points = [];
        foreach ($this->findMapIncidentDetails() as $row) {
            $country = (string) ($row['country'] ?? '');
            $coords = $this->countryCentroidProvider->resolve($country);
            if ($coords === null) {
                continue;
            }
            $points[] = [
                'id' => (int) $row['id'],
                'lat' => $coords[0],
                'lng' => $coords[1],
                'label' => sprintf('%s — %s', $country, $row['localite'] ?? ''),
                'deaths' => (int) $row['totalDeces'],
                'localite' => (string) ($row['localite'] ?? ''),
                'country' => $country,
            ];
        }

        return $points;
    }

    /**
     * @return list<array{country: string, count: int, deaths: int, injured: int, lat: float, lng: float}>
     */
    public function findMapAggregatesByCountry(): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select(
                'p.libelle AS country',
                'COUNT(a.id) AS incidentCount',
                'SUM(a.totalDeces) AS deathCount',
                'SUM(a.totalBlesses) AS injuredCount'
            )
            ->innerJoin('a.pays', 'p')
            ->andWhere('a.isPublished = true')
            ->andWhere('a.dateAttaque IS NOT NULL')
            ->groupBy('p.libelle')
            ->getQuery()
            ->getArrayResult();

        $aggregates = [];
        foreach ($rows as $row) {
            $coords = $this->countryCentroidProvider->resolve((string) $row['country']);
            if ($coords === null) {
                continue;
            }
            $aggregates[] = [
                'country' => (string) $row['country'],
                'count' => (int) $row['incidentCount'],
                'deaths' => (int) $row['deathCount'],
                'injured' => (int) ($row['injuredCount'] ?? 0),
                'lat' => $coords[0],
                'lng' => $coords[1],
            ];
        }

        return $aggregates;
    }
}
