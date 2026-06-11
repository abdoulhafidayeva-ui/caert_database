<?php

namespace App\Controller;

use App\Repository\PaysRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class RegionPaysApiController extends AbstractController
{
    #[Route(path: '/api/pays-by-region', name: 'api_pays_by_region', methods: ['GET'])]
    public function paysByRegion(Request $request, PaysRepository $paysRepository): JsonResponse
    {
        $regions = $request->query->all('region');
        if ($regions === []) {
            $single = $request->query->get('region');
            if (is_string($single) && $single !== '') {
                $regions = [$single];
            }
        }

        $libelles = [];
        foreach ($regions as $value) {
            if (is_string($value) && trim($value) !== '') {
                $libelles[] = trim($value);
            }
        }

        return $this->json([
            'pays' => $paysRepository->findForApiByRegionLibelles($libelles),
        ]);
    }
}
