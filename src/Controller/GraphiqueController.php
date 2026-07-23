<?php

namespace App\Controller;

use App\Entity\AllData;
use App\Entity\Region;
use App\Entity\User;
use App\Service\Incident\AttackYearSelection;
use App\Service\Security\UserDataScope;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class GraphiqueController extends AbstractAppController
{
    private string $menu = 'graphique';

    public function __construct(
        private readonly UserDataScope $userDataScope,
    ) {
    }

    #[Route(path: '/graphique/page', name: 'graphique', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $currentYear = (int) (new DateTimeImmutable('today'))->format('Y');
        $availableYears = AttackYearSelection::ensureCurrentYearListed(
            $em->getRepository(AllData::class)->findDistinctAttackYears(),
            $currentYear,
        );

        return $this->render('graphique/index.html.twig', [
            'menu' => $this->menu,
            'regions' => $em->getRepository(Region::class)->findAllUniqueByLibelle(),
            'types' => [
                'attaque' => $this->trans('analytics.indicator.attaque'),
                'deces' => $this->trans('analytics.indicator.deces'),
                'blesses' => $this->trans('analytics.indicator.blesses'),
            ],
            'analyticsDefaults' => $this->userDataScope->getAnalyticsDefaults($user),
            'availableYears' => $availableYears,
            'summaryPeriodDefault' => AttackYearSelection::MODE_LAST12,
        ]);
    }

    #[Route(path: '/graphique/nb_Terrorist_Incidents', name: 'nb_Terrorist_Incidents', methods: ['GET'])]
    public function getCountTotalIncidents(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $period = AttackYearSelection::fromSummaryQueryParam($request->query->get('year'));
        $allDataRep = $em->getRepository(AllData::class);

        return $this->json([
            'totalDeath' => (int) $allDataRep->getCountTotalAttackInjuredDeath('death', $user, $this->userDataScope, $period),
            'totalInjured' => (int) $allDataRep->getCountTotalAttackInjuredDeath('injured', $user, $this->userDataScope, $period),
            'totalAttack' => (int) $allDataRep->getCountTotalAttackInjuredDeath('attack', $user, $this->userDataScope, $period),
            'period' => $period['queryValue'],
        ]);
    }

    #[Route(path: '/graphique/pr_Targets_Attacks', name: 'pr_Targets_Attacks', methods: ['GET'])]
    public function getCountTotalTargetsAttacks(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $period = AttackYearSelection::fromSummaryQueryParam($request->query->get('year'));
        $allDataRep = $em->getRepository(AllData::class);
        $rows = $allDataRep->getTopTargetsByIncidents($user, $this->userDataScope, 6, $period);

        return $this->json([
            'labels' => array_column($rows, 'label'),
            'values' => array_column($rows, 'count'),
            'items' => $rows,
            'period' => $period['queryValue'],
        ]);
    }

    #[Route(path: '/search_for_graphique', name: 'search_for_graphique', methods: ['POST'])]
    public function searchForGraphique(Request $request, EntityManagerInterface $em): Response
    {
        $start = trim((string) $request->request->get('start', ''));
        $end = trim((string) $request->request->get('end', ''));
        $type = $this->normalizeIndicatorType((string) $request->request->get('type', ''));
        $regionIds = $this->normalizeRegionIds($request);

        if ($start === '' || $type === null || $regionIds === []) {
            return $this->json(['error' => $this->trans('analytics.error.period_required')], 400);
        }

        if ($end === '') {
            $end = $start;
        }

        $allDataRep = $em->getRepository(AllData::class);
        $periodOne = [];
        $periodTwo = [];
        $regionLabels = [];
        $pendingTotal = 0;
        $publishedIncidents = 0;

        foreach ($regionIds as $regionId) {
            $regionEntity = $em->getRepository(Region::class)->find($regionId);
            if (!$regionEntity instanceof Region) {
                continue;
            }

            $periodOne[] = (int) ($allDataRep->getCountTotalForSearch($type, $regionEntity, $start) ?? 0);
            $publishedIncidents += (int) ($allDataRep->getCountTotalForSearch('attaque', $regionEntity, $start) ?? 0);
            $pendingTotal += $allDataRep->countPendingForSearchRange($regionEntity, $start, $start);

            if ($end !== $start) {
                $periodTwo[] = (int) ($allDataRep->getCountTotalForSearch($type, $regionEntity, $end) ?? 0);
                $publishedIncidents += (int) ($allDataRep->getCountTotalForSearch('attaque', $regionEntity, $end) ?? 0);
                $pendingTotal += $allDataRep->countPendingForSearchRange($regionEntity, $end, $end);
            }

            $regionLabels[] = (string) $regionEntity->getLibelle();
        }

        if ($regionLabels === []) {
            return $this->json(['error' => $this->trans('analytics.error.no_valid_region')], 400);
        }

        $locale = $request->getLocale();
        $countMonth = [
            [
                'label' => $this->formatMonthLabel($start, $locale),
                'donnees' => $periodOne,
            ],
        ];

        if ($end !== $start) {
            $countMonth[] = [
                'label' => $this->formatMonthLabel($end, $locale),
                'donnees' => $periodTwo,
            ];
        }

        $indicatorTotal = 0;
        foreach ($countMonth as $period) {
            foreach ($period['donnees'] as $value) {
                $indicatorTotal += (float) $value;
            }
        }

        $hasIndicatorData = $indicatorTotal > 0;
        $info = null;
        if (!$hasIndicatorData) {
            if ($pendingTotal > 0) {
                $info = $this->trans('analytics.error.pending_not_published', ['%count%' => $pendingTotal]);
            } elseif ($publishedIncidents > 0 && $type !== 'attaque') {
                $info = $this->trans('analytics.error.indicator_zero', [
                    '%indicator%' => $this->trans('analytics.indicator.'.$type),
                    '%count%' => $publishedIncidents,
                ]);
            } else {
                $info = $this->trans('analytics.error.no_published_data');
            }
        }

        return $this->json([
            'type' => strtoupper($type),
            'typeLabel' => $this->trans('analytics.indicator.'.$type),
            'sameMois' => $end === $start ? 'oui' : 'no',
            'regions' => $regionLabels,
            'countMonth' => $countMonth,
            'noPublishedData' => !$hasIndicatorData,
            'pendingCount' => $pendingTotal,
            'publishedIncidentCount' => $publishedIncidents,
            'info' => $info,
        ]);
    }

    /**
     * Accepte la valeur technique ou un libellé (Select2 / anciennes versions serveur).
     */
    private function normalizeIndicatorType(string $raw): ?string
    {
        $type = mb_strtolower(trim($raw));
        if ($type === '') {
            return null;
        }

        // Si Select2 renvoie un tableau (cas rare)
        $aliases = [
            'attaque' => 'attaque',
            'attack' => 'attaque',
            'nombre d\'attaques' => 'attaque',
            'number of attacks' => 'attaque',
            'deces' => 'deces',
            'deaths' => 'deces',
            'total décès' => 'deces',
            'total deces' => 'deces',
            'total deaths' => 'deces',
            // Anciens indicateurs détail → totaux (compat)
            'perpetrateurs' => 'deces',
            'perpetrators' => 'deces',
            'morts terroristes' => 'deces',
            'terrorist deaths' => 'deces',
            'civil' => 'deces',
            'morts civils' => 'deces',
            'civilian deaths' => 'deces',
            'blesses' => 'blesses',
            'injured' => 'blesses',
            'total blessés' => 'blesses',
            'total blesses' => 'blesses',
            'total injured' => 'blesses',
        ];

        return $aliases[$type] ?? (in_array($type, ['attaque', 'deces', 'blesses'], true) ? $type : null);
    }
    private function normalizeRegionIds(Request $request): array
    {
        $regionIds = $request->request->all('region');
        if (!is_array($regionIds)) {
            $regionIds = [];
        }

        if ($regionIds === []) {
            $single = $request->request->get('region');
            if (is_string($single) && $single !== '') {
                $regionIds = [$single];
            }
        }

        return array_values(array_filter($regionIds, static fn ($id) => $id !== null && $id !== ''));
    }

    private function formatMonthLabel(string $yearMonth, string $locale): string
    {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $yearMonth.'-01');
        if ($date === false) {
            return $yearMonth;
        }

        $normalizedLocale = strtolower(substr($locale, 0, 2));

        // Sous Windows sans extension intl native, le polyfill ne gère que "en".
        if (class_exists(\IntlDateFormatter::class)) {
            try {
                $formatter = new \IntlDateFormatter(
                    $normalizedLocale === 'fr' ? 'fr_FR' : ($normalizedLocale === 'en' ? 'en_US' : $locale),
                    \IntlDateFormatter::NONE,
                    \IntlDateFormatter::NONE,
                    null,
                    null,
                    'MMMM yyyy'
                );
                $formatted = $formatter->format($date);
                if (is_string($formatted) && $formatted !== '') {
                    return ucfirst($formatted);
                }
            } catch (\Throwable) {
                // Fallback ci-dessous
            }
        }

        if ($normalizedLocale === 'fr') {
            $months = [
                1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
                5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
                9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
            ];
            $monthNum = (int) $date->format('n');

            return ($months[$monthNum] ?? $date->format('m')).' '.$date->format('Y');
        }

        return $date->format('F Y');
    }
}
