<?php

namespace App\Repository;

use App\Entity\Appointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    /**
     * Filtre les rendez-vous par spécialité, jour, mois, année
     */
    public function findFiltered(?string $specialty = null, ?int $day = null, ?int $month = null, ?int $year = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.doctor', 'd')
            ->leftJoin('d.specialtyEntity', 's')
            ->addSelect('d', 's')
            ->orderBy('a.appointmentDate', 'ASC')
            ->addOrderBy('a.appointmentTime', 'ASC');

        if ($specialty) {
            $qb->andWhere('s.name = :specialty')->setParameter('specialty', $specialty);
        }

        // Filtrage par année/mois/jour
        if ($year) {
            $qb->andWhere('a.appointmentDate >= :yearStart AND a.appointmentDate <= :yearEnd')
                ->setParameter('yearStart', new \DateTime("$year-01-01"))
                ->setParameter('yearEnd', new \DateTime("$year-12-31"));
        }
        if ($month) {
            $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
            $start = isset($year) ? "$year-$monthStr-01" : date('Y')."-$monthStr-01";
            $end = isset($year) ? "$year-$monthStr-31" : date('Y')."-$monthStr-31";
            $qb->andWhere('a.appointmentDate >= :monthStart AND a.appointmentDate <= :monthEnd')
                ->setParameter('monthStart', new \DateTime($start))
                ->setParameter('monthEnd', new \DateTime($end));
        }
        if ($day) {
            if ($year && $month) {
                $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
                $dayStr = str_pad($day, 2, '0', STR_PAD_LEFT);
                $dateStr = "$year-$monthStr-$dayStr";
                $qb->andWhere('a.appointmentDate = :dayDate')
                    ->setParameter('dayDate', new \DateTime($dateStr));
            }
        }

        return $qb->getQuery()->getResult();
    }
}
