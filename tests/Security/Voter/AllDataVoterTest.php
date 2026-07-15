<?php

namespace App\Tests\Security\Voter;

use App\Entity\AllData;
use App\Entity\Pays;
use App\Entity\Region;
use App\Entity\User;
use App\Security\Voter\AllDataVoter;
use App\Service\Security\UserDataScope;
use App\Service\Security\UserProfile;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class AllDataVoterTest extends TestCase
{
    private AllDataVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new AllDataVoter(new UserDataScope());
    }

    public function testFocalCanViewForeignCountryButCannotEdit(): void
    {
        $mali = $this->createCountry(1);
        $senegal = $this->createCountry(2);

        $user = (new User())
            ->setRoles(UserProfile::resolveRoles(UserProfile::FOCAL))
            ->setPays($mali)
            ->setEnable(true);

        $foreignIncident = (new AllData())->setPays($senegal);
        $ownIncident = (new AllData())->setPays($mali);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $foreignIncident, [AllDataVoter::VIEW]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, $foreignIncident, [AllDataVoter::EDIT]));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, $ownIncident, [AllDataVoter::EDIT]));
    }

    public function testStaffCanEditWithinRegionOnly(): void
    {
        $region = (new Region())->setLibelle('WEST');
        $idProp = new \ReflectionProperty(Region::class, 'id');
        $idProp->setValue($region, 1);

        $mali = $this->createCountry(1);
        $mali->setRegion($region);
        $east = (new Region())->setLibelle('EAST');
        $idProp->setValue($east, 2);
        $kenya = $this->createCountry(2);
        $kenya->setRegion($east);

        $user = (new User())
            ->setRoles(UserProfile::resolveRoles(UserProfile::STAFF))
            ->setRegion($region)
            ->setEnable(true);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, (new AllData())->setPays($mali), [AllDataVoter::EDIT]));
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, (new AllData())->setPays($kenya), [AllDataVoter::EDIT]));
    }

    public function testStaffCanPublish(): void
    {
        $user = (new User())
            ->setRoles(UserProfile::resolveRoles(UserProfile::STAFF))
            ->setEnable(true);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter->vote($token, new AllData(), [AllDataVoter::PUBLISH]));
    }

    public function testFocalCannotPublish(): void
    {
        $user = (new User())
            ->setRoles(UserProfile::resolveRoles(UserProfile::FOCAL))
            ->setEnable(true);

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());

        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter->vote($token, new AllData(), [AllDataVoter::PUBLISH]));
    }

    private function createCountry(int $id): Pays
    {
        $country = new Pays();
        $idProp = new \ReflectionProperty(Pays::class, 'id');
        $idProp->setValue($country, $id);

        return $country;
    }
}
