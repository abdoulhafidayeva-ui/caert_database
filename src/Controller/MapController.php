<?php



namespace App\Controller;



use App\Repository\AllDataRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Attribute\Route;

use Symfony\Component\Security\Http\Attribute\IsGranted;



#[IsGranted('ROLE_USER')]

class MapController extends AbstractController

{

    #[Route(path: '/map', name: 'app_map')]

    public function index(AllDataRepository $repository): Response

    {

        $aggregates = $repository->findMapAggregatesByCountry();



        return $this->render('map/index.html.twig', [

            'menu' => 'map',

            'countryCount' => count($aggregates),

            'incidentCount' => array_sum(array_column($aggregates, 'count')),

        ]);

    }



    #[Route(path: '/api/map/incidents', name: 'api_map_incidents', methods: ['GET'])]

    public function incidents(AllDataRepository $repository): JsonResponse

    {

        return $this->json([

            'type' => 'FeatureCollection',

            'features' => array_map(static fn (array $p) => [

                'type' => 'Feature',

                'geometry' => [

                    'type' => 'Point',

                    'coordinates' => [$p['lng'], $p['lat']],

                ],

                'properties' => [

                    'id' => $p['id'],

                    'label' => $p['label'],

                    'deaths' => $p['deaths'],

                    'localite' => $p['localite'],

                ],

            ], $repository->findMapPointsByCountry()),

        ]);

    }



    #[Route(path: '/api/map/countries', name: 'api_map_countries', methods: ['GET'])]

    public function countries(AllDataRepository $repository): JsonResponse

    {

        return $this->json([

            'type' => 'FeatureCollection',

            'features' => array_map(static fn (array $row) => [

                'type' => 'Feature',

                'geometry' => [

                    'type' => 'Point',

                    'coordinates' => [$row['lng'], $row['lat']],

                ],

                'properties' => [

                    'country' => $row['country'],

                    'count' => $row['count'],

                    'deaths' => $row['deaths'],

                ],

            ], $repository->findMapAggregatesByCountry()),

        ]);

    }

}


