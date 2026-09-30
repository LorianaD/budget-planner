<?php
// src/Service/Account/AccountService.php

namespace App\Service\Account;

use App\Entity\Account;
use App\Entity\User;
use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\AccountRepository;
use App\Service\Household\HouseholdAccessChecker;
use Doctrine\ORM\EntityManagerInterface;

class AccountService
{
    public function __construct(
        private AccountRepository $accountRepository,
        private HouseholdAccessChecker $householdAccessChecker,
        private AccountHydrator $accountHydrator,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @return Account[]
     */
    public function listForUser(User $user): array
    {
        return $this->accountRepository->findAllForUser($user);
    }

    public function getForUser(int $id, User $user): Account
    {
        $account = $this->accountRepository->findOneForUser($id, $user);

        if ($account === null) {
            throw new NotFoundException('Compte introuvable.');
        }

        return $account;
    }

    public function create(array $data, User $user): Account
    {
        $account = new Account();

        $this->accountHydrator->hydrate($account, $data, $user, false);
        $this->assertCanEdit($account, $user);

        $this->entityManager->persist($account);
        $this->entityManager->flush();

        return $account;
    }

    public function update(int $id, array $data, User $user): Account
    {
        $account = $this->getForUser($id, $user);
        $this->assertCanEdit($account, $user);

        $this->accountHydrator->hydrate($account, $data, $user, true);
        // Checked again in case the account was moved to another household
        $this->assertCanEdit($account, $user);

        // Already tracked by Doctrine since it was loaded: flush is enough
        $this->entityManager->flush();

        return $account;
    }

    public function delete(int $id, User $user): void
    {
        $account = $this->getForUser($id, $user);
        $this->assertCanEdit($account, $user);
        $this->assertHasNoTransactions($account);

        $this->entityManager->remove($account);
        $this->entityManager->flush();
    }

    private function assertCanEdit(Account $account, User $user): void
    {
        $this->householdAccessChecker->assertIsAdmin($user, $account->getHousehold());
    }

    // The foreign key would make the DELETE fail with a 500 otherwise
    private function assertHasNoTransactions(Account $account): void
    {
        if (!$account->getTransactions()->isEmpty()) {
            throw new ConflictException('Ce compte contient des transactions et ne peut pas être supprimé.');
        }
    }
}
