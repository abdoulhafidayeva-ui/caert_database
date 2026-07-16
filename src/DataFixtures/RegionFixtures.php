<?php

namespace App\DataFixtures;

use App\Entity\Pays;
use App\Entity\Region;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Régions et pays (Afrique de l'Ouest, Centrale, Est) — sans utilisateurs.
 */
class RegionFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['geo', 'prod'];
    }

    public function load(ObjectManager $manager): void
    {
        $regionTables = [
                [   "region"=>"AFRIQUE DE L'OUEST", 
                    "lespays"=>[
                        ["pays"=>"BURKINA FASO","capitale"=>"Ouagadougou"],
                        ["pays"=>"BENIN","capitale"=>"Cotonou"],
                        ["pays"=>"COTE D'IVOIRE","capitale"=>"Abidjan"],
                        ["pays"=>"GHANA","capitale"=>"Acra"],
                        ["pays"=>"GUINEE","capitale"=>"Conakry"],
                        ["pays"=>"GUINEE BISSAU","capitale"=>"BISSAU"],
                        ["pays"=>"GAMBIE","capitale"=>"Banjul"],
                        ["pays"=>"CAP-VERT","capitale"=>"PRAIA"],
                        ["pays"=>"LIBERIA","capitale"=>"MORONVIA"],
                        ["pays"=>"MALI","capitale"=>"BAMAKO"],
                        ["pays"=>"NIGER","capitale"=>"NIAMEY"],
                        ["pays"=>"NIGERIA","capitale"=>"ABUJA"],
                        ["pays"=>"SIERRA LEONNE","capitale"=>"FREETOWN"],
                        ["pays"=>"SENEGAL","capitale"=>"DAKAR"],
                        ["pays"=>"MAURITANIE","capitale"=>"NOUAKTCHOK"],
                        ["pays"=>"TOGO","capitale"=>"LOME"]
                    ]
                ],
                [   "region"=>"AFRIQUE CENTRALE", 
                    "lespays"=>[
                        ["pays"=>"CAMEROUN","capitale"=>"YAOUNDE"],
                        ["pays"=>"CENTRAFRIQUE","capitale"=>"BANGUI"],
                        ["pays"=>"CONGO","capitale"=>"BRAZAVILLE"],
                        ["pays"=>"RDC","capitale"=>"KINSHASA"],
                        ["pays"=>"ANGOLA","capitale"=>"LUANDA"],
                        ["pays"=>"GABON","capitale"=>"LIBREVILLE"],
                        ["pays"=>"GUINEE EQUATORIALE","capitale"=>"MALABO"],
                        ["pays"=>"SAO TOME ET PRINCIPE","capitale"=>"SAO TOME"],
                        ["pays"=>"TCHAD","capitale"=>"NDJAMENA"]
                    ]
                ],
                [   "region"=>"AFRIQUE DE L'EST",
                    "lespays"=>[
                        ["pays"=>"BURUNDI","capitale"=>"BUJUMBURA"],
                        ["pays"=>"COMORES","capitale"=>"MORONIE"],
                        ["pays"=>"DJIBOUTI","capitale"=>"DJIBOUTI"],
                        ["pays"=>"ERYTHREE","capitale"=>"ASMARA"],
                        ["pays"=>"ETHIOPIE","capitale"=>"ADDIS ABEBA"],
                        ["pays"=>"KENYA","capitale"=>"NAIROBI"],
                        ["pays"=>"MADAGASCAR","capitale"=>"ANTANARIVO"],
                        ["pays"=>"MALAWI","capitale"=>"LILONGWE"],
                        ["pays"=>"MAURICE","capitale"=>"PORT LOUIS"],
                        ["pays"=>"MOZAMBIQUE","capitale"=>"MAPUTO"],
                        ["pays"=>"OUGANDA","capitale"=>"KAMPALA"],
                        ["pays"=>"RWANDA","capitale"=>"KIGALI"],
                        ["pays"=>"SEYCHELLES","capitale"=>"VICTORIA"],
                        ["pays"=>"SOMALIE","capitale"=>"MOGADISCIO"],
                        ["pays"=>"SOUDAN","capitale"=>"KHARTOULM"],
                        ["pays"=>"SOUDAN DU SUD","capitale"=>"DJOUBA"],
                        ["pays"=>"TANZANIE","capitale"=>"DODOMA"],
                        ["pays"=>"ZAMBIE","capitale"=>"LUSAKA"],
                        ["pays"=>"ZIMBABWE","capitale"=>"HARARE"]
                    ]
                ],
        ];

        foreach ($regionTables as $regionTable) {
            $region = new Region();
            $region->setLibelle($regionTable["region"]);
            $region->setCode(str_replace(" ", "-", $regionTable["region"]));
            $manager->persist($region);

            foreach ($regionTable["lespays"] as $lepays) { 
                $country = new Pays();
                $country->setLibelle($lepays["pays"]);
                $country->setCode(str_replace(" ", "-", $lepays["pays"]));
                $country->setCapitale($lepays["capitale"]);
                $country->setRegion($region);
                $manager->persist($country); 
            }
        }

        $manager->flush();
    }
}
