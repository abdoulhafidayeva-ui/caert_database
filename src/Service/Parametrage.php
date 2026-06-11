<?php
namespace App\Service;

use App\Entity\AppParam;
use Doctrine\ORM\EntityManagerInterface;

class Parametrage {

    protected $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    public function object() {
        $param = $this->em->getRepository(AppParam::class)->findOneBy(array(), array('id' => 'DESC'));
        return $param;
    }

}
