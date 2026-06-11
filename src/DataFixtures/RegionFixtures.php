<?php

namespace App\DataFixtures;

use App\Entity\Pays;
use App\Entity\Region;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Cocur\Slugify\Slugify;

class RegionFixtures extends Fixture
{
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
                [   "region"=>"AFRIQUE DU NORD", 
                    "lespays"=>[
                        ["pays"=>"ALGERIE","capitale"=>"ALGER"],
                        ["pays"=>"EGYPTE","capitale"=>"LE CAIRE"],
                        ["pays"=>"LIBYE","capitale"=>"TRIPOLI"],
                        ["pays"=>"MAROC","capitale"=>"RABAT"],
                        ["pays"=>"SAHARA OCCIDENTAL","capitale"=>"LAAYOUNE"],
                        ["pays"=>"TUNISIE","capitale"=>"TUNIS"]
                    ]
                ],
                [   "region"=>"AFRIQUE DU NORD", 
                    "lespays"=>[
                        ["pays"=>"AFRIQUE DU SUD","capitale"=>"PRETORIA"],
                        ["pays"=>"BOTSWANA","capitale"=>"GABORONE"],
                        ["pays"=>"SWAZILAND","capitale"=>"MBABANE"],
                        ["pays"=>"LESOTHO","capitale"=>"MASERU"],
                        ["pays"=>"NAMIBIE","capitale"=>"WINDOEK"]
                    ]
                ]  
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
