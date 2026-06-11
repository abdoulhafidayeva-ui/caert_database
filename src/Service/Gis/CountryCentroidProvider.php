<?php

namespace App\Service\Gis;

/**
 * Approximate country centroids for map visualization (WGS84).
 * Keys must match pays.libelle in the database (uppercase).
 */
final class CountryCentroidProvider
{
    /** @var array<string, array{0: float, 1: float}> */
    private const COORDINATES = [
        'ALGERIE' => [28.0339, 1.6596],
        'ANGOLA' => [-11.2027, 17.8739],
        'BENIN' => [9.3077, 2.3158],
        'BOTSWANA' => [-22.3285, 24.6849],
        'BURKINA FASO' => [12.2383, -1.5616],
        'BURUNDI' => [-3.3731, 29.9189],
        'CAMEROUN' => [7.3697, 12.3547],
        'CAP-VERT' => [15.1217, -23.6052],
        'CENTRAFRIQUE' => [6.6111, 20.9394],
        'COMORES' => [-11.6455, 43.3333],
        'CONGO' => [-0.2280, 15.8277],
        'COTE D\'IVOIRE' => [7.5400, -5.5471],
        'DJIBOUTI' => [11.8251, 42.5903],
        'EGYPTE' => [26.8206, 30.8025],
        'ERYTHREE' => [15.1794, 39.7823],
        'ETHIOPIE' => [9.1450, 40.4897],
        'GABON' => [-0.8037, 11.6094],
        'GAMBIE' => [13.4432, -15.3101],
        'GHANA' => [7.9465, -1.0232],
        'GUINEE' => [9.9456, -9.6966],
        'GUINEE BISSAU' => [11.8037, -15.1804],
        'GUINEE EQUATORIALE' => [1.6508, 10.2679],
        'KENYA' => [-0.0236, 37.9062],
        'LESOTHO' => [-29.6100, 28.2336],
        'LIBERIA' => [6.4281, -9.4295],
        'LIBYE' => [26.3351, 17.2283],
        'MADAGASCAR' => [-18.7669, 46.8691],
        'MALAWI' => [-13.2543, 34.3015],
        'MALI' => [17.5707, -3.9962],
        'MAROC' => [31.7917, -7.0926],
        'MAURICE' => [-20.3484, 57.5522],
        'MAURITANIE' => [21.0079, -10.9408],
        'MOZAMBIQUE' => [-18.6657, 35.5296],
        'NAMIBIE' => [-22.9576, 18.4904],
        'NIGER' => [17.6078, 8.0817],
        'NIGERIA' => [9.0820, 8.6753],
        'OUGANDA' => [1.3733, 32.2903],
        'RDC' => [-4.0383, 21.7587],
        'RWANDA' => [-1.9403, 29.8739],
        'SAHARA OCCIDENTAL' => [24.2155, -12.8858],
        'SAO TOME ET PRINCIPE' => [0.1864, 6.6131],
        'SENEGAL' => [14.4974, -14.4524],
        'SEYCHELLES' => [-4.6796, 55.4920],
        'SIERRA LEONNE' => [8.4606, -11.7799],
        'SOMALIE' => [5.1521, 46.1996],
        'SOUDAN' => [12.8628, 30.2176],
        'SOUDAN DU SUD' => [6.8770, 31.3070],
        'AFRIQUE DU SUD' => [-30.5595, 22.9375],
        'SWAZILAND' => [-26.5225, 31.4659],
        'TANZANIE' => [-6.3690, 34.8888],
        'TCHAD' => [15.4542, 18.7322],
        'TOGO' => [8.6195, 0.8248],
        'TUNISIE' => [33.8869, 9.5375],
        'ZAMBIE' => [-13.1339, 27.8493],
        'ZIMBABWE' => [-19.0154, 29.1549],
    ];

    /**
     * @return array{0: float, 1: float}|null [lat, lng]
     */
    public function resolve(string $countryLabel): ?array
    {
        $key = strtoupper(trim($countryLabel));

        return self::COORDINATES[$key] ?? null;
    }

    /**
     * @return list<string>
     */
    public function supportedCountries(): array
    {
        return array_keys(self::COORDINATES);
    }
}
