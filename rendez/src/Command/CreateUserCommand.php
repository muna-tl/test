<?php

namespace App\Command;

use App\Entity\User;
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
    name: 'app:create-user',
    description: 'Créer un nouvel utilisateur',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse email de l\'utilisateur')
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe')
            ->addArgument('firstName', InputArgument::REQUIRED, 'Prénom')
            ->addArgument('lastName', InputArgument::REQUIRED, 'Nom')
            ->addOption('role', 'r', InputOption::VALUE_REQUIRED, 'Rôle (patient, doctor, admin)', 'patient')
            ->addOption('phone', 'p', InputOption::VALUE_REQUIRED, 'Numéro de téléphone')
            ->addOption('gender', 'g', InputOption::VALUE_REQUIRED, 'Sexe (male, female)', 'male');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $firstName = $input->getArgument('firstName');
        $lastName = $input->getArgument('lastName');
        $role = $input->getOption('role');
        $phone = $input->getOption('phone');
        $gender = $input->getOption('gender');

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->entityManager->getRepository(User::class)->findOneByEmail($email);
        if ($existingUser) {
            $io->error('Un utilisateur avec cette adresse email existe déjà.');
            return Command::FAILURE;
        }

        // Créer le nouvel utilisateur
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setGender($gender);
        
        if ($phone) {
            $user->setPhone($phone);
        }

        // Définir le rôle
        switch (strtolower($role)) {
            case 'admin':
                $user->setRoles([User::ROLE_ADMIN]);
                break;
            case 'doctor':
                $user->setRoles([User::ROLE_DOCTOR]);
                break;
            case 'patient':
            default:
                $user->setRoles([User::ROLE_PATIENT]);
                break;
        }

        // Hasher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        
        // Marquer comme vérifié
        $user->setIsVerified(true);

        // Sauvegarder en base
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Utilisateur créé avec succès !');
        $io->table(['Champ', 'Valeur'], [
            ['Email', $user->getEmail()],
            ['Nom complet', $user->getFullName()],
            ['Rôle', $user->getRoleLabel()],
            ['Téléphone', $user->getPhone() ?? 'Non renseigné'],
            ['Créé le', $user->getCreatedAt()->format('d/m/Y H:i:s')]
        ]);

        return Command::SUCCESS;
    }
}

