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
    public function dashboard(): Response
    {
        $user = $this->getUser();
        $doctorProfile = $this->doctorProfileRepository->findOneBy(['user' => $user]);
        
        if (!$doctorProfile) {
            throw $this->createNotFoundException('Profil docteur non trouvé');
        }

        // Récupérer tous les rendez-vous du docteur
        $appointments = $this->appointmentRepository->findBy(
            ['doctor' => $doctorProfile],
            ['appointmentDate' => 'ASC', 'appointmentTime' => 'ASC']
        );

        return $this->render('doctor/dashboard.html.twig', [
            'doctor' => $doctorProfile,
            'appointments' => $appointments,
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
        
    // Récupérer les paramètres de filtrage
        $filterDay = $request->query->get('filter_day');
        $filterMonth = $request->query->get('filter_month');
        $filterYear = $request->query->get('filter_year');
    $searchCode = $request->query->get('search_code');
        
        // Récupérer tous les rendez-vous du docteur avec les patients
        $qb = $this->appointmentRepository->createQueryBuilder('a')
            ->where('a.doctor = :doctor')
            ->setParameter('doctor', $doctorProfile)
            ->orderBy('a.appointmentDate', 'DESC');
        
        // Filtre par code de confirmation si fourni (prend priorité logique sur la date si seule)
        if ($searchCode) {
            $qb->andWhere('a.confirmationCode = :code')
               ->setParameter('code', strtoupper(trim($searchCode)));
        }

        // Appliquer les filtres de date
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
        
        // Pagination
        $page = max(1, $request->query->getInt('page', 1));
        $itemsPerPage = 5;
        $totalItems = count($patientAppointments);
        $totalPages = max(1, ceil($totalItems / $itemsPerPage));
        $page = min($page, $totalPages);
        
        $offset = ($page - 1) * $itemsPerPage;
        $paginatedPatients = array_slice($patientAppointments, $offset, $itemsPerPage, true);
        
        return $this->render('doctor/patients_history.html.twig', [
            'patientAppointments' => $paginatedPatients,
            'doctor' => $doctorProfile,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'filterDay' => $filterDay,
            'filterMonth' => $filterMonth,
            'filterYear' => $filterYear,
            'searchCode' => $searchCode,
        ]);
    }
}