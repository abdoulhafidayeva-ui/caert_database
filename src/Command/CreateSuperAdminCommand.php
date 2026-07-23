<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Security\UserProfile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-super-admin',
    description: 'Crée ou met à jour un compte super administrateur actif.',
)]
final class CreateSuperAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse e-mail du super administrateur')
            ->addOption('password-env', null, InputOption::VALUE_REQUIRED, 'Variable d’environnement contenant le mot de passe', 'CAERT_SUPER_ADMIN_PASSWORD')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Nom', 'Administrateur')
            ->addOption('first-name', null, InputOption::VALUE_REQUIRED, 'Prénom(s)', 'Super');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = mb_strtolower(trim((string) $input->getArgument('email')));
        $passwordEnv = (string) $input->getOption('password-env');
        $plainPassword = getenv($passwordEnv);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('Adresse e-mail invalide.');

            return Command::INVALID;
        }

        if (!is_string($plainPassword) || $plainPassword === '') {
            $io->error(sprintf('Définissez la variable d’environnement %s avant de lancer la commande.', $passwordEnv));

            return Command::FAILURE;
        }

        $user = $this->users->findOneBy(['email' => $email]) ?? new User();
        $isNew = $user->getId() === null;

        $user
            ->setEmail($email)
            ->setName((string) $input->getOption('name'))
            ->setPrenoms((string) $input->getOption('first-name'))
            ->setFonction('Super administrateur')
            ->setProfil(UserProfile::ADMIN)
            ->setRoles(['ROLE_SUPER_ADMIN'])
            ->setOrganisation('AUCTC')
            ->setLocale('fr')
            ->setNotifyBy(0)
            ->setEnable(true)
            ->setIsVerified(true)
            ->setToken(null)
            ->setTokenCreatedAt(null);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        if ($isNew) {
            $this->em->persist($user);
        }
        $this->em->flush();

        $io->success(sprintf('Compte super administrateur %s prêt (ID %d).', $email, $user->getId()));

        return Command::SUCCESS;
    }
}
