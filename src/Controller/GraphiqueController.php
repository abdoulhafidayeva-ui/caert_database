<?php

namespace App\Controller;

use App\Entity\AllData;
use App\Entity\Region;
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

    #[Route(path: '/graphique/page', name: 'graphique', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        return $this->render('graphique/index.html.twig', [
            'menu' => $this->menu,
            'regions' => $em->getRepository(Region::class)->findAllUniqueByLibelle(),
            'types' => [
                'attaque' => $this->trans('analytics.indicator.attaque'),
                'perpetrateurs' => $this->trans('analytics.indicator.perpetrateurs'),
                'civil' => $this->trans('analytics.indicator.civil'),
            ],
        ]);
    }

    #[Route(path: '/graphique/nb_Terrorist_Incidents', name: 'nb_Terrorist_Incidents', methods: ['GET'])]
    public function getCountTotalIncidents(EntityManagerInterface $em): Response
    {
        $allDataRep = $em->getRepository(AllData::class);

        return $this->json([
            'totalDeath' => (int) $allDataRep->getCountTotalAttackInjuredDeath('death'),
            'totalInjured' => (int) $allDataRep->getCountTotalAttackInjuredDeath('injured'),
            'totalAttack' => (int) $allDataRep->getCountTotalAttackInjuredDeath('attack'),
        ]);
    }

    #[Route(path: '/graphique/pr_Targets_Attacks', name: 'pr_Targets_Attacks', methods: ['GET'])]
    public function getCountTotalTargetsAttacks(EntityManagerInterface $em): Response
    {
        $allDataRep = $em->getRepository(AllData::class);

        return $this->json([
            'totalCivil' => (int) $allDataRep->getCountTotalTargetsAttacks('civil'),
            'totalSecuriteMilitaire' => (int) $allDataRep->getCountTotalTargetsAttacks('securiteMilitaire'),
            'totalTerroriste' => (int) $allDataRep->getCountTotalTargetsAttacks('terroriste'),
        ]);
    }

    #[Route(path: '/search_for_graphique', name: 'search_for_graphique', methods: ['POST'])]
    public function searchForGraphique(Request $request, EntityManagerInterface $em): Response
    {
        $start = trim((string) $request->request->get('start', ''));
        $end = trim((string) $request->request->get('end', ''));
        $type = strtolower(trim((string) $request->request->get('type', '')));
        $regionIds = $this->normalizeRegionIds($request);

        if ($start === '' || $type === '' || $regionIds === []) {
            return $this->json(['error' => $this->trans('analytics.error.period_required')], 400);
        }

        if (!in_array($type, ['attaque', 'perpetrateurs', 'civil'], true)) {
            return $this->json(['error' => $this->trans('analytics.error.invalid_indicator')], 400);
        }

        if ($end === '') {
            $end = $start;
        }

        $allDataRep = $em->getRepository(AllData::class);
        $periodOne = [];
        $periodTwo = [];
        $regionLabels = [];

        foreach ($regionIds as $regionId) {
            $regionEntity = $em->getRepository(Region::class)->find($regionId);
            if (!$regionEntity instanceof Region) {
                continue;
            }

            $periodOne[] = (int) ($allDataRep->getCountTotalForSearch(
                $type,
                $regionEntity,
                $start,
                $this->getLimitDay($start)
            ) ?? 0);

            if ($end !== $start) {
                $periodTwo[] = (int) ($allDataRep->getCountTotalForSearch(
                    $type,
                    $regionEntity,
                    $end,
                    $this->getLimitDay($end)
                ) ?? 0);
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

        $hasPublishedData = false;
        foreach ($countMonth as $period) {
            foreach ($period['donnees'] as $value) {
                if ((float) $value > 0) {
                    $hasPublishedData = true;
                    break 2;
                }
            }
        }

        return $this->json([
            'type' => strtoupper($type),
            'typeLabel' => $this->trans('analytics.indicator.'.$type),
            'sameMois' => $end === $start ? 'oui' : 'no',
            'regions' => $regionLabels,
            'countMonth' => $countMonth,
            'noPublishedData' => !$hasPublishedData,
            'info' => !$hasPublishedData ? $this->trans('analytics.error.no_published_data') : null,
        ]);
    }

    /**
     * @return list<string|int>
     */
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
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $yearMonth . '-01');
        if (!$date) {
            return $yearMonth;
        }

        if (!class_exists(\IntlDateFormatter::class)) {
            return $yearMonth;
        }

        $formatter = new \IntlDateFormatter(
            $locale,
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            null,
            null,
            'MMMM yyyy'
        );
        $formatted = $formatter->format($date);

        return is_string($formatted) ? ucfirst($formatted) : $yearMonth;
    }

    private function getLimitDay(string $month): int
    {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $month . '-01');
        if (!$date) {
            return 31;
        }

        return (int) $date->format('t');
    }
}
