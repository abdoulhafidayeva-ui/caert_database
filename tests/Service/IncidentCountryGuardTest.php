<?php

namespace App\Tests\Service;

use App\Entity\Pays;
use App\Entity\User;
use App\Service\Security\IncidentCountryGuard;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class IncidentCountryGuardTest extends TestCase
{
    private IncidentCountryGuard $guard;

    protected function setUp(): void
    {
        $this->guard = new IncidentCountryGuard();
    }

    public function testAdminIsNotCountryRestricted(): void
    {
        $user = (new User())->setRoles(['ROLE_ADMIN']);
        self::assertFalse($this->guard->isCountryRestricted($user));
    }

    public function testFocalUserMustMatchAssignedCountry(): void
    {
        $mali = $this->createCountry(1, 'MALI');
        $senegal = $this->createCountry(2, 'SENEGAL');

        $user = (new User())->setRoles(['ROLE_USER'])->setPays($mali);

        $this->guard->assertCountryAllowed($user, $mali);
        $this->expectException(AccessDeniedException::class);
        $this->guard->assertCountryAllowed($user, $senegal);
    }

    private function createCountry(int $id, string $label): Pays
    {
        $country = (new Pays())->setLibelle($label);
        $idProp = new \ReflectionProperty(Pays::class, 'id');
        $idProp->setValue($country, $id);

        return $country;
    }
}
