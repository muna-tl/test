<?php

namespace App\Controller;

use App\Entity\MedicalDocument;
use App\Entity\MedicalRecord;
use App\Entity\User;
use App\Repository\MedicalRecordRepository;
use App\Repository\MedicalDocumentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;

#[IsGranted('ROLE_USER')]
class MedicalRecordController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MedicalRecordRepository $medicalRecordRepository,
        private MedicalDocumentRepository $medicalDocumentRepository,
        private UserRepository $userRepository,
        private SluggerInterface $slugger
    ) {
    }

    #[Route('/medical-record/{patientId}', name: 'medical_record_view')]
    public function view(int $patientId): Response
    {
        $patient = $this->userRepository->find($patientId);
        
        if (!$patient) {
            throw $this->createNotFoundException('Patient non trouvé');
        }

        // Check access rights
        /** @var User $user */
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && 
            !$this->isGranted('ROLE_DOCTOR') && 
            $user->getId() !== $patientId) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        // Find or create medical record
        $medicalRecord = $this->medicalRecordRepository->createQueryBuilder('m')
            ->where('m.patient = :patient')
            ->setParameter('patient', $patient)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$medicalRecord) {
            $medicalRecord = new MedicalRecord();
            $medicalRecord->setPatient($patient);
            $this->entityManager->persist($medicalRecord);
            $this->entityManager->flush();
        }

        return $this->render('medical_record/view.html.twig', [
            'patient' => $patient,
            'medicalRecord' => $medicalRecord,
            'analyses' => $medicalRecord->getDocumentsByType('analyse'),
            'radios' => $medicalRecord->getDocumentsByType('radio'),
            'observations' => $medicalRecord->getDocumentsByType('observation'),
        ]);
    }

    #[Route('/medical-record/{id}/update-info', name: 'medical_record_update_info', methods: ['POST'])]
    #[IsGranted('ROLE_DOCTOR')]
    public function updateInfo(int $id, Request $request): Response
    {
        $medicalRecord = $this->medicalRecordRepository->find($id);
        
        if (!$medicalRecord) {
            throw $this->createNotFoundException('Dossier médical non trouvé');
        }

        $medicalRecord->setBloodType($request->request->get('bloodType'));
        $medicalRecord->setAllergies($request->request->get('allergies'));
        $medicalRecord->setChronicDiseases($request->request->get('chronicDiseases'));
        $medicalRecord->setCurrentMedications($request->request->get('currentMedications'));
        $medicalRecord->setEmergencyContact($request->request->get('emergencyContact'));
        $medicalRecord->setNotes($request->request->get('notes'));
        $medicalRecord->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        $this->addFlash('success', 'Informations mises à jour avec succès');

        return $this->redirectToRoute('medical_record_view', [
            'patientId' => $medicalRecord->getPatient()->getId()
        ]);
    }

    #[Route('/medical-record/{id}/add-document', name: 'medical_record_add_document', methods: ['POST'])]
    #[IsGranted('ROLE_DOCTOR')]
    public function addDocument(int $id, Request $request): Response
    {
        $medicalRecord = $this->medicalRecordRepository->find($id);
        
        if (!$medicalRecord) {
            throw $this->createNotFoundException('Dossier médical non trouvé');
        }

        /** @var UploadedFile $file */
        $file = $request->files->get('file');
        
        if (!$file) {
            $this->addFlash('error', 'Aucun fichier sélectionné');
            return $this->redirectToRoute('medical_record_view', [
                'patientId' => $medicalRecord->getPatient()->getId()
            ]);
        }

        // Validate file size (10MB max)
        if ($file->getSize() > 10 * 1024 * 1024) {
            $this->addFlash('error', 'Le fichier est trop volumineux (max 10MB)');
            return $this->redirectToRoute('medical_record_view', [
                'patientId' => $medicalRecord->getPatient()->getId()
            ]);
        }

        // Validate file type
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedExtensions)) {
            $this->addFlash('error', 'Type de fichier non autorisé. Formats acceptés: ' . implode(', ', $allowedExtensions));
            return $this->redirectToRoute('medical_record_view', [
                'patientId' => $medicalRecord->getPatient()->getId()
            ]);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

        try {
            $file->move(
                $this->getParameter('kernel.project_dir') . '/public/uploads/medical_documents',
                $newFilename
            );

            $document = new MedicalDocument();
            $document->setMedicalRecord($medicalRecord);
            $document->setType($request->request->get('type', 'observation'));
            $document->setTitle($request->request->get('title', $originalFilename));
            $document->setDescription($request->request->get('description'));
            $document->setFilePath($newFilename);
            $document->setUploadedBy($this->getUser());

            $this->entityManager->persist($document);
            $this->entityManager->flush();

            $this->addFlash('success', 'Document ajouté avec succès');
        } catch (FileException $e) {
            $this->addFlash('error', 'Erreur lors de l\'upload du fichier');
        }

        return $this->redirectToRoute('medical_record_view', [
            'patientId' => $medicalRecord->getPatient()->getId()
        ]);
    }

    #[Route('/medical-record/document/{id}/download', name: 'medical_record_download_document')]
    public function downloadDocument(int $id): Response
    {
        $document = $this->medicalDocumentRepository->find($id);
        
        if (!$document) {
            throw $this->createNotFoundException('Document non trouvé');
        }

        // Check access rights
        /** @var User $user */
        $user = $this->getUser();
        $patientId = $document->getMedicalRecord()->getPatient()->getId();
        
        if (!$this->isGranted('ROLE_ADMIN') && 
            !$this->isGranted('ROLE_DOCTOR') && 
            $user->getId() !== $patientId) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/medical_documents/' . $document->getFilePath();
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Fichier non trouvé');
        }

        return new BinaryFileResponse($filePath);
    }

    #[Route('/medical-record/document/{id}/delete', name: 'medical_record_delete_document', methods: ['POST'])]
    #[IsGranted('ROLE_DOCTOR')]
    public function deleteDocument(int $id): Response
    {
        $document = $this->medicalDocumentRepository->find($id);
        
        if (!$document) {
            throw $this->createNotFoundException('Document non trouvé');
        }

        $patientId = $document->getMedicalRecord()->getPatient()->getId();
        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/medical_documents/' . $document->getFilePath();
        
        // Delete file from filesystem
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete from database
        $this->entityManager->remove($document);
        $this->entityManager->flush();

        $this->addFlash('success', 'Document supprimé avec succès');

        return $this->redirectToRoute('medical_record_view', [
            'patientId' => $patientId
        ]);
    }

    #[Route('/medical-record/{patientId}/pdf', name: 'medical_record_pdf')]
    public function generatePdf(int $patientId): Response
    {
        $patient = $this->userRepository->find($patientId);
        
        if (!$patient) {
            throw $this->createNotFoundException('Patient non trouvé');
        }

        // Check access rights
        /** @var User $user */
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && 
            !$this->isGranted('ROLE_DOCTOR') && 
            $user->getId() !== $patientId) {
            throw $this->createAccessDeniedException('Accès refusé');
        }

        // Find or create medical record
        $medicalRecord = $this->medicalRecordRepository->createQueryBuilder('m')
            ->where('m.patient = :patient')
            ->setParameter('patient', $patient)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$medicalRecord) {
            $medicalRecord = new MedicalRecord();
            $medicalRecord->setPatient($patient);
        }

        // Get doctor name if available (from latest appointment)
        $doctorName = 'Non assigné';
        $latestAppointment = $this->entityManager->getRepository(\App\Entity\Appointment::class)
            ->createQueryBuilder('a')
            ->where('a.patient = :patient')
            ->setParameter('patient', $patient)
            ->orderBy('a.startAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        
        if ($latestAppointment && $latestAppointment->getDoctor()) {
            $doctorName = 'Dr. ' . $latestAppointment->getDoctor()->getUser()->getFullName();
        }

        $html = $this->renderView('medical_record/pdf.html.twig', [
            'patient' => $patient,
            'medicalRecord' => $medicalRecord,
            'doctorName' => $doctorName,
            'analyses' => $medicalRecord->getDocumentsByType('analyse'),
            'radios' => $medicalRecord->getDocumentsByType('radio'),
            'observations' => $medicalRecord->getDocumentsByType('observation'),
        ]);

        // Configure Dompdf
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $filename = 'dossier_medical_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $patient->getFullName()) . '_' . date('Y-m-d') . '.pdf';

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]);
    }
}
