<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Bundle\SecurityBundle\Security;

class UserManager
{
    
    private $parameterBag;
    private $roles;
    private $security;

    public function __construct(ParameterBagInterface $parameterBag, Security $security, UserRepository $userRepository)
    {
        $this->parameterBag = $parameterBag;
        $this->roles = $this->parameterBag->get('security.role_hierarchy.roles');
        $this->security = $security;
        $this->userRepository = $userRepository;
    }

    public function getRoles()
    {
        return $this->processGetUniqueRoles($this->roles);
    }

    /**
     * @return array<string, string> libellé => valeur Symfony
     */
    public function getAssignableRoleChoices(): array
    {
        $labels = [
            'ROLE_USER' => 'Utilisateur',
            'ROLE_ADMIN' => 'Administrateur',
            'ROLE_USERS_MANAGEMENT' => 'Gestion des utilisateurs',
            'ROLE_SUPER_ADMIN' => 'Super administrateur',
        ];

        $choices = [];
        foreach ($this->getRoles() as $role) {
            $choices[$labels[$role] ?? $role] = $role;
        }

        ksort($choices);

        return $choices;
    }

    public function processGetUniqueRoles($roles)
    {
        $allRoles = [];
        foreach($roles as $key => $role){
            if(is_string($key)){
                $allRoles[] = $key;
            }
            if(is_string($role)){
                $allRoles[] = $role;
            }else if(is_array($role)){
                $subRoles = $this->processGetUniqueRoles($role);
                $allRoles = array_merge($allRoles, $subRoles);
            }
        }
        $uniquesRoles = array_unique($allRoles);
        return $uniquesRoles;
    }

}
