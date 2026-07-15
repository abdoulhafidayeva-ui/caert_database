<?php

namespace App\Service\Security;

use App\Entity\AllData;
use App\Entity\Pays;
use App\Entity\Region;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\DataTableState;

/**
 * Périmètre de données par défaut (région + fenêtre temporelle) pour focal et staff.
 */
final class UserDataScope
{
    public const DEFAULT_MONTHS = 12;

    public function isRegionScoped(User $user): bool
    {
        return UserProfile::isRegionScoped($user);
    }

    public function getAssignedRegion(User $user): ?Region
    {
        $region = $user->getRegion();
        if ($region !== null) {
            return $region;
        }

        return $user->getPays()?->getRegion();
    }

    public function getAssignedRegionLabel(User $user): ?string
    {
        return $this->getAssignedRegion($user)?->getLibelle();
    }

    public function incidentInAssignedRegion(User $user, AllData $incident): bool
    {
        $assigned = $this->getAssignedRegion($user);
        $incidentRegion = $incident->getPays()?->getRegion();

        if ($assigned === null || $incidentRegion === null) {
            return false;
        }

        return $assigned->getId() === $incidentRegion->getId();
    }

    public function paysInAssignedRegion(User $user, ?Pays $pays): bool
    {
        if ($pays === null) {
            return false;
        }

        $assigned = $this->getAssignedRegion($user);
        $paysRegion = $pays->getRegion();

        if ($assigned === null || $paysRegion === null) {
            return false;
        }

        return $assigned->getId() === $paysRegion->getId();
    }

    /**
     * @return array{region: ?string, dateStart: string, dateEnd: string}|null
     */
    public function getDashboardDefaults(User $user): ?array
    {
        if (!$this->isRegionScoped($user)) {
            return null;
        }

        $regionLabel = $this->getAssignedRegionLabel($user);
        if ($regionLabel === null) {
            return null;
        }

        $end = new \DateTime('today');
        $start = (clone $end)->modify('-'.self::DEFAULT_MONTHS.' months');

        return [
            'region' => $regionLabel,
            'dateStart' => $start->format('d/m/Y'),
            'dateEnd' => $end->format('d/m/Y'),
        ];
    }

    /**
     * @return array{regionIds: list<int>, startMonth: string, endMonth: string}
     */
    public function getAnalyticsDefaults(User $user): array
    {
        $end = new \DateTime('today');
        $start = (clone $end)->modify('-'.self::DEFAULT_MONTHS.' months');

        $defaults = [
            'regionIds' => [],
            'startMonth' => $start->format('Y-m'),
            'endMonth' => $end->format('Y-m'),
        ];

        if (!$this->isRegionScoped($user)) {
            return $defaults;
        }

        $region = $this->getAssignedRegion($user);
        if ($region?->getId() !== null) {
            $defaults['regionIds'] = [(int) $region->getId()];
        }

        return $defaults;
    }

    public function getDefaultDateStart(): \DateTimeInterface
    {
        return (new \DateTime('today'))->modify('-'.self::DEFAULT_MONTHS.' months')->setTime(0, 0, 0);
    }

    public function applyDefaultListScope(QueryBuilder $qb, DataTableState $state, User $user): void
    {
        if (!$this->isRegionScoped($user)) {
            if (!$this->hasColumnSearch($state, 'dateAttaque')) {
                $qb->andWhere($qb->expr()->gte('a.dateAttaque', ':defaultDateStart'))
                    ->setParameter('defaultDateStart', $this->getDefaultDateStart());
            }

            return;
        }

        if (!$this->hasColumnSearch($state, 'region') && !$this->hasColumnSearch($state, 'pays')) {
            $regionLabel = $this->getAssignedRegionLabel($user);
            if ($regionLabel !== null) {
                $qb->andWhere('region.libelle = :defaultRegion')
                    ->setParameter('defaultRegion', $regionLabel);
            }
        }

        if (!$this->hasColumnSearch($state, 'dateAttaque')) {
            $qb->andWhere($qb->expr()->gte('a.dateAttaque', ':defaultDateStart'))
                ->setParameter('defaultDateStart', $this->getDefaultDateStart());
        }
    }

    public function applyRegionScopeToQueryBuilder(QueryBuilder $qb, User $user, string $paysAlias = 'scopePays', string $regionAlias = 'scopeRegion'): void
    {
        if (!$this->isRegionScoped($user)) {
            return;
        }

        $region = $this->getAssignedRegion($user);
        if ($region === null) {
            return;
        }

        $qb->innerJoin('a.pays', $paysAlias)
            ->innerJoin($paysAlias.'.region', $regionAlias)
            ->andWhere($regionAlias.' = :scopedRegion')
            ->setParameter('scopedRegion', $region);
    }

    private function hasColumnSearch(DataTableState $state, string $columnName): bool
    {
        foreach ($state->getSearchColumns() as $searchInfo) {
            $column = $searchInfo['column'];
            if ($column->getName() !== $columnName) {
                continue;
            }

            $value = $searchInfo['search'];
            if ($value === null || $value === '') {
                continue;
            }

            if ($columnName === 'dateAttaque' || $columnName === 'createdAt') {
                $trimmed = is_string($value) ? trim($value) : '';
                if ($trimmed === '' || $trimmed === '[]' || $trimmed === '{}') {
                    continue;
                }
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded) && empty($decoded['start']) && empty($decoded['end'])) {
                    continue;
                }

                return true;
            }

            $values = is_array($value) ? $value : json_decode((string) $value, true);
            if (is_array($values) && $values !== []) {
                return true;
            }
            if (is_string($value) && trim($value) !== '' && trim($value) !== '[]') {
                return true;
            }
        }

        return false;
    }
}
