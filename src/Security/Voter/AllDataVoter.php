<?php

namespace App\Security\Voter;

use App\Entity\AllData;
use App\Entity\User;
use App\Service\Security\UserDataScope;
use App\Service\Security\UserProfile;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, AllData|null>
 */
final class AllDataVoter extends Voter
{
    public function __construct(private readonly UserDataScope $dataScope)
    {
    }

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

        return match ($attribute) {
            self::VIEW => true,
            self::PUBLISH => UserProfile::canPublish($user),
            self::EDIT, self::DELETE => $this->canModify($user, $subject),
            default => false,
        };
    }

    private function canModify(User $user, ?AllData $incident): bool
    {
        if (UserProfile::hasFullDataAccess($user)) {
            return true;
        }

        if ($incident === null) {
            return true;
        }

        if (UserProfile::isStaff($user)) {
            return $this->dataScope->incidentInAssignedRegion($user, $incident);
        }

        $userCountry = $user->getPays();
        $incidentCountry = $incident->getPays();

        if ($userCountry === null || $incidentCountry === null) {
            return false;
        }

        return $userCountry->getId() === $incidentCountry->getId();
    }
}
