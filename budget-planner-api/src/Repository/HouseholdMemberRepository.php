<?php

namespace App\Repository;

use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\User;
use App\Enum\HouseholdMemberRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HouseholdMember>
 */
class HouseholdMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HouseholdMember::class);
    }

//    /**
//     * @return HouseholdMember[] Returns an array of HouseholdMember objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('h')
//            ->andWhere('h.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('h.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?HouseholdMember
//    {
//        return $this->createQueryBuilder('h')
//            ->andWhere('h.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function isAdmin(User $user, Household $household): bool
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT COUNT(m.id)
             FROM App\Entity\HouseholdMember m
             WHERE m.user = :user
             AND m.household = :household
             AND m.role = :role'
        );
        $query->setParameter('user', $user);
        $query->setParameter('household', $household);
        $query->setParameter('role', HouseholdMemberRole::Admin->value);

        $count = (int) $query->getSingleScalarResult();

        return $count > 0;
    }
}
