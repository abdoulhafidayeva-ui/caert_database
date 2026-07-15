<?php

namespace App\Tests\Service;

use App\Entity\Pays;
use App\Entity\Region;
use App\Entity\User;
use App\Service\Security\IncidentCountryGuard;
use App\Service\Security\UserDataScope;
use App\Service\Security\UserProfile;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class IncidentCountryGuardTest extends TestCase
{
    private IncidentCountryGuard $guard;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);
        $this->guard = new IncidentCountryGuard($translator, new UserDataScope());
    }

    public function testAdminIsNotCountryRestricted(): void
    {
        $user = (new User())->setRoles(['ROLE_ADMIN']);
        self::assertFalse($this->guard->isCountryRestricted($user));
    }

    public function testStaffIsNotCountryRestricted(): void
    {
        $user = (new User())->setRoles(['ROLE_STAFF']);
        self::assertFalse($this->guard->isCountryRestricted($user));
    }

    public function testFocalUserIsCountryRestricted(): void
    {
        $user = (new User())->setRoles(UserProfile::resolveRoles(UserProfile::FOCAL));
        self::assertTrue($this->guard->isCountryRestricted($user));
    }

    public function testFocalUserMustMatchAssignedCountry(): void
    {
        $mali = $this->createCountry(1, 'MALI');
        $senegal = $this->createCountry(2, 'SENEGAL');

        $user = (new User())->setRoles(UserProfile::resolveRoles(UserProfile::FOCAL))->setPays($mali);

        $this->guard->assertWriteAllowed($user, $mali);
        $this->expectException(AccessDeniedException::class);
        $this->guard->assertWriteAllowed($user, $senegal);
    }

    public function testStaffMustWriteWithinAssignedRegion(): void
    {
        $region = (new Region())->setLibelle('WEST');
        $idProp = new \ReflectionProperty(Region::class, 'id');
        $idProp->setValue($region, 1);

        $mali = $this->createCountry(1, 'MALI', $region);
        $east = (new Region())->setLibelle('EAST');
        $idProp->setValue($east, 2);
        $kenya = $this->createCountry(3, 'KENYA', $east);

        $user = (new User())
            ->setRoles(UserProfile::resolveRoles(UserProfile::STAFF))
            ->setRegion($region);

        $this->guard->assertWriteAllowed($user, $mali);
        $this->expectException(AccessDeniedException::class);
        $this->guard->assertWriteAllowed($user, $kenya);
    }

    private function createCountry(int $id, string $label, ?Region $region = null): Pays
    {
        $country = (new Pays())->setLibelle($label);
        if ($region !== null) {
            $country->setRegion($region);
        }
        $idProp = new \ReflectionProperty(Pays::class, 'id');
        $idProp->setValue($country, $id);

        return $country;
    }
}
