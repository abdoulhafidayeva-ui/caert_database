<?php

namespace App\Repository;

use App\Entity\AllData;
use App\Entity\Region;
use App\Entity\User;
use App\Service\Gis\CountryCentroidProvider;
use App\Service\Security\UserDataScope;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    public function createPublishedAnalyticsQueryBuilder(?User $user = null, ?UserDataScope $dataScope = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.isPublished = :published')
            ->setParameter('published', true);

        if ($user instanceof User && $dataScope instanceof UserDataScope && $dataScope->isRegionScoped($user)) {
            $dataScope->applyRegionScopeToQueryBuilder($qb, $user);
        }

        return $qb;
    }

    /**
     * @param array{mode: string, selectedYear: ?int}|null $period
     */
    public function getCountTotalAttackInjuredDeath(
        string $type,
        ?User $user = null,
        ?UserDataScope $dataScope = null,
        ?array $period = null,
    ): int|float|null {
        $qb = $this->createPublishedAnalyticsQueryBuilder($user, $dataScope);
        $this->applySummaryPeriodFilter($qb, $period, $dataScope);

        if ($type === 'attack') {
            $qb->select('COUNT(a.id) as total');
        } elseif ($type === 'injured') {
            $qb->select('COALESCE(SUM(a.totalBlesses), 0) as total');
        } else {
            $qb->select('COALESCE(SUM(a.totalDeces), 0) as total');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Top cibles (référentiel) par nombre d’incidents publiés — aligné sur les totaux métier.
     *
     * @param array{mode: string, selectedYear: ?int}|null $period
     *
     * @return list<array{label: string, count: int}>
     */
    public function getTopTargetsByIncidents(
        ?User $user = null,
        ?UserDataScope $dataScope = null,
        int $limit = 6,
        ?array $period = null,
    ): array {
        $qb = $this->createPublishedAnalyticsQueryBuilder($user, $dataScope)
            ->select('c.libelle AS label, COUNT(a.id) AS incidentCount')
            ->innerJoin('a.cible', 'c')
            ->groupBy('c.libelle')
            ->orderBy('incidentCount', 'DESC')
            ->setMaxResults(max(1, $limit));

        $this->applySummaryPeriodFilter($qb, $period, $dataScope);

        $rows = $qb->getQuery()->getArrayResult();

        return array_map(static fn (array $row): array => [
            'label' => (string) $row['label'],
            'count' => (int) $row['incidentCount'],
        ], $rows);
    }

    /** @deprecated Conservé pour compat ; préférer getTopTargetsByIncidents(). */
    public function getCountTotalTargetsAttacks(string $type, ?User $user = null, ?UserDataScope $dataScope = null): int|float|null
    {
        $qb = $this->createPublishedAnalyticsQueryBuilder($user, $dataScope);
        $select = match ($type) {
            'civil' => 'COALESCE(SUM(a.blesseCivil), 0)',
            'securiteMilitaire' => 'COALESCE(SUM(a.blesseSecuriteMilitaire), 0)',
            default => 'COALESCE(SUM(a.blesseTerroriste), 0)',
        };

        return $qb->select($select.' as total')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param array{mode: string, selectedYear: ?int}|null $period
     */
    private function applySummaryPeriodFilter(QueryBuilder $qb, ?array $period, ?UserDataScope $dataScope): void
    {
        if ($period === null) {
            return;
        }

        $mode = (string) ($period['mode'] ?? '');

        if ($mode === 'last12') {
            $start = $dataScope?->getDefaultDateStart() ?? (new \DateTime('today'))->modify('-12 months')->setTime(0, 0, 0);
            $qb->andWhere('a.dateAttaque IS NOT NULL')
                ->andWhere('a.dateAttaque >= :summaryDateStart')
                ->setParameter('summaryDateStart', $start);

            return;
        }

        if ($mode === 'year') {
            $this->applyAttackYearFilter($qb, isset($period['selectedYear']) ? (int) $period['selectedYear'] : null);
        }
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

    public function getCountTotalForSearch(string $type, Region $region, string $month, int $limitDay = 31): int|float|null
    {
        return $this->getCountTotalForSearchRange($type, $region, $month, $month);
    }

    /**
     * Agrège les incidents publiés pour une région sur une plage de mois inclusive (Y-m → Y-m).
     */
    public function getCountTotalForSearchRange(
        string $type,
        Region $region,
        string $startMonth,
        string $endMonth,
    ): int|float|null {
        $range = $this->resolveMonthRange($startMonth, $endMonth);
        if ($range === null) {
            return 0;
        }

        [$dateStart, $dateEnd] = $range;

        $qb = $this->createQueryBuilder('a');
        $qb->innerJoin('a.pays', 'p')
            ->innerJoin('p.region', 'r')
            ->andWhere('r.id = :regionId')
            ->andWhere('a.dateAttaque >= :start')
            ->andWhere('a.dateAttaque <= :end')
            ->andWhere('a.isPublished = :published')
            ->setParameter('regionId', $region->getId())
            ->setParameter('start', $dateStart)
            ->setParameter('end', $dateEnd)
            ->setParameter('published', true);

        $this->applyAnalyticsIndicatorSelect($qb, $type);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Incidents non publiés (en attente) sur la même plage / région — pour message d'aide.
     */
    public function countPendingForSearchRange(Region $region, string $startMonth, string $endMonth): int
    {
        $range = $this->resolveMonthRange($startMonth, $endMonth);
        if ($range === null) {
            return 0;
        }

        [$dateStart, $dateEnd] = $range;

        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->innerJoin('a.pays', 'p')
            ->innerJoin('p.region', 'r')
            ->andWhere('r.id = :regionId')
            ->andWhere('a.dateAttaque >= :start')
            ->andWhere('a.dateAttaque <= :end')
            ->andWhere('a.isPublished IS NULL OR a.isPublished = false')
            ->andWhere('a.objetRejet IS NULL OR a.objetRejet = \'\'')
            ->setParameter('regionId', $region->getId())
            ->setParameter('start', $dateStart)
            ->setParameter('end', $dateEnd)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array{0: DateTime, 1: DateTime}|null
     */
    private function resolveMonthRange(string $startMonth, string $endMonth): ?array
    {
        $start = \DateTimeImmutable::createFromFormat('Y-m-d', $startMonth.'-01');
        $end = \DateTimeImmutable::createFromFormat('Y-m-d', $endMonth.'-01');
        if ($start === false || $end === false) {
            return null;
        }

        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }

        return [
            DateTime::createFromImmutable($start->setTime(0, 0, 0)),
            DateTime::createFromImmutable($end->modify('last day of this month')->setTime(23, 59, 59)),
        ];
    }

    private function applyAnalyticsIndicatorSelect(QueryBuilder $qb, string $type): void
    {
        $normalizedType = strtolower($type);

        // Indicateurs métier = totaux (alignés sur l’historique AUCTC et les KPI).
        if ($normalizedType === 'attaque') {
            $qb->select('COUNT(a.id) as total');
        } elseif ($normalizedType === 'deces' || $normalizedType === 'perpetrateurs' || $normalizedType === 'civil') {
            $qb->select('COALESCE(SUM(a.totalDeces), 0) as total');
        } elseif ($normalizedType === 'blesses') {
            $qb->select('COALESCE(SUM(a.totalBlesses), 0) as total');
        } else {
            $qb->select('COUNT(a.id) as total');
        }
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

    public function createPendingReviewQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.isPublished IS NULL')
            ->andWhere('a.objetRejet IS NULL OR a.objetRejet = \'\'')
            ->orderBy('a.createdAt', 'DESC');
    }

    public function countPendingReview(?User $user = null, ?UserDataScope $dataScope = null): int
    {
        $qb = $this->createPendingReviewQueryBuilder();
        if ($user instanceof User && $dataScope instanceof UserDataScope) {
            $dataScope->applyRegionScopeToQueryBuilder($qb, $user);
        }

        return (int) $qb->select('COUNT(a.id)')->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array{published: int, pending: int, rejected: int, deaths: int, injured: int, total: int}
     */
    public function getExecutiveSummary(?int $year = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select(
                'COUNT(a.id) AS total',
                'SUM(CASE WHEN a.isPublished = true THEN 1 ELSE 0 END) AS published',
                'SUM(CASE WHEN a.isPublished IS NULL AND (a.objetRejet IS NULL OR a.objetRejet = \'\') THEN 1 ELSE 0 END) AS pending',
                'SUM(CASE WHEN a.isPublished = false AND a.objetRejet IS NOT NULL AND a.objetRejet <> \'\' THEN 1 ELSE 0 END) AS rejected',
                'SUM(CASE WHEN a.isPublished = true THEN a.totalDeces ELSE 0 END) AS deaths',
                'SUM(CASE WHEN a.isPublished = true THEN a.totalBlesses ELSE 0 END) AS injured'
            );

        $this->applyAttackYearFilter($qb, $year);

        $row = $qb->getQuery()->getSingleResult();

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
    public function getTopCountriesByIncidents(int $limit = 10, ?int $year = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('p.libelle AS country, COUNT(a.id) AS incidentCount, SUM(a.totalDeces) AS deaths')
            ->innerJoin('a.pays', 'p')
            ->andWhere('a.isPublished = true')
            ->groupBy('p.libelle')
            ->orderBy('incidentCount', 'DESC')
            ->setMaxResults($limit);

        $this->applyAttackYearFilter($qb, $year);

        $rows = $qb->getQuery()->getArrayResult();

        return array_map(static fn (array $row): array => [
            'country' => (string) $row['country'],
            'incidentCount' => (int) $row['incidentCount'],
            'deaths' => (int) ($row['deaths'] ?? 0),
        ], $rows);
    }

    /**
     * Années distinctes présentes sur dateAttaque (desc), pour le filtre synthèse.
     *
     * @return list<int>
     */
    public function findDistinctAttackYears(): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchFirstColumn(
            'SELECT DISTINCT YEAR(date_attaque) FROM all_data WHERE date_attaque IS NOT NULL ORDER BY 1 DESC'
        );

        $years = [];
        foreach ($rows as $year) {
            $year = (int) $year;
            if ($year > 0) {
                $years[] = $year;
            }
        }

        return $years;
    }

    private function applyAttackYearFilter(QueryBuilder $qb, ?int $year): void
    {
        if ($year === null) {
            return;
        }

        $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', sprintf('%04d-01-01 00:00:00', $year));
        $end = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', sprintf('%04d-12-31 23:59:59', $year));
        if ($start === false || $end === false) {
            return;
        }

        $qb->andWhere('a.dateAttaque IS NOT NULL')
            ->andWhere('a.dateAttaque >= :attackYearStart')
            ->andWhere('a.dateAttaque <= :attackYearEnd')
            ->setParameter('attackYearStart', \DateTime::createFromImmutable($start))
            ->setParameter('attackYearEnd', \DateTime::createFromImmutable($end));
    }

    /**
     * Published incidents for map detail panels (optional filter by country label / year).
     *
     * @return list<array<string, mixed>>
     */
    public function findMapIncidentDetails(?string $country = null, ?int $year = null): array
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

        $this->applyAttackYearFilter($qb, $year);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @return list<array{id: int, lat: float, lng: float, label: string, deaths: int, localite: string, country: string}>
     */
    public function findMapPointsByCountry(?int $year = null): array
    {
        $points = [];
        foreach ($this->findMapIncidentDetails(null, $year) as $row) {
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
    public function findMapAggregatesByCountry(?User $user = null, ?UserDataScope $dataScope = null, ?int $year = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select(
                'p.libelle AS country',
                'COUNT(a.id) AS incidentCount',
                'SUM(a.totalDeces) AS deathCount',
                'SUM(a.totalBlesses) AS injuredCount'
            )
            ->innerJoin('a.pays', 'p')
            ->andWhere('a.isPublished = true')
            ->andWhere('a.dateAttaque IS NOT NULL')
            ->groupBy('p.libelle');

        $this->applyAttackYearFilter($qb, $year);

        if ($user instanceof User && $dataScope instanceof UserDataScope && $dataScope->isRegionScoped($user)) {
            $dataScope->applyRegionScopeToQueryBuilder($qb, $user);
        }

        $rows = $qb->getQuery()->getArrayResult();

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
