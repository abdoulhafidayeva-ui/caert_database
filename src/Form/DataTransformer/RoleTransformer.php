<?php

namespace App\Form\DataTransformer;

use App\Entity\Roles;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;

class RoleTransformer implements DataTransformerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function transform(mixed $roles): ArrayCollection
    {
        $roleCollection = new ArrayCollection();
        if (null === $roles) {
            return $roleCollection;
        }

        $roleRep = $this->em->getRepository(Roles::class);
        foreach ($roles as $roleNom) {
            $role = $roleRep->findOneBy(['label' => $roleNom]);
            if ($role) {
                $roleCollection->add($role);
            }
        }

        return $roleCollection;
    }

    public function reverseTransform(mixed $roles): array
    {
        $rolesArray = [];
        if (!$roles) {
            return $rolesArray;
        }

        foreach ($roles as $role) {
            $rolesArray[] = $role->getLabel();
        }

        return $rolesArray;
    }
}
