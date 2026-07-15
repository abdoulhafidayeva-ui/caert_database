<?php

namespace App\Service\Security;

use App\Entity\User;

/**
 * Profils métier CAERT et mapping vers les rôles Symfony.
 *
 * - Point focal (0)     → ROLE_USER   : sa région par défaut, édite son pays
 * - Staff AUCTC (1)     → ROLE_STAFF  : sa région par défaut, édite/valide sa région
 * - Administrateur (3)  → ROLE_ADMIN  : accès données sans restriction
 * - Super administrateur → ROLE_SUPER_ADMIN : gestion utilisateurs et paramètres système
 */
final class UserProfile
{
    public const FOCAL = '0';
    public const STAFF = '1';
    public const ADMIN = '3';

    /** @return array<string, string> clé de traduction => valeur stockée */
    public static function choices(): array
    {
        return [
            'user.profile.focal' => self::FOCAL,
            'user.profile.staff' => self::STAFF,
            'user.profile.admin' => self::ADMIN,
        ];
    }

    public static function focalChoices(): array
    {
        return [
            'user.profile.focal' => self::FOCAL,
        ];
    }

    public static function normalize(mixed $profil): string
    {
        $value = trim((string) $profil);

        return match ($value) {
            self::FOCAL, 'Point focal Pays' => self::FOCAL,
            self::STAFF, 'Staff_Caert', 'Staff AUCTC' => self::STAFF,
            self::ADMIN, 'Administrateur' => self::ADMIN,
            default => self::FOCAL,
        };
    }

    /**
     * @return list<string>
     */
    public static function resolveRoles(string $profil, bool $isSuperAdmin = false): array
    {
        if ($isSuperAdmin) {
            return ['ROLE_SUPER_ADMIN'];
        }

        return match (self::normalize($profil)) {
            self::STAFF => ['ROLE_STAFF'],
            self::ADMIN => ['ROLE_ADMIN'],
            default => ['ROLE_USER'],
        };
    }

    public static function isSuperAdmin(User $user): bool
    {
        return in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
    }

    public static function canPublish(User $user): bool
    {
        $roles = $user->getRoles();

        return in_array('ROLE_SUPER_ADMIN', $roles, true)
            || in_array('ROLE_ADMIN', $roles, true)
            || in_array('ROLE_STAFF', $roles, true);
    }

    public static function isRegionScoped(User $user): bool
    {
        if (self::hasFullDataAccess($user)) {
            return false;
        }

        $roles = $user->getRoles();

        return in_array('ROLE_STAFF', $roles, true)
            || in_array('ROLE_USER', $roles, true);
    }

    public static function hasFullDataAccess(User $user): bool
    {
        $roles = $user->getRoles();

        return self::isSuperAdmin($user)
            || in_array('ROLE_ADMIN', $roles, true);
    }

    public static function isStaff(User $user): bool
    {
        return in_array('ROLE_STAFF', $user->getRoles(), true);
    }

    public static function isFocal(User $user): bool
    {
        return in_array('ROLE_USER', $user->getRoles(), true)
            && !self::isStaff($user)
            && !self::hasFullDataAccess($user);
    }

    public static function isCountryRestricted(User $user): bool
    {
        return self::isFocal($user);
    }

    public static function translationKey(User $user): string
    {
        if (self::isSuperAdmin($user)) {
            return 'user.profile.super_admin';
        }

        return match (self::normalize((string) $user->getProfil())) {
            self::STAFF => 'user.profile.staff',
            self::ADMIN => 'user.profile.admin',
            default => 'user.profile.focal',
        };
    }
}
