<?php

namespace App\Controller;

use App\Entity\DoctorProfile;
use App\Entity\Appointment;
use App\Entity\Specialty;
use App\Repository\SpecialtyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use Dompdf\Dompdf;
use Dompdf\Options;

class AppointmentController extends AbstractController
{
    #[Route('/appointment/pdf/{id}', name: 'appointment_pdf')]
    public function pdf(Appointment $id, EntityManagerInterface $em): Response
    {
        $appointment = $em->getRepository(Appointment::class)->find($id->getId());
        // Extract all info into simple variables
        $patientName = $appointment && $appointment->getPatient() ? $appointment->getPatient()->getFullName() : '';
        $doctorName = $appointment && $appointment->getDoctor() && $appointment->getDoctor()->getUser() ? $appointment->getDoctor()->getUser()->getFullName() : '';
        $date = $appointment && $appointment->getStartAt() ? $appointment->getStartAt()->format('d/m/Y') : '';
        $heureStart = $appointment && $appointment->getStartAt() ? $appointment->getStartAt()->format('H:i') : '';
        $heureEnd = $appointment && $appointment->getEndAt() ? $appointment->getEndAt()->format('H:i') : '';
        $confirmationCode = $appointment ? $appointment->getConfirmationCode() : '';

        $html = $this->renderView('appointment/pdf.html.twig', [
            'patientName' => $patientName,
            'doctorName' => $doctorName,
            'date' => $date,
            'heureStart' => $heureStart,
            'heureEnd' => $heureEnd,
            'confirmationCode' => $confirmationCode
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'confirmation_rdv_' . $confirmationCode . '.pdf';
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }

    #[Route('/appointment/verify/{code}', name: 'appointment_verify')]
    public function verifyConfirmationCode(string $code, EntityManagerInterface $em): Response
    {
        // Find appointment by confirmation code
        $appointment = $em->getRepository(Appointment::class)->findOneBy(['confirmationCode' => strtoupper($code)]);

        if (!$appointment) {
            return $this->render('appointment/verify.html.twig', [
                'found' => false,
                'code' => strtoupper($code)
            ]);
        }

        return $this->render('appointment/verify.html.twig', [
            'found' => true,
            'appointment' => $appointment
        ]);
    }

    #[Route('/prendre-rdv', name: 'appointment_specialties')]
    public function specialties(SpecialtyRepository $specialtyRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PATIENT');

        // Get only active specialties configured by admin
        $activeSpecialties = $specialtyRepository->findActiveSpecialties();
        
        // Extract specialty names for the template
        $specialties = array_map(fn($spec) => $spec->getName(), $activeSpecialties);

        // If no active specialties configured by admin, show message
        if (empty($specialties)) {
            $this->addFlash('info', 'Aucune spécialité n\'est actuellement disponible. Veuillez contacter l\'administration.');
        }

        return $this->render('appointment/specialties.html.twig', [
            'specialties' => $specialties,
            'activeSpecialties' => $activeSpecialties,
        ]);
    }

    #[Route('/doctors/{specialty}', name: 'appointment_doctors')]
    public function doctors(string $specialty, EntityManagerInterface $em, SpecialtyRepository $specialtyRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PATIENT');

        // Trouver l'entité Specialty par son nom
        $specialtyEntity = $specialtyRepository->findOneBy(['name' => $specialty]);
        
        if (!$specialtyEntity) {
            $this->addFlash('error', 'Spécialité non trouvée.');
            return $this->redirectToRoute('appointment_specialties');
        }

        // Trouver les docteurs de cette spécialité
        $doctors = $em->getRepository(DoctorProfile::class)->findBy(['specialty' => $specialtyEntity]);

        // Generate available slots per doctor for the next 7 days
        $slotsByDoctor = [];
        $days = 7;
        $slotMinutes = 30;
        $workStart = 9; // 9:00
        $workEnd = 17; // 17:00

        $now = new \DateTime();
        $endDate = (clone $now)->modify("+$days days");

        foreach ($doctors as $doctor) {
            // load appointments for the period
            $qb = $em->getRepository(Appointment::class)->createQueryBuilder('a')
                ->where('a.doctor = :doc')
                ->andWhere('a.startAt BETWEEN :from AND :to')
                ->setParameter('doc', $doctor)
                ->setParameter('from', $now->format('Y-m-d 00:00:00'))
                ->setParameter('to', $endDate->format('Y-m-d 23:59:59'));

            $appts = $qb->getQuery()->getResult();
            $occupied = [];
            foreach ($appts as $a) {
                $occupied[] = $a->getStartAt()->format('Y-m-d H:i');
            }

            $slots = [];
            $cursor = (clone $now)->setTime($workStart,0,0);
            $lastDay = (clone $now)->modify("+$days days");
            while ($cursor <= $lastDay) {
                $hour = (int)$cursor->format('H');
                if ($hour >= $workStart && $hour < $workEnd) {
                    $slotStart = clone $cursor;
                    $slotKey = $slotStart->format('Y-m-d H:i');
                    if (!in_array($slotKey, $occupied)) {
                        // Only future slots
                        if ($slotStart > new \DateTime()) {
                            $slots[] = ['date' => $slotStart->format('Y-m-d'), 'time' => $slotStart->format('H:i')];
                        }
                    }
                    $cursor->modify("+$slotMinutes minutes");
                } else {
                    // move to next day's start
                    $cursor->modify('+1 day');
                    $cursor->setTime($workStart,0,0);
                }
            }

            $slotsByDoctor[$doctor->getId()] = $slots;
        }

        return $this->render('appointment/doctors.html.twig', [
            'specialty' => $specialty,
            'doctors' => $doctors,
            'slotsByDoctor' => $slotsByDoctor,
        ]);
    }

    #[Route('/book/{id}', name: 'appointment_book')]
    public function book(DoctorProfile $id, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PATIENT');

        $doctor = $id;

        // For simplicity, we'll show a week calendar and accept a datetime parameter
        if ($request->isMethod('POST')) {
            $date = $request->request->get('date');
            $time = $request->request->get('time');

            if ($date && $time) {
                $start = new \DateTime($date . ' ' . $time);
                $end = (clone $start)->modify('+30 minutes');

                $appointment = new Appointment();
                $appointment->setDoctor($doctor);
                $appointment->setPatient($this->getUser());
                $appointment->setStartAt($start);
                $appointment->setEndAt($end);
                $appointment->setStatus(Appointment::STATUS_CONFIRMED);
                
                // Set the required appointmentDate and appointmentTime fields
                $appointment->setAppointmentDate(new \DateTime($date));
                $appointment->setAppointmentTime(new \DateTime($time));
                
                // Generate unique confirmation code
                $confirmationCode = $this->generateConfirmationCode($em);
                $appointment->setConfirmationCode($confirmationCode);

                $em->persist($appointment);
                $em->flush();

                return $this->redirectToRoute('appointment_confirm', ['id' => $appointment->getId()]);
            }
        }

        // load existing appointments for this doctor to show on the calendar
        $appointments = $em->getRepository(Appointment::class)->findBy(['doctor' => $doctor]);

        // Prefill date/time from query params if present
        $prefillDate = $request->query->get('date');
        $prefillTime = $request->query->get('time');

        return $this->render('appointment/book.html.twig', [
            'doctor' => $doctor,
            'appointments' => $appointments,
            'prefillDate' => $prefillDate,
            'prefillTime' => $prefillTime,
        ]);
    }

    #[Route('/appointment/confirm/{id}', name: 'appointment_confirm')]
    public function confirm(Appointment $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PATIENT');

        $appointment = $id;

        return $this->render('appointment/confirm.html.twig', [
            'appointment' => $appointment,
        ]);
    }

    #[Route('/doctor/calendar', name: 'doctor_calendar')]
    public function doctorCalendar(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_DOCTOR');

        // find doctor profile for current user
        $doctor = $em->getRepository(DoctorProfile::class)->findOneBy(['user' => $this->getUser()]);

        $appointments = [];
        if ($doctor) {
            $appointments = $em->getRepository(Appointment::class)->findBy(['doctor' => $doctor]);
        }

        return $this->render('appointment/doctor_calendar.html.twig', [
            'appointments' => $appointments,
        ]);
    }

    /**
     * Generate a unique confirmation code for an appointment
     */
    private function generateConfirmationCode(EntityManagerInterface $em): string
    {
        do {
            // Generate a 4-character PIN where the first char is a letter (A-Z)
            // and the next 3 chars are digits (000-999), e.g., A123, Z045
            $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $first = $letters[random_int(0, strlen($letters) - 1)];
            $digits = str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT);
            $code = $first . $digits;

            // Ensure uniqueness
            $existing = $em->getRepository(Appointment::class)->findOneBy(['confirmationCode' => $code]);
        } while ($existing !== null);

        return $code;
    }
}
