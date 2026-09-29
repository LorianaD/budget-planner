<?php
// src/Service/Transaction/TransactionService.php

namespace App\Service\Transaction;

use App\Entity\Transaction;
use App\Entity\User;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Repository\HouseholdMemberRepository;
use App\Repository\TransactionRepository;

class TransactionService
{
    public function __construct(
        private TransactionRepository $transactionRepository,
        private HouseholdMemberRepository $householdMemberRepository,
        private TransactionHydrator $transactionHydrator
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

        $this->transactionRepository->save($transaction);

        return $transaction;
    }

    public function update(int $id, array $data, User $user): Transaction
    {
        $transaction = $this->getForUser($id, $user);
        $this->assertCanEdit($transaction, $user);

        $this->transactionHydrator->hydrate($transaction, $data, $user, true);
        // Checked again in case the transaction was moved to another account
        $this->assertCanEdit($transaction, $user);

        $this->transactionRepository->save($transaction);

        return $transaction;
    }

    public function delete(int $id, User $user): void
    {
        $transaction = $this->getForUser($id, $user);
        $this->assertCanEdit($transaction, $user);

        $this->transactionRepository->remove($transaction);
    }

    // Viewers can read the household data but never modify it
    private function assertCanEdit(Transaction $transaction, User $user): void
    {
        $household = $transaction->getAccount()->getHousehold();

        if (!$this->householdMemberRepository->isAdmin($user, $household)) {
            throw new ForbiddenException('Vous avez un accès en lecture seule à ce foyer.');
        }
    }
}