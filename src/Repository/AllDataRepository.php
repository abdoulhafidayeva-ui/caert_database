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
        $monthNum = date('m', strtotime($month));
        $year = date('Y', strtotime($month));
        $dateStart = new DateTime(sprintf('%s-%s-01 00:00:00', $year, str_pad($monthNum, 2, '0', STR_PAD_LEFT)));
        $dateEnd = new DateTime(sprintf('%s-%s-%02d 23:59:59', $year, str_pad($monthNum, 2, '0', STR_PAD_LEFT), $limitDay));

        $qb = $this->createQueryBuilder('a');
        $qb->innerJoin('a.pays', 'p')
            ->where('p.region = :region')
            ->andWhere('a.dateAttaque >= :start')
            ->andWhere('a.dateAttaque <= :end')
            ->andWhere('a.isPublished = :published')
            ->setParameter('region', $region)
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
        return $this->createQueryBuilder('a')
            ->andWhere('a.isPublished IS NULL')
            ->andWhere('a.objetRejet IS NULL OR a.objetRejet = \'\'')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
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
     * Published incidents with country for map (country-level aggregation).
     *
     * @return list<array{id: int, lat: float, lng: float, label: string, deaths: int}>
     */
    public function findMapPointsByCountry(): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('a.id, a.totalDeces, a.localite, p.libelle AS country')
            ->innerJoin('a.pays', 'p')
            ->andWhere('a.isPublished = true')
            ->andWhere('a.dateAttaque IS NOT NULL')
            ->getQuery()
            ->getArrayResult();

        $points = [];
        foreach ($rows as $row) {
            $coords = $this->countryCentroidProvider->resolve((string) ($row['country'] ?? ''));
            if ($coords === null) {
                continue;
            }
            $points[] = [
                'id' => (int) $row['id'],
                'lat' => $coords[0],
                'lng' => $coords[1],
                'label' => sprintf('%s — %s', $row['country'], $row['localite'] ?? ''),
                'deaths' => (int) $row['totalDeces'],
                'localite' => (string) ($row['localite'] ?? ''),
            ];
        }

        return $points;
    }

    /**
     * @return list<array{country: string, count: int, deaths: int, lat: float, lng: float}>
     */
    public function findMapAggregatesByCountry(): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('p.libelle AS country, COUNT(a.id) AS incidentCount, SUM(a.totalDeces) AS deathCount')
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
                'lat' => $coords[0],
                'lng' => $coords[1],
            ];
        }

        return $aggregates;
    }
}
