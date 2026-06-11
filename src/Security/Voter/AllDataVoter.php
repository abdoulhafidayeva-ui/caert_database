<?php

namespace App\Security\Voter;

use App\Entity\AllData;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, AllData|null>
 */
final class AllDataVoter extends Voter
{
    public const VIEW = 'INCIDENT_VIEW';
    public const EDIT = 'INCIDENT_EDIT';
    public const DELETE = 'INCIDENT_DELETE';
    public const PUBLISH = 'INCIDENT_PUBLISH';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::PUBLISH], true)
            && ($subject === null || $subject instanceof AllData);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($user->getEnable() === false) {
            return false;
        }

        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        return match ($attribute) {
            self::PUBLISH => in_array('ROLE_ADMIN', $user->getRoles(), true),
            self::VIEW, self::EDIT, self::DELETE => $this->canAccessCountry($user, $subject),
            default => false,
        };
    }

    private function canAccessCountry(User $user, ?AllData $incident): bool
    {
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        if ($incident === null) {
            return true;
        }

        $userCountry = $user->getPays();
        $incidentCountry = $incident->getPays();

        if ($userCountry === null || $incidentCountry === null) {
            return false;
        }

        return $userCountry->getId() === $incidentCountry->getId();
    }
}
