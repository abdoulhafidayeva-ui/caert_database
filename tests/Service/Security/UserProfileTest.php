<?php

namespace App\Tests\Service\Security;

use App\Entity\User;
use App\Service\Security\UserProfile;
use PHPUnit\Framework\TestCase;

final class UserProfileTest extends TestCase
{
    public function testResolveRolesFromProfil(): void
    {
        self::assertSame(['ROLE_USER'], UserProfile::resolveRoles(UserProfile::FOCAL));
        self::assertSame(['ROLE_STAFF'], UserProfile::resolveRoles(UserProfile::STAFF));
        self::assertSame(['ROLE_ADMIN'], UserProfile::resolveRoles(UserProfile::ADMIN));
        self::assertSame(['ROLE_SUPER_ADMIN'], UserProfile::resolveRoles(UserProfile::ADMIN, true));
    }

    public function testNormalizeLegacyProfilLabels(): void
    {
        self::assertSame(UserProfile::FOCAL, UserProfile::normalize('Point focal Pays'));
        self::assertSame(UserProfile::STAFF, UserProfile::normalize('Staff_Caert'));
        self::assertSame(UserProfile::ADMIN, UserProfile::normalize('Administrateur'));
    }

    public function testSuperAdminTranslationKey(): void
    {
        $user = (new User())
            ->setProfil(UserProfile::ADMIN)
            ->setRoles(['ROLE_SUPER_ADMIN']);

        self::assertSame('user.profile.super_admin', UserProfile::translationKey($user));
    }
}
