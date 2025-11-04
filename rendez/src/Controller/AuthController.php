<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Entity\DoctorProfile;
use App\Repository\SpecialtyRepository;
use App\Repository\DoctorProfileRepository;
use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(SpecialtyRepository $specialtyRepository): Response
    {
        // Si l'utilisateur est connecté, l'amener au dashboard approprié
        if ($this->getUser()) {
            // Rediriger les admins vers le dashboard admin
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_dashboard');
            }
            // Rediriger les docteurs vers leur dashboard
            if ($this->isGranted('ROLE_DOCTOR')) {
                return $this->redirectToRoute('doctor_dashboard');
            }
            return $this->redirectToRoute('app_dashboard');
        }

        // Sinon, afficher la page d'accueil publique avec les spécialités
        $activeSpecialties = $specialtyRepository->findActiveSpecialties();
        return $this->render('home.html.twig', [
            'specialties' => $activeSpecialties,
        ]);
    }

    #[Route('/public/doctors/{specialty}', name: 'public_doctors_by_specialty')]
    public function publicDoctorsBySpecialty(string $specialty, SpecialtyRepository $specialtyRepository, DoctorProfileRepository $doctorProfileRepository): Response
    {
        // Trouver la spécialité
        $specialtyEntity = $specialtyRepository->findOneBy(['name' => $specialty]);
        
        if (!$specialtyEntity) {
            return $this->render('public/doctors_list.html.twig', [
                'doctors' => [],
                'specialty' => $specialty,
            ]);
        }

        // Trouver les docteurs de cette spécialité
        $doctors = $doctorProfileRepository->findBy(['specialty' => $specialtyEntity]);

        return $this->render('public/doctors_list.html.twig', [
            'doctors' => $doctors,
            'specialty' => $specialty,
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        // Redirection si déjà connecté
        if ($this->getUser()) {
            // Rediriger les admins vers le dashboard admin
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_dashboard');
            }
            return $this->redirectToRoute('app_dashboard');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Tous les nouveaux utilisateurs sont des patients par défaut
            $user->setRoles([User::ROLE_PATIENT]);
            
            // Encode le mot de passe
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');
            
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Redirection si déjà connecté
        if ($this->getUser()) {
            // Rediriger les admins vers le dashboard admin
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_dashboard');
            }
            return $this->redirectToRoute('app_dashboard');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        
        return $this->render('auth/dashboard.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/doctor', name: 'doctor_redirect')]
    public function doctorRedirect(): Response
    {
        // Redirection automatique vers le dashboard docteur
        return $this->redirectToRoute('doctor_dashboard');
    }

    #[Route('/admin', name: 'admin_redirect')]
    public function adminRedirect(): Response
    {
        // Redirection automatique vers le dashboard admin
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/patient/appointments', name: 'patient_appointments')]
    public function patientAppointments(AppointmentRepository $appointmentRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_PATIENT');

        $user = $this->getUser();
        
        // Récupérer tous les rendez-vous du patient connecté
        $appointments = $appointmentRepository->findBy(['patient' => $user], ['appointmentDate' => 'DESC']);
        
        return $this->render('patient/appointments.html.twig', [
            'appointments' => $appointments,
            'user' => $user,
        ]);
    }
}
