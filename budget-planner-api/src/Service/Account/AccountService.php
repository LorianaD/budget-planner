<?php
// src/Service/Account/AccountService.php

namespace App\Service\Account;

use App\Entity\Account;
use App\Entity\User;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\AccountRepository;
use App\Repository\HouseholdMemberRepository;

class AccountService
{
    public function __construct(
        private AccountRepository $accountRepository,
        private HouseholdMemberRepository $householdMemberRepository,
        private AccountHydrator $accountHydrator
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

        $this->accountRepository->save($account);

        return $account;
    }

    public function update(int $id, array $data, User $user): Account
    {
        $account = $this->getForUser($id, $user);
        $this->assertCanEdit($account, $user);

        $this->accountHydrator->hydrate($account, $data, $user, true);
        // Checked again in case the account was moved to another household
        $this->assertCanEdit($account, $user);

        $this->accountRepository->save($account);

        return $account;
    }

    public function delete(int $id, User $user): void
    {
        $account = $this->getForUser($id, $user);
        $this->assertCanEdit($account, $user);
        $this->assertHasNoTransactions($account);

        $this->accountRepository->remove($account);
    }

    // Viewers can read the household data but never modify it
    private function assertCanEdit(Account $account, User $user): void
    {
        $household = $account->getHousehold();

        if (!$this->householdMemberRepository->isAdmin($user, $household)) {
            throw new ForbiddenException('Vous avez un accès en lecture seule à ce foyer.');
        }
    }

    // The foreign key would make the DELETE fail with a 500 otherwise
    private function assertHasNoTransactions(Account $account): void
    {
        if (!$account->getTransactions()->isEmpty()) {
            throw new ValidationException('Ce compte contient des transactions et ne peut pas être supprimé.');
        }
    }
}
