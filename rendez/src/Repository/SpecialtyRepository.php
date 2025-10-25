<?php

namespace App\Repository;

use App\Entity\Specialty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Specialty>
 */
class SpecialtyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Specialty::class);
    }

    /**
     * Find all active specialties ordered by name
     */
    public function findActiveSpecialties(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find specialty by name
     */
    public function findByName(string $name): ?Specialty
    {
        return $this->createQueryBuilder('s')
            ->where('s.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }
}