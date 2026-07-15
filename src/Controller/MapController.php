<?php

namespace App\Controller;

use App\Repository\AllDataRepository;
use App\Service\Gis\CountryCentroidProvider;
use App\Service\Security\UserDataScope;
use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class MapController extends AbstractAppController
{
    private string $menu = 'map';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly CountryCentroidProvider $countryCentroidProvider,
        private readonly UserDataScope $dataScope,
    ) {
    }

    #[Route(path: '/map', name: 'app_map')]
    public function index(AllDataRepository $repository): Response
    {
        $user = $this->getUser();
        $aggregates = $repository->findMapAggregatesByCountry(
            $user instanceof User ? $user : null,
            $this->dataScope,
        );

        return $this->render('map/index.html.twig', [
            'menu' => $this->menu,
            'countryCount' => count($aggregates),
            'incidentCount' => array_sum(array_column($aggregates, 'count')),
        ]);
    }

    #[Route(path: '/api/map/incidents', name: 'api_map_incidents', methods: ['GET'])]
    public function incidents(Request $request, AllDataRepository $repository): JsonResponse
    {
        $country = trim((string) $request->query->get('country', ''));
        $rows = $repository->findMapIncidentDetails($country !== '' ? $country : null);

        $features = [];
        $incidents = [];
        foreach ($rows as $row) {
            $incidents[] = $this->normalizeIncident($row);
            $feature = $this->buildIncidentFeature($row);
            if ($feature !== null) {
                $features[] = $feature;
            }
        }

        return $this->json([
            'type' => 'FeatureCollection',
            'features' => $features,
            'incidents' => $incidents,
        ]);
    }

    #[Route(path: '/api/map/countries', name: 'api_map_countries', methods: ['GET'])]
    public function countries(AllDataRepository $repository): JsonResponse
    {
        $user = $this->getUser();

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
                    'injured' => $row['injured'],
                ],
            ], $repository->findMapAggregatesByCountry(
                $user instanceof User ? $user : null,
                $this->dataScope,
            )),
        ]);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function normalizeIncident(array $row): array
    {
        $date = $row['dateAttaque'] ?? null;
        $id = (int) $row['id'];

        return [
            'id' => $id,
            'country' => (string) ($row['country'] ?? ''),
            'localite' => (string) ($row['localite'] ?? ''),
            'deaths' => (int) ($row['totalDeces'] ?? 0),
            'injured' => (int) ($row['totalBlesses'] ?? 0),
            'attackType' => (string) ($row['attackType'] ?? ''),
            'target' => (string) ($row['target'] ?? ''),
            'perpetrator' => (string) ($row['perpetrator'] ?? ''),
            'date' => $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : null,
            'viewUrl' => $this->urlGenerator->generate('view_all_data', ['allData' => $id]),
        ];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>|null
     */
    private function buildIncidentFeature(array $row): ?array
    {
        $country = (string) ($row['country'] ?? '');
        $coords = $this->countryCentroidProvider->resolve($country);
        if ($coords === null) {
            return null;
        }

        return [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [$coords[1], $coords[0]],
            ],
            'properties' => $this->normalizeIncident($row),
        ];
    }
}
