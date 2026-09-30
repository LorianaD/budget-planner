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

    public function isMember(User $user, Household $household): bool
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT COUNT(m.id)
             FROM App\Entity\HouseholdMember m
             WHERE m.user = :user
             AND m.household = :household'
        );
        $query->setParameter('user', $user);
        $query->setParameter('household', $household);

        $count = (int) $query->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * @return HouseholdMember[]
     */
    public function findAllInHousehold(Household $household): array
    {
        // Fetch-join so the presenter can read the user without extra queries
        $query = $this->getEntityManager()->createQuery(
            'SELECT m, u
             FROM App\Entity\HouseholdMember m
             JOIN m.user u
             WHERE m.household = :household
             ORDER BY m.role ASC, u.name ASC, m.id ASC'
        );
        $query->setParameter('household', $household);

        return $query->getResult();
    }

    // Only returns the member if it belongs to this household
    public function findOneInHousehold(int $id, Household $household): ?HouseholdMember
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT m
             FROM App\Entity\HouseholdMember m
             WHERE m.id = :id
             AND m.household = :household'
        );
        $query->setParameter('id', $id);
        $query->setParameter('household', $household);

        return $query->getOneOrNullResult();
    }

    public function countAdmins(Household $household): int
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT COUNT(m.id)
             FROM App\Entity\HouseholdMember m
             WHERE m.household = :household
             AND m.role = :role'
        );
        $query->setParameter('household', $household);
        $query->setParameter('role', HouseholdMemberRole::Admin->value);

        return (int) $query->getSingleScalarResult();
    }

    public function save(HouseholdMember $member): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($member);
        $entityManager->flush();
    }

    public function remove(HouseholdMember $member): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($member);
        $entityManager->flush();
    }
}
