<?php

namespace App\Repository;

use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Household>
 */
class HouseholdRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Household::class);
    }

//    /**
//     * @return Household[] Returns an array of Household objects
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

//    public function findOneBySomeField($value): ?Household
//    {
//        return $this->createQueryBuilder('h')
//            ->andWhere('h.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    // Only returns the household if the user is one of its members
    public function findOneForUser(int $id, User $user): ?Household
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT h
             FROM App\Entity\Household h
             JOIN h.householdMembers m
             WHERE h.id = :id
             AND m.user = :user'
        );
        $query->setParameter('id', $id);
        $query->setParameter('user', $user);

        return $query->getOneOrNullResult();
    }

    /**
     * @return Household[]
     */
    public function findAllForUser(User $user): array
    {
        // No fetch-join on m: it is filtered on the user, so it would only load one member
        $query = $this->getEntityManager()->createQuery(
            'SELECT h
             FROM App\Entity\Household h
             JOIN h.householdMembers m
             WHERE m.user = :user
             ORDER BY h.name ASC, h.id ASC'
        );
        $query->setParameter('user', $user);

        return $query->getResult();
    }

    public function save(Household $household): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($household);
        $entityManager->flush();
    }

    // Saves a new household and its first member in the same flush
    public function saveWithMember(Household $household, HouseholdMember $member): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($household);
        $entityManager->persist($member);
        $entityManager->flush();
    }

    // Members are removed first, otherwise their foreign key blocks the delete
    public function remove(Household $household): void
    {
        $entityManager = $this->getEntityManager();

        foreach ($household->getHouseholdMembers() as $member) {
            $entityManager->remove($member);
        }

        $entityManager->remove($household);
        $entityManager->flush();
    }
}
