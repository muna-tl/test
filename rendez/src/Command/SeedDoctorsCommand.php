<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\DoctorProfile;
use App\Entity\Appointment;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
// Note: keep property untyped with a docblock and use the fully-qualified type in the
// constructor so Symfony can autowire the password hasher while avoiding some static
// analysis issues in certain environments.

#[AsCommand(name: 'app:seed-doctors', description: 'Seed doctors with common specialties')]
class SeedDoctorsCommand extends Command
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher)
    {
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
        parent::__construct();
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $specialties = [
            'Cardiologie',
            'Ophtalmologie',
            'Médecin généraliste',
            'Neurologie',
            'Psychiatrie'
        ];

        foreach ($specialties as $i => $spec) {
            // create a doctor user
            $email = 'doc' . ($i+1) . '@example.com';
            $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existing) {
                $io->writeln("Utilisateur $email existe déjà, saut...");
                // ensure doctor profile exists
                $profile = $this->em->getRepository(DoctorProfile::class)->findOneBy(['user' => $existing]);
                if (!$profile) {
                    $profile = new DoctorProfile();
                    $profile->setUser($existing);
                    $profile->setSpecialty($spec);
                    $this->em->persist($profile);
                }
                continue;
            }

            $user = new User();
            $user->setEmail($email);
            $user->setFirstName('Doc' . ($i+1));
            $user->setLastName($spec);
            $user->setRoles([User::ROLE_DOCTOR]);
            $user->setIsVerified(true);

            $pwd = 'password123';
            $user->setPassword($this->passwordHasher->hashPassword($user, $pwd));

            $this->em->persist($user);
            $this->em->flush();

            $profile = new DoctorProfile();
            $profile->setUser($user);
            $profile->setSpecialty($spec);
            $this->em->persist($profile);
            $this->em->flush();

            $io->writeln("Créé docteur $email (mot de passe: $pwd) spécialité: $spec");
        }

        // Create a test patient
        $patientEmail = 'patient@example.com';
        $patient = $this->em->getRepository(User::class)->findOneBy(['email' => $patientEmail]);
        if (!$patient) {
            $patient = new User();
            $patient->setEmail($patientEmail);
            $patient->setFirstName('Patient');
            $patient->setLastName('Test');
            $patient->setRoles([User::ROLE_PATIENT]);
            $patient->setIsVerified(true);
            $pwd = 'password123';
            $patient->setPassword($this->passwordHasher->hashPassword($patient, $pwd));
            $this->em->persist($patient);
            $this->em->flush();
            $io->writeln("Créé patient $patientEmail (mot de passe: $pwd)");
        } else {
            $io->writeln("Patient $patientEmail existe déjà, saut...");
        }

        // Create a demo appointment with doc1 if possible
        $doc1 = $this->em->getRepository(User::class)->findOneBy(['email' => 'doc1@example.com']);
        if ($doc1) {
            $docProfile = $this->em->getRepository(DoctorProfile::class)->findOneBy(['user' => $doc1]);
            if ($docProfile) {
                // check if an appointment already exists
                $existingAppt = $this->em->getRepository(Appointment::class)->findOneBy(['doctor' => $docProfile]);
                if (!$existingAppt) {
                    $start = (new DateTime())->modify('+2 days')->setTime(10,0);
                    $end = (clone $start)->modify('+30 minutes');
                    $appt = new Appointment();
                    $appt->setDoctor($docProfile);
                    $appt->setPatient($patient);
                    $appt->setStartAt($start);
                    $appt->setEndAt($end);
                    $this->em->persist($appt);
                    $this->em->flush();
                    $io->writeln('Créé un rendez-vous démo avec doc1 dans 2 jours à 10:00');
                } else {
                    $io->writeln('Un rendez-vous démo pour doc1 existe déjà, saut...');
                }
            }
        }

        $io->success('Seed des docteurs et patient démo terminé.');
        return Command::SUCCESS;
    }
}
