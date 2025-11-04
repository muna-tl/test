<?php

namespace App\Repository;

use App\Entity\MedicalRecord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MedicalRecord>
 */
class MedicalRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MedicalRecord::class);
    }

    /**
     * Find or create medical record for a patient
     */
    public function findOrCreateForPatient(int $patientId): MedicalRecord
    {
        $record = $this->createQueryBuilder('m')
            ->where('m.patient = :patientId')
            ->setParameter('patientId', $patientId)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$record) {
            $record = new MedicalRecord();
            // Patient will be set by the controller
        }

        return $record;
    }
}
