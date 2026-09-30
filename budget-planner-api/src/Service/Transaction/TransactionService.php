<?php
// src/Service/Transaction/TransactionService.php

namespace App\Service\Transaction;

use App\Entity\Transaction;
use App\Entity\User;
use App\Exception\NotFoundException;
use App\Repository\TransactionRepository;
use App\Service\Household\HouseholdAccessChecker;
use Doctrine\ORM\EntityManagerInterface;

class TransactionService
{
    public function __construct(
        private TransactionRepository $transactionRepository,
        private HouseholdAccessChecker $householdAccessChecker,
        private TransactionHydrator $transactionHydrator,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @return Transaction[]
     */
    public function listForUser(User $user): array
    {
        return $this->transactionRepository->findAllForUser($user);
    }

    public function getForUser(int $id, User $user): Transaction
    {
        $transaction = $this->transactionRepository->findOneForUser($id, $user);

        if ($transaction === null) {
            throw new NotFoundException('Transaction introuvable.');
        }

        return $transaction;
    }

    public function create(array $data, User $user): Transaction
    {
        $transaction = new Transaction();
        $transaction->setUser($user);

        $this->transactionHydrator->hydrate($transaction, $data, $user, false);
        $this->assertCanEdit($transaction, $user);

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        return $transaction;
    }

    public function update(int $id, array $data, User $user): Transaction
    {
        $transaction = $this->getForUser($id, $user);
        $this->assertCanEdit($transaction, $user);

        $this->transactionHydrator->hydrate($transaction, $data, $user, true);
        // Checked again in case the transaction was moved to another account
        $this->assertCanEdit($transaction, $user);

        // Already tracked by Doctrine since it was loaded: flush is enough
        $this->entityManager->flush();

        return $transaction;
    }

    public function delete(int $id, User $user): void
    {
        $transaction = $this->getForUser($id, $user);
        $this->assertCanEdit($transaction, $user);

        $this->entityManager->remove($transaction);
        $this->entityManager->flush();
    }

    // A transaction belongs to the household of its account
    private function assertCanEdit(Transaction $transaction, User $user): void
    {
        $household = $transaction->getAccount()->getHousehold();

        $this->householdAccessChecker->assertIsAdmin($user, $household);
    }
}