<?php

namespace App\DataFixtures;

use App\Entity\Pays;
use App\Entity\Region;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Cocur\Slugify\Slugify;

class RegionSuiteFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $regionTables = [
                [   "region"=>"AFRIQUE DU NORD", 
                    "lespays"=>[
                        ["pays"=>"ALGERIE","capitale"=>"ALGER"],
                        ["pays"=>"EGYPTE","capitale"=>"LECAIRE"],
                        ["pays"=>"LIBYE","capitale"=>"TRIPOLI"],
                        ["pays"=>"MAROC","capitale"=>"RABAT"],
                        ["pays"=>"SAHARA OCCIDENTAL","capitale"=>"LAAYOUNE"],
                        ["pays"=>"TUNISIE","capitale"=>"TUNIS"]
                    ]
                ],
                [   "region"=>"AFRIQUE DU SUD", 
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
