<?php

namespace App\Controller;

use App\Repository\AllDataRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route(path: '/executive', name: 'executive_dashboard')]
    public function executive(AllDataRepository $repository): Response
    {
        return $this->render('dashboard/executive.html.twig', [
            'menu' => 'executive',
            'summary' => $repository->getExecutiveSummary(),
            'topCountries' => $repository->getTopCountriesByIncidents(),
        ]);
    }
}
