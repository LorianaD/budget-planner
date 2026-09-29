<?php
// src/Service/Account/AccountPresenter.php

namespace App\Service\Account;

use App\Entity\Account;

// Single place defining which account fields are exposed to the front
class AccountPresenter
{
    public function toArray(Account $account): array
    {
        return [
            'id' => $account->getId(),
            'name' => $account->getName(),
            'type' => $account->getType(),
            'initialBalance' => $account->getInitialBalance(),
            'household' => [
                'id' => $account->getHousehold()->getId(),
                'name' => $account->getHousehold()->getName(),
            ],
        ];
    }

    /**
     * @param Account[] $accounts
     */
    public function toList(array $accounts): array
    {
        $list = [];

        foreach ($accounts as $account) {
            $list[] = $this->toArray($account);
        }

        return $list;
    }
}
