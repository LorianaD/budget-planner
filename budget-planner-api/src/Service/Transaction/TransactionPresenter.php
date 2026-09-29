<?php
// src/Service/Transaction/TransactionPresenter.php

namespace App\Service\Transaction;

use App\Entity\Transaction;

// Single place defining which transaction fields are exposed to the front
class TransactionPresenter
{
    public function toArray(Transaction $transaction): array
    {
        $frequency = null;
        if ($transaction->getFrequency() !== null) {
            $frequency = $transaction->getFrequency()->value;
        }

        $commitmentEndDate = null;
        if ($transaction->getCommitmentEndDate() !== null) {
            $commitmentEndDate = $transaction->getCommitmentEndDate()->format('Y-m-d');
        }

        return [
            'id' => $transaction->getId(),
            'type' => $transaction->getType()->value,
            'amount' => $transaction->getAmount(),
            'date' => $transaction->getDate()->format('Y-m-d'),
            'label' => $transaction->getLabel(),
            'isRecurring' => $transaction->isRecurring(),
            'frequency' => $frequency,
            'commitmentEndDate' => $commitmentEndDate,
            'account' => [
                'id' => $transaction->getAccount()->getId(),
                'name' => $transaction->getAccount()->getName(),
            ],
            'category' => [
                'id' => $transaction->getCategory()->getId(),
                'name' => $transaction->getCategory()->getName(),
            ],
            'userId' => $transaction->getUser()->getId(),
        ];
    }

    /**
     * @param Transaction[] $transactions
     */
    public function toList(array $transactions): array
    {
        $list = [];

        foreach ($transactions as $transaction) {
            $list[] = $this->toArray($transaction);
        }

        return $list;
    }
}