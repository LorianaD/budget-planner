<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

//    /**
//     * @return Category[] Returns an array of Category objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Category
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    /**
     * @return Category[]
     */
    public function findAllForUser(User $user): array
    {
        // Fetch-join so the presenter can read the household without extra queries
        $query = $this->getEntityManager()->createQuery(
            'SELECT c, h
             FROM App\Entity\Category c
             JOIN c.household h
             JOIN h.householdMembers m
             WHERE m.user = :user
             ORDER BY c.name ASC, c.id ASC'
        );
        $query->setParameter('user', $user);

        return $query->getResult();
    }

    public function findOneForUser(int $id, User $user): ?Category
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT c
             FROM App\Entity\Category c
             JOIN c.household h
             JOIN h.householdMembers m
             WHERE c.id = :id
             AND m.user = :user'
        );
        $query->setParameter('id', $id);
        $query->setParameter('user', $user);

        return $query->getOneOrNullResult();
    }
}
