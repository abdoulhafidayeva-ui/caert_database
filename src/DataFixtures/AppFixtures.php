<?php

namespace App\DataFixtures;

use App\Entity\Pays;
use App\Entity\Region;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Security\UserProfile;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Comptes de démonstration CAERT — un exemple par profil métier.
 * Rechargeable : met à jour profil/rôles des comptes connus et réconcilie les utilisateurs existants.
 */
class AppFixtures extends Fixture implements DependentFixtureInterface
{
    public const DEMO_PASSWORD = 'n4n86fgh';

    /** Super administrateur système (gestion utilisateurs + paramètres). */
    public const SUPER_ADMIN_EMAIL = 'abdoulhafidayeva@gmail.com';

    /** @deprecated Utiliser SUPER_ADMIN_EMAIL */
    public const ADMIN_EMAIL = self::SUPER_ADMIN_EMAIL;

    /** Staff AUCTC — validation et gestion multi-pays. */
    public const STAFF_EMAIL = 'staff.caert@caert.test';

    /** Administrateur métier — accès données sans restriction (hors admin système). */
    public const ADMIN_METIER_EMAIL = 'admin.caert@caert.test';

    /** Point focal Mali — saisie limitée à son pays. */
    public const FOCAL_EMAIL = 'focal.mali@caert.test';

    public const DEMO_REGION = "AFRIQUE DE L'OUEST";

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->reconcileExistingUsers();

        foreach ($this->userDefinitions($manager) as $definition) {
            $this->upsertUser($manager, $definition);
        }

        $manager->flush();
    }

    /**
     * Normalise les profils et réaligne les rôles sur le modèle profil → rôle.
     */
    private function reconcileExistingUsers(): void
    {
        foreach ($this->userRepository->findAll() as $user) {
            $profil = UserProfile::normalize((string) ($user->getProfil() ?? UserProfile::FOCAL));
            $user->setProfil($profil);
            $user->setRoles(UserProfile::resolveRoles($profil, UserProfile::isSuperAdmin($user)));
            if ($user->getRegion() === null && $user->getPays()?->getRegion() !== null) {
                $user->setRegion($user->getPays()->getRegion());
            }
        }
    }

    /**
     * @return list<array{
     *     email: string,
     *     name: string,
     *     prenoms: string,
     *     fonction: string,
     *     profil: string,
     *     roles: list<string>,
     *     organisation: string,
     *     pays?: string|null,
     *     locale?: string|null
     * }>
     */
    private function userDefinitions(ObjectManager $manager): array
    {
        return [
            [
                'email' => self::SUPER_ADMIN_EMAIL,
                'name' => 'Ayeva',
                'prenoms' => 'Abdoulhafid',
                'fonction' => 'Super administrateur CAERT',
                'profil' => UserProfile::ADMIN,
                'roles' => UserProfile::resolveRoles(UserProfile::ADMIN, true),
                'organisation' => 'AUCTC',
                'locale' => 'fr',
            ],
            [
                'email' => self::STAFF_EMAIL,
                'name' => 'Koné',
                'prenoms' => 'Fatou',
                'fonction' => 'Analyste régional',
                'profil' => UserProfile::STAFF,
                'roles' => UserProfile::resolveRoles(UserProfile::STAFF),
                'organisation' => 'AUCTC',
                'region' => self::DEMO_REGION,
                'locale' => 'fr',
            ],
            [
                'email' => self::ADMIN_METIER_EMAIL,
                'name' => 'Traoré',
                'prenoms' => 'Ibrahim',
                'fonction' => 'Administrateur données',
                'profil' => UserProfile::ADMIN,
                'roles' => UserProfile::resolveRoles(UserProfile::ADMIN),
                'organisation' => 'AUCTC',
                'locale' => 'fr',
            ],
            [
                'email' => self::FOCAL_EMAIL,
                'name' => 'Diallo',
                'prenoms' => 'Aminata',
                'fonction' => 'Point focal national',
                'profil' => UserProfile::FOCAL,
                'roles' => UserProfile::resolveRoles(UserProfile::FOCAL),
                'organisation' => 'Mali — Ministère',
                'region' => self::DEMO_REGION,
                'pays' => 'MALI',
                'locale' => 'fr',
            ],
        ];
    }

    /**
     * @param array{
     *     email: string,
     *     name: string,
     *     prenoms: string,
     *     fonction: string,
     *     profil: string,
     *     roles: list<string>,
     *     organisation: string,
     *     pays?: string|null,
     *     locale?: string|null
     * } $definition
     */
    private function upsertUser(ObjectManager $manager, array $definition): void
    {
        $user = $this->userRepository->findOneByEmail($definition['email']);
        $isNew = $user === null;

        if ($isNew) {
            $user = new User();
            $user->setEmail($definition['email']);
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, self::DEMO_PASSWORD)
            );
        }

        $user->setName($definition['name']);
        $user->setPrenoms($definition['prenoms']);
        $user->setFonction($definition['fonction']);
        $user->setProfil($definition['profil']);
        $user->setRoles($definition['roles']);
        $user->setOrganisation($definition['organisation']);
        $user->setEnable(true);
        $user->setIsVerified(true);
        $user->setNotifyBy(0);
        $user->setToken(null);
        $user->setTokenCreatedAt(null);

        if (!empty($definition['locale'])) {
            $user->setLocale($definition['locale']);
        }

        if (!empty($definition['region'])) {
            $region = $manager->getRepository(Region::class)->findOneBy(['libelle' => $definition['region']]);
            $user->setRegion($region);
        }

        if (!empty($definition['pays'])) {
            $pays = $manager->getRepository(Pays::class)->findOneBy(['libelle' => $definition['pays']]);
            $user->setPays($pays);
        } elseif (!array_key_exists('region', $definition)) {
            $user->setPays(null);
        }

        if ($isNew) {
            $manager->persist($user);
        }
    }

    public function getDependencies(): array
    {
        return [RegionFixtures::class, RegionSuiteFixtures::class];
    }
}
