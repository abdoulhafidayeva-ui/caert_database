<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->getIsVerified() !== true) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte n\'est pas encore vérifié. Consultez votre e-mail ou contactez un administrateur.'
            );
        }

        if ($user->getEnable() !== true) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte est suspendu. Contactez un administrateur.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
    }
}
