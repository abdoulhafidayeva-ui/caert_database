<?php

namespace App\Controller;

use App\Repository\AllDataRepository;
use App\Service\Incident\AttackYearSelection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route(path: '/executive', name: 'executive_dashboard')]
    public function executive(Request $request, AllDataRepository $repository): Response
    {
        $selection = AttackYearSelection::fromQueryParam($request->query->get('year'));
        $availableYears = AttackYearSelection::ensureCurrentYearListed(
            $repository->findDistinctAttackYears(),
            $selection['currentYear'],
        );

        return $this->render('dashboard/executive.html.twig', [
            'menu' => 'executive',
            'summary' => $repository->getExecutiveSummary($selection['selectedYear']),
            'topCountries' => $repository->getTopCountriesByIncidents(10, $selection['selectedYear']),
            'availableYears' => $availableYears,
            'selectedYear' => $selection['selectedYear'],
            'isAllYears' => $selection['isAllYears'],
            'currentYear' => $selection['currentYear'],
        ]);
    }
}
