<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\DoctorProfile;
use App\Entity\Appointment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:create-booking', description: 'Créer un rendez-vous pour un patient avec un docteur (simulate booking)')]
class CreateBookingCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('doctor', null, InputOption::VALUE_REQUIRED, 'Email du docteur', 'doc1@example.com')
            ->addOption('patient', null, InputOption::VALUE_REQUIRED, 'Email du patient', 'patient@example.com')
            ->addOption('datetime', null, InputOption::VALUE_REQUIRED, 'Date et heure (Y-m-d H:i)', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $doctorEmail = $input->getOption('doctor');
        $patientEmail = $input->getOption('patient');
        $datetime = $input->getOption('datetime');

        $doctorUser = $this->em->getRepository(User::class)->findOneBy(['email' => $doctorEmail]);
        if (!$doctorUser) {
            $io->error("Docteur non trouvé: $doctorEmail");
            return Command::FAILURE;
        }

        $patientUser = $this->em->getRepository(User::class)->findOneBy(['email' => $patientEmail]);
        if (!$patientUser) {
            $io->error("Patient non trouvé: $patientEmail");
            return Command::FAILURE;
        }

        $doctorProfile = $this->em->getRepository(DoctorProfile::class)->findOneBy(['user' => $doctorUser]);
        if (!$doctorProfile) {
            $io->error("Profil docteur introuvable pour $doctorEmail");
            return Command::FAILURE;
        }

        if (!$datetime) {
            // default to tomorrow at 11:00
            $start = new \DateTime('+1 day');
            $start->setTime(11, 0);
        } else {
            $start = \DateTime::createFromFormat('Y-m-d H:i', $datetime);
            if (!$start) {
                $io->error('Format de date invalide. Utilisez Y-m-d H:i');
                return Command::FAILURE;
            }
        }

        $end = (clone $start)->modify('+30 minutes');

        $appointment = new Appointment();
        $appointment->setDoctor($doctorProfile);
        $appointment->setPatient($patientUser);
        $appointment->setStartAt($start);
        $appointment->setEndAt($end);

        try {
            $this->em->getConnection()->beginTransaction();
            $this->em->persist($appointment);
            $this->em->flush();
            $this->em->getConnection()->commit();

            $io->success('Rendez-vous créé (ID: ' . $appointment->getId() . ')');
            $io->writeln('URL de confirmation: /appointment/confirm/' . $appointment->getId());
        } catch (\Throwable $e) {
            // rollback if possible
            try {
                $this->em->getConnection()->rollBack();
            } catch (\Throwable $rollbackEx) {
                // ignore
            }
            $io->error('Erreur lors de la création du rendez-vous: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
