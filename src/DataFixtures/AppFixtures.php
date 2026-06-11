<?php

namespace App\DataFixtures;

use App\Entity\Pays;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture implements DependentFixtureInterface
{
    public const ADMIN_EMAIL = 'abdoulhafidayeva@gmail.com';
    public const ADMIN_PASSWORD = 'n4n86fgh';
    public const FOCAL_EMAIL = 'focal.mali@caert.test';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->ensureAdmin($manager);
        $this->ensureFocalUser($manager);
        $manager->flush();
    }

    private function ensureAdmin(ObjectManager $manager): void
    {
        if ($this->userRepository->findOneByEmail(self::ADMIN_EMAIL) !== null) {
            return;
        }

        $user = new User();
        $user->setName('Ayeva');
        $user->setPrenoms('Abdoulhafid');
        $user->setEmail(self::ADMIN_EMAIL);
        $user->setFonction('Administrateur CAERT');
        $user->setProfil('Administrateur');
        $user->setOrganisation('AUCTC');
        $user->setEnable(true);
        $user->setIsVerified(true);
        $user->setNotifyBy(0);
        $user->setRoles(['ROLE_SUPER_ADMIN']);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, self::ADMIN_PASSWORD)
        );

        $manager->persist($user);
    }

    private function ensureFocalUser(ObjectManager $manager): void
    {
        if ($this->userRepository->findOneByEmail(self::FOCAL_EMAIL) !== null) {
            return;
        }

        $mali = $manager->getRepository(Pays::class)->findOneBy(['libelle' => 'MALI']);
        if ($mali === null) {
            return;
        }

        $user = new User();
        $user->setName('Diallo');
        $user->setPrenoms('Aminata');
        $user->setEmail(self::FOCAL_EMAIL);
        $user->setFonction('Point focal');
        $user->setProfil('Point focal Pays');
        $user->setOrganisation('Mali');
        $user->setEnable(true);
        $user->setIsVerified(true);
        $user->setNotifyBy(0);
        $user->setRoles(['ROLE_USER']);
        $user->setPays($mali);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, self::ADMIN_PASSWORD)
        );

        $manager->persist($user);
    }

    public function getDependencies(): array
    {
        return [RegionFixtures::class, RegionSuiteFixtures::class];
    }
}
