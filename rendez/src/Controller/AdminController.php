<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\DoctorProfile;
use App\Entity\User;
use App\Entity\Specialty;
use App\Repository\AppointmentRepository;
use App\Repository\DoctorProfileRepository;
use App\Repository\SpecialtyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private AppointmentRepository $appointmentRepository,
        private DoctorProfileRepository $doctorProfileRepository,
        private SpecialtyRepository $specialtyRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function dashboard(Request $request): Response
    {
        // Récupérer toutes les spécialités actives
        $activeSpecialties = $this->specialtyRepository->findActiveSpecialties();
        $selectedSpecialty = $request->query->get('specialty');
        $searchCode = $request->query->get('search_code');
        $filterDay = $request->query->get('filter_day');
        $filterMonth = $request->query->get('filter_month');
        $filterYear = $request->query->get('filter_year');

        // Si un code de confirmation est recherché
        if ($searchCode) {
            $normalized = strtoupper(trim($searchCode));
            $searchedAppointment = $this->appointmentRepository->findOneBy(['confirmationCode' => $normalized]);
            return $this->render('admin/dashboard.html.twig', [
                'activeSpecialties' => $activeSpecialties,
                'appointmentsBySpecialty' => [],
                'selectedSpecialty' => $selectedSpecialty,
                'searchCode' => $normalized,
                'searchedAppointment' => $searchedAppointment,
                'filterDay' => $filterDay,
                'filterMonth' => $filterMonth,
                'filterYear' => $filterYear,
                'currentPage' => 1,
                'totalPages' => 1,
            ]);
        }

        // Récupérer les rendez-vous filtrés
        $filteredAppointments = $this->appointmentRepository->findFiltered(
            $selectedSpecialty,
            $filterDay ? (int)$filterDay : null,
            $filterMonth ? (int)$filterMonth : null,
            $filterYear ? (int)$filterYear : null
        );

        // Organiser les rendez-vous par spécialité
        $appointmentsBySpecialty = [];
        foreach ($filteredAppointments as $appointment) {
            $specialty = $appointment->getDoctor()->getSpecialty() ? $appointment->getDoctor()->getSpecialty()->getName() : 'Non définie';
            if (!isset($appointmentsBySpecialty[$specialty])) {
                $appointmentsBySpecialty[$specialty] = [];
            }
            $appointmentsBySpecialty[$specialty][] = $appointment;
        }
        
        // Pagination
        $page = max(1, $request->query->getInt('page', 1));
        $itemsPerPage = 10;
        $totalItems = count($filteredAppointments);
        $totalPages = max(1, ceil($totalItems / $itemsPerPage));
        $page = min($page, $totalPages);
        
        // Paginer les appointments par spécialité
        $offset = ($page - 1) * $itemsPerPage;
        $paginatedAppointments = array_slice($filteredAppointments, $offset, $itemsPerPage);
        
        $paginatedBySpecialty = [];
        foreach ($paginatedAppointments as $appointment) {
            $specialty = $appointment->getDoctor()->getSpecialty() ? $appointment->getDoctor()->getSpecialty()->getName() : 'Non définie';
            if (!isset($paginatedBySpecialty[$specialty])) {
                $paginatedBySpecialty[$specialty] = [];
            }
            $paginatedBySpecialty[$specialty][] = $appointment;
        }

        return $this->render('admin/dashboard.html.twig', [
            'activeSpecialties' => $activeSpecialties,
            'appointmentsBySpecialty' => $paginatedBySpecialty,
            'selectedSpecialty' => $selectedSpecialty,
            'searchCode' => null,
            'searchedAppointment' => null,
            'filterDay' => $filterDay,
            'filterMonth' => $filterMonth,
            'filterYear' => $filterYear,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
        ]);
    }

    #[Route('/admin/appointment/{id}/status', name: 'admin_update_status', methods: ['POST'])]
    public function updateAppointmentStatus(Appointment $appointment, Request $request): Response
    {
        $newStatus = $request->request->get('status');
        
        if (in_array($newStatus, [Appointment::STATUS_CONFIRMED, Appointment::STATUS_DONE, Appointment::STATUS_CANCELED])) {
            $appointment->setStatus($newStatus);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Statut du rendez-vous mis à jour avec succès');
        } else {
            $this->addFlash('error', 'Statut invalide');
        }

        return $this->redirectToRoute('admin_dashboard');
    }
    // ...existing code...

    #[Route('/admin/add-doctor', name: 'admin_add_doctor')]
    public function addDoctor(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $firstName = $request->request->get('firstName');
            $lastName = $request->request->get('lastName');
            $specialty = $request->request->get('specialty');
            $email = $request->request->get('email');
            $phone = $request->request->get('phone');
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirmPassword');

            // Vérifier que les mots de passe correspondent
            if ($password !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('admin_add_doctor');
            }

            // Vérifier si l'email existe déjà
            $existingUser = $this->entityManager->getRepository(User::class)->findOneByEmail($email);
            if ($existingUser) {
                $this->addFlash('error', 'Un utilisateur avec cette adresse email existe déjà.');
                return $this->redirectToRoute('admin_add_doctor');
            }

            // Créer l'utilisateur docteur
            $user = new User();
            $user->setEmail($email);
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setPhone($phone);
            $user->setGender('male'); // Valeur par défaut
            $user->setRoles([User::ROLE_DOCTOR]);
            $user->setIsVerified(true);
            
            // Hasher le mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);

            // Créer le profil docteur
            $doctorProfile = new DoctorProfile();
            $doctorProfile->setUser($user);
            
            // Récupérer l'entité Specialty
            $specialtyEntity = $this->specialtyRepository->findOneBy(['name' => $specialty]);
            if (!$specialtyEntity) {
                $this->addFlash('error', 'Spécialité invalide.');
                return $this->redirectToRoute('admin_add_doctor');
            }
            $doctorProfile->setSpecialty($specialtyEntity);
            
            // Générer un code ID unique
            $doctorId = 'DR' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Vérifier l'unicité du code ID
            while ($this->doctorProfileRepository->findOneBy(['doctorId' => $doctorId])) {
                $doctorId = 'DR' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            }
            
            $doctorProfile->setDoctorId($doctorId);

            $this->entityManager->persist($doctorProfile);
            $this->entityManager->flush();

            $this->addFlash('success', "Docteur créé avec succès ! Code ID: $doctorId");
            return $this->redirectToRoute('admin_dashboard');
        }

        // Liste des spécialités actives
        $activeSpecialties = $this->specialtyRepository->findActiveSpecialties();
        $specialties = array_map(fn($spec) => $spec->getName(), $activeSpecialties);

        return $this->render('admin/add_doctor.html.twig', [
            'specialties' => $specialties,
        ]);
    }

    #[Route('/admin/specialties', name: 'admin_specialties')]
    public function manageSpecialties(Request $request): Response
    {
        $specialties = $this->specialtyRepository->findBy([], ['name' => 'ASC']);

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            
            if ($action === 'add') {
                $name = trim($request->request->get('name'));
                $description = trim($request->request->get('description'));
                
                if ($name) {
                    // Vérifier si la spécialité existe déjà
                    $existing = $this->specialtyRepository->findByName($name);
                    if ($existing) {
                        $this->addFlash('error', 'Cette spécialité existe déjà.');
                    } else {
                        $specialty = new Specialty();
                        $specialty->setName($name);
                        $specialty->setDescription($description);
                        
                        $this->entityManager->persist($specialty);
                        $this->entityManager->flush();
                        
                        $this->addFlash('success', 'Spécialité ajoutée avec succès.');
                    }
                }
            } elseif ($action === 'toggle') {
                $id = $request->request->get('id');
                $specialty = $this->specialtyRepository->find($id);
                if ($specialty) {
                    $specialty->setIsActive(!$specialty->isActive());
                    $this->entityManager->flush();
                    
                    $status = $specialty->isActive() ? 'activée' : 'désactivée';
                    $this->addFlash('success', "Spécialité {$status} avec succès.");
                }
            }
            
            return $this->redirectToRoute('admin_specialties');
        }

        return $this->render('admin/specialties.html.twig', [
            'specialties' => $specialties,
        ]);
    }
}