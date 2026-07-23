<?php

namespace App\Service\Incident;

/**
 * Résolution du filtre Année (tableau de bord / synthèse / carte / analyses) :
 * - défaut tableau de bord, synthèse, carte : année courante
 * - défaut graphiques résumé analyses : 12 derniers mois (last12)
 * - "all" = toutes les années
 */
final class AttackYearSelection
{
    public const MODE_YEAR = 'year';
    public const MODE_ALL = 'all';
    public const MODE_LAST12 = 'last12';

    /**
     * @return array{selectedYear: ?int, isAllYears: bool, currentYear: int}
     */
    public static function fromQueryParam(mixed $yearParam): array
    {
        $currentYear = (int) (new \DateTimeImmutable('today'))->format('Y');

        if ($yearParam === null) {
            return [
                'selectedYear' => $currentYear,
                'isAllYears' => false,
                'currentYear' => $currentYear,
            ];
        }

        $raw = is_string($yearParam) || is_numeric($yearParam) ? trim((string) $yearParam) : '';

        if ($raw === '' || $raw === 'all') {
            return [
                'selectedYear' => null,
                'isAllYears' => true,
                'currentYear' => $currentYear,
            ];
        }

        $selectedYear = (int) $raw;
        if ($selectedYear < 1990 || $selectedYear > $currentYear + 1) {
            $selectedYear = $currentYear;
        }

        return [
            'selectedYear' => $selectedYear,
            'isAllYears' => false,
            'currentYear' => $currentYear,
        ];
    }

    /**
     * Filtre des 2 graphiques résumé Analyses : last12 (défaut) | année | all.
     *
     * @return array{mode: string, selectedYear: ?int, currentYear: int, queryValue: string}
     */
    public static function fromSummaryQueryParam(mixed $yearParam): array
    {
        $currentYear = (int) (new \DateTimeImmutable('today'))->format('Y');
        $raw = is_string($yearParam) || is_numeric($yearParam) ? trim((string) $yearParam) : '';

        if ($raw === '' || $raw === self::MODE_LAST12 || $yearParam === null) {
            return [
                'mode' => self::MODE_LAST12,
                'selectedYear' => null,
                'currentYear' => $currentYear,
                'queryValue' => self::MODE_LAST12,
            ];
        }

        if ($raw === self::MODE_ALL) {
            return [
                'mode' => self::MODE_ALL,
                'selectedYear' => null,
                'currentYear' => $currentYear,
                'queryValue' => self::MODE_ALL,
            ];
        }

        $selectedYear = (int) $raw;
        if ($selectedYear < 1990 || $selectedYear > $currentYear + 1) {
            return [
                'mode' => self::MODE_LAST12,
                'selectedYear' => null,
                'currentYear' => $currentYear,
                'queryValue' => self::MODE_LAST12,
            ];
        }

        return [
            'mode' => self::MODE_YEAR,
            'selectedYear' => $selectedYear,
            'currentYear' => $currentYear,
            'queryValue' => (string) $selectedYear,
        ];
    }

    /**
     * @param list<int> $yearsFromData
     *
     * @return list<int>
     */
    public static function ensureCurrentYearListed(array $yearsFromData, int $currentYear): array
    {
        if (!in_array($currentYear, $yearsFromData, true)) {
            $yearsFromData[] = $currentYear;
        }

        rsort($yearsFromData);

        return array_values($yearsFromData);
    }

    /**
     * Valeur à remonter dans l’URL (?year=).
     */
    public static function toQueryValue(?int $selectedYear, bool $isAllYears): string
    {
        return $isAllYears || $selectedYear === null ? 'all' : (string) $selectedYear;
    }
}
