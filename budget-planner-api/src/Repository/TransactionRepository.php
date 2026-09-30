<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

//    /**
//     * @return Transaction[] Returns an array of Transaction objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Transaction
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    // src/Repository/TransactionRepository.php — methods to add

    /**
     * @return Transaction[]
     */
    public function findAllForUser(User $user): array
    {
        // Fetch-join account and category to avoid one query per transaction
        $query = $this->getEntityManager()->createQuery(
            'SELECT t, a, c
             FROM App\Entity\Transaction t
             JOIN t.account a
             JOIN t.category c
             JOIN a.household h
             JOIN h.householdMembers m
             WHERE m.user = :user
             ORDER BY t.date DESC, t.id DESC'
        );
        $query->setParameter('user', $user);

        return $query->getResult();
    }

    public function findOneForUser(int $id, User $user): ?Transaction
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT t, a, c
             FROM App\Entity\Transaction t
             JOIN t.account a
             JOIN t.category c
             JOIN a.household h
             JOIN h.householdMembers m
             WHERE t.id = :id
             AND m.user = :user'
        );
        $query->setParameter('id', $id);
        $query->setParameter('user', $user);

        return $query->getOneOrNullResult();
    }
}
