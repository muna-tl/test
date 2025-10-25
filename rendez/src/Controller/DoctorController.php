<?php
namespace App\Controller;

use App\Entity\Appointment;
use App\Repository\AppointmentRepository;
use App\Repository\DoctorProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_DOCTOR')]
class DoctorController extends AbstractController
{
    public function __construct(
        private AppointmentRepository $appointmentRepository,
        private DoctorProfileRepository $doctorProfileRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/doctor/dashboard', name: 'doctor_dashboard')]
    public function dashboard(Request $request): Response
    {
        $user = $this->getUser();
        $doctorProfile = $this->doctorProfileRepository->findOneBy(['user' => $user]);
        if (!$doctorProfile) {
            throw $this->createNotFoundException('Profil docteur non trouvé');
        }

        $filterDay = $request->query->get('filter_day');
        $filterMonth = $request->query->get('filter_month');
        $filterYear = $request->query->get('filter_year');

        // Récupérer les rendez-vous filtrés pour ce docteur
        $qb = $this->appointmentRepository->createQueryBuilder('a')
            ->andWhere('a.doctor = :doctor')
            ->setParameter('doctor', $doctorProfile)
            ->orderBy('a.appointmentDate', 'ASC')
            ->addOrderBy('a.appointmentTime', 'ASC');

        if ($filterYear) {
            $yearStart = new \DateTime("$filterYear-01-01");
            $yearEnd = new \DateTime("$filterYear-12-31");
            $qb->andWhere('a.appointmentDate >= :yearStart AND a.appointmentDate <= :yearEnd')
                ->setParameter('yearStart', $yearStart)
                ->setParameter('yearEnd', $yearEnd);
        }
        if ($filterMonth) {
            $monthStr = str_pad($filterMonth, 2, '0', STR_PAD_LEFT);
            $start = $filterYear ? "$filterYear-$monthStr-01" : date('Y')."-$monthStr-01";
            $end = $filterYear ? "$filterYear-$monthStr-31" : date('Y')."-$monthStr-31";
            $qb->andWhere('a.appointmentDate >= :monthStart AND a.appointmentDate <= :monthEnd')
                ->setParameter('monthStart', new \DateTime($start))
                ->setParameter('monthEnd', new \DateTime($end));
        }
        if ($filterDay) {
            if ($filterYear && $filterMonth) {
                $monthStr = str_pad($filterMonth, 2, '0', STR_PAD_LEFT);
                $dayStr = str_pad($filterDay, 2, '0', STR_PAD_LEFT);
                $dateStr = "$filterYear-$monthStr-$dayStr";
                $qb->andWhere('a.appointmentDate = :dayDate')
                    ->setParameter('dayDate', new \DateTime($dateStr));
            }
        }

        $appointments = $qb->getQuery()->getResult();

        $today = (new \DateTime())->setTime(0,0,0);
        $tomorrow = (clone $today)->add(new \DateInterval('P1D'));
        $nextWeekStart = (clone $today)->add(new \DateInterval('P2D'));
        $nextWeekEnd = (clone $today)->add(new \DateInterval('P8D'));
        $nextMonthStart = (clone $today)->add(new \DateInterval('P8D'));
        $nextMonthEnd = (clone $today)->add(new \DateInterval('P32D'));

        $groupedAppointments = [
            'today' => [],
            'tomorrow' => [],
            'next_week' => [],
            'next_month' => [],
        ];

        foreach ($appointments as $appointment) {
            $date = $appointment->getAppointmentDate();
            if ($date >= $today && $date < $tomorrow) {
                $groupedAppointments['today'][] = $appointment;
            } elseif ($date >= $tomorrow && $date < $nextWeekStart) {
                $groupedAppointments['tomorrow'][] = $appointment;
            } elseif ($date >= $nextWeekStart && $date < $nextWeekEnd) {
                $groupedAppointments['next_week'][] = $appointment;
            } elseif ($date >= $nextMonthStart && $date < $nextMonthEnd) {
                $groupedAppointments['next_month'][] = $appointment;
            }
        }

        return $this->render('doctor/dashboard.html.twig', [
            'doctor' => $doctorProfile,
            'groupedAppointments' => $groupedAppointments,
            'filterDay' => $filterDay,
            'filterMonth' => $filterMonth,
            'filterYear' => $filterYear,
        ]);
    }

        #[Route('/doctor/calendar', name: 'doctor_calendar')]
        public function calendar(Request $request): Response
        {
            $user = $this->getUser();
            $doctorProfile = $this->doctorProfileRepository->findOneBy(['user' => $user]);
            if (!$doctorProfile) {
                throw $this->createNotFoundException('Profil docteur non trouvé');
            }

            $filterSchedule = $request->query->get('filter_schedule');
            $filterDay = $request->query->get('filter_day');
            $filterMonth = $request->query->get('filter_month');
            $filterYear = $request->query->get('filter_year');

            // Récupérer les rendez-vous filtrés pour ce docteur
            $qb = $this->appointmentRepository->createQueryBuilder('a')
                ->andWhere('a.doctor = :doctor')
                ->setParameter('doctor', $doctorProfile)
                ->orderBy('a.appointmentDate', 'ASC')
                ->addOrderBy('a.appointmentTime', 'ASC');

            // Filtrage par plage horaire
            if ($filterSchedule) {
                if ($filterSchedule == 'morning') {
                    $qb->andWhere('a.appointmentTime >= :start AND a.appointmentTime < :end')
                        ->setParameter('start', '08:00')
                        ->setParameter('end', '12:00');
                } elseif ($filterSchedule == 'afternoon') {
                    $qb->andWhere('a.appointmentTime >= :start AND a.appointmentTime < :end')
                        ->setParameter('start', '12:00')
                        ->setParameter('end', '17:00');
                } elseif ($filterSchedule == 'evening') {
                    $qb->andWhere('a.appointmentTime >= :start AND a.appointmentTime < :end')
                        ->setParameter('start', '17:00')
                        ->setParameter('end', '21:00');
                }
            }
            if ($filterYear) {
                $yearStart = new \DateTime("$filterYear-01-01");
                $yearEnd = new \DateTime("$filterYear-12-31");
                $qb->andWhere('a.appointmentDate >= :yearStart AND a.appointmentDate <= :yearEnd')
                    ->setParameter('yearStart', $yearStart)
                    ->setParameter('yearEnd', $yearEnd);
            }
            if ($filterMonth) {
                $monthStr = str_pad($filterMonth, 2, '0', STR_PAD_LEFT);
                $start = $filterYear ? "$filterYear-$monthStr-01" : date('Y')."-$monthStr-01";
                $end = $filterYear ? "$filterYear-$monthStr-31" : date('Y')."-$monthStr-31";
                $qb->andWhere('a.appointmentDate >= :monthStart AND a.appointmentDate <= :monthEnd')
                    ->setParameter('monthStart', new \DateTime($start))
                    ->setParameter('monthEnd', new \DateTime($end));
            }
            if ($filterDay) {
                if ($filterYear && $filterMonth) {
                    $monthStr = str_pad($filterMonth, 2, '0', STR_PAD_LEFT);
                    $dayStr = str_pad($filterDay, 2, '0', STR_PAD_LEFT);
                    $dateStr = "$filterYear-$monthStr-$dayStr";
                    $qb->andWhere('a.appointmentDate = :dayDate')
                        ->setParameter('dayDate', new \DateTime($dateStr));
                }
            }

            $appointments = $qb->getQuery()->getResult();

            // Grouper les rendez-vous par période
            $today = (new \DateTime())->setTime(0,0,0);
            $tomorrow = (clone $today)->add(new \DateInterval('P1D'));
            $nextWeekStart = (clone $today)->add(new \DateInterval('P2D'));
            $nextWeekEnd = (clone $today)->add(new \DateInterval('P8D'));
            $nextMonthStart = (clone $today)->add(new \DateInterval('P8D'));
            $nextMonthEnd = (clone $today)->add(new \DateInterval('P32D'));

            $groupedAppointments = [
                'today' => [],
                'tomorrow' => [],
                'next_week' => [],
                'next_month' => [],
            ];

            foreach ($appointments as $appointment) {
                $date = $appointment->getAppointmentDate();
                if ($date >= $today && $date < $tomorrow) {
                    $groupedAppointments['today'][] = $appointment;
                } elseif ($date >= $tomorrow && $date < $nextWeekStart) {
                    $groupedAppointments['tomorrow'][] = $appointment;
                } elseif ($date >= $nextWeekStart && $date < $nextWeekEnd) {
                    $groupedAppointments['next_week'][] = $appointment;
                } elseif ($date >= $nextMonthStart && $date < $nextMonthEnd) {
                    $groupedAppointments['next_month'][] = $appointment;
                }
            }

            return $this->render('doctor/calendar.html.twig', [
                'doctor' => $doctorProfile,
                'groupedAppointments' => $groupedAppointments,
                'filterSchedule' => $filterSchedule,
                'filterDay' => $filterDay,
                'filterMonth' => $filterMonth,
                'filterYear' => $filterYear,
            ]);
        }
    #[Route('/doctor/appointment/{id}/status', name: 'doctor_update_status', methods: ['POST'])]
    public function updateAppointmentStatus(Appointment $appointment, Request $request): Response
    {
        $user = $this->getUser();
        $doctorProfile = $this->doctorProfileRepository->findOneBy(['user' => $user]);
        
        // Vérifier que le docteur peut modifier ce rendez-vous
        if ($appointment->getDoctor()->getId() !== $doctorProfile->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce rendez-vous');
        }

        $newStatus = $request->request->get('status');
        
        if (in_array($newStatus, [Appointment::STATUS_CONFIRMED, Appointment::STATUS_DONE, Appointment::STATUS_CANCELED])) {
            $appointment->setStatus($newStatus);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Statut du rendez-vous mis à jour avec succès');
        } else {
            $this->addFlash('error', 'Statut invalide');
        }

        return $this->redirectToRoute('doctor_dashboard');
    }

    #[Route('/doctor/patients-history', name: 'doctor_patients_history')]
    public function patientsHistory(Request $request): Response
    {
        $user = $this->getUser();
        $doctorProfile = $this->doctorProfileRepository->findOneBy(['user' => $user]);
        if (!$doctorProfile) {
            throw $this->createNotFoundException('Profil docteur non trouvé');
        }

        $filterDay = $request->query->get('filter_day');
        $filterMonth = $request->query->get('filter_month');
        $filterYear = $request->query->get('filter_year');

        $qb = $this->appointmentRepository->createQueryBuilder('a')
            ->andWhere('a.doctor = :doctor')
            ->setParameter('doctor', $doctorProfile)
            ->orderBy('a.appointmentDate', 'DESC');

        if ($filterYear) {
            $qb->andWhere('a.appointmentDate >= :yearStart AND a.appointmentDate <= :yearEnd')
                ->setParameter('yearStart', new \DateTime("$filterYear-01-01"))
                ->setParameter('yearEnd', new \DateTime("$filterYear-12-31"));
        }
        if ($filterMonth) {
            $monthStr = str_pad($filterMonth, 2, '0', STR_PAD_LEFT);
            $start = isset($filterYear) ? "$filterYear-$monthStr-01" : date('Y')."-$monthStr-01";
            $end = isset($filterYear) ? "$filterYear-$monthStr-31" : date('Y')."-$monthStr-31";
            $qb->andWhere('a.appointmentDate >= :monthStart AND a.appointmentDate <= :monthEnd')
                ->setParameter('monthStart', new \DateTime($start))
                ->setParameter('monthEnd', new \DateTime($end));
        }
        if ($filterDay) {
            if ($filterYear && $filterMonth) {
                $monthStr = str_pad($filterMonth, 2, '0', STR_PAD_LEFT);
                $dayStr = str_pad($filterDay, 2, '0', STR_PAD_LEFT);
                $dateStr = "$filterYear-$monthStr-$dayStr";
                $qb->andWhere('a.appointmentDate = :dayDate')
                    ->setParameter('dayDate', new \DateTime($dateStr));
            }
        }

        $appointments = $qb->getQuery()->getResult();

        // Organiser les rendez-vous par patient
        $patientAppointments = [];
        foreach ($appointments as $appointment) {
            $patientId = $appointment->getPatient()->getId();
            if (!isset($patientAppointments[$patientId])) {
                $patientAppointments[$patientId] = [
                    'patient' => $appointment->getPatient(),
                    'appointments' => []
                ];
            }
            $patientAppointments[$patientId]['appointments'][] = $appointment;
        }

        return $this->render('doctor/patients_history.html.twig', [
            'patientAppointments' => $patientAppointments,
            'doctor' => $doctorProfile,
            'filterDay' => $filterDay,
            'filterMonth' => $filterMonth,
            'filterYear' => $filterYear,
        ]);
    }
}