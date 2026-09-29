<?php

namespace App\Repository;

use App\Entity\Account;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Account>
 */
class AccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

//    /**
//     * @return Account[] Returns an array of Account objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Account
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    /**
     * @return Account[]
     */
    public function findAllForUser(User $user): array
    {
        // Fetch-join so the household can be read without extra queries
        $query = $this->getEntityManager()->createQuery(
            'SELECT a, h
             FROM App\Entity\Account a
             JOIN a.household h
             JOIN h.householdMembers m
             WHERE m.user = :user
             ORDER BY a.name ASC, a.id ASC'
        );
        $query->setParameter('user', $user);

        return $query->getResult();
    }

    public function findOneForUser(int $id, User $user): ?Account
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT a
             FROM App\Entity\Account a
             JOIN a.household h
             JOIN h.householdMembers m
             WHERE a.id = :id
             AND m.user = :user'
        );
        $query->setParameter('id', $id);
        $query->setParameter('user', $user);

        return $query->getOneOrNullResult();
    }

    public function save(Account $account): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($account);
        $entityManager->flush();
    }

    public function remove(Account $account): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($account);
        $entityManager->flush();
    }
}
