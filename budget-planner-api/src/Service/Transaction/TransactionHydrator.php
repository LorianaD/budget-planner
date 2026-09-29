<?php
// src/Service/Transaction/TransactionHydrator.php

namespace App\Service\Transaction;

use App\Entity\Account;
use App\Entity\Category;
use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\TransactionFrequency;
use App\Enum\TransactionType;
use App\Exception\ValidationException;
use App\Repository\AccountRepository;
use App\Repository\CategoryRepository;

class TransactionHydrator
{
    private const REQUIRED_FIELDS = ['accountId', 'categoryId', 'type', 'amount', 'date', 'label'];

    public function __construct(
        private AccountRepository $accountRepository,
        private CategoryRepository $categoryRepository
    ) {
    }

    // On creation every required field must be sent, on PATCH only the sent fields are applied
    public function hydrate(Transaction $transaction, array $data, User $user, bool $isPartial): void
    {
        if (!$isPartial) {
            $this->assertRequiredFields($data);
        }

        if (array_key_exists('accountId', $data)) {
            $transaction->setAccount($this->findAccount($data['accountId'], $user));
        }

        if (array_key_exists('categoryId', $data)) {
            $transaction->setCategory($this->findCategory($data['categoryId'], $user));
        }

        if (array_key_exists('type', $data)) {
            $transaction->setType($this->parseType($data['type']));
        }

        if (array_key_exists('amount', $data)) {
            $transaction->setAmount($this->parseAmount($data['amount']));
        }

        if (array_key_exists('date', $data)) {
            $transaction->setDate($this->parseDate($data['date'], 'date'));
        }

        if (array_key_exists('label', $data)) {
            $transaction->setLabel($this->parseLabel($data['label']));
        }

        if (array_key_exists('isRecurring', $data)) {
            $transaction->setIsRecurring($this->parseBoolean($data['isRecurring']));
        }

        if (array_key_exists('frequency', $data)) {
            $transaction->setFrequency($this->parseFrequency($data['frequency']));
        }

        if (array_key_exists('commitmentEndDate', $data)) {
            $transaction->setCommitmentEndDate($this->parseOptionalDate($data['commitmentEndDate']));
        }

        $this->assertRecurrenceIsConsistent($transaction);
    }

    private function assertRequiredFields(array $data): void
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $data)) {
                throw new ValidationException(sprintf('Le champ "%s" est obligatoire.', $field));
            }
        }
    }

    private function findAccount(mixed $accountId, User $user): Account
    {
        if (!is_int($accountId)) {
            throw new ValidationException('Le compte est invalide.');
        }

        $account = $this->accountRepository->findOneForUser($accountId, $user);
        if ($account === null) {
            throw new ValidationException('Compte introuvable.');
        }

        return $account;
    }

    private function findCategory(mixed $categoryId, User $user): Category
    {
        if (!is_int($categoryId)) {
            throw new ValidationException('La catégorie est invalide.');
        }

        $category = $this->categoryRepository->findOneForUser($categoryId, $user);
        if ($category === null) {
            throw new ValidationException('Catégorie introuvable.');
        }

        return $category;
    }

    private function parseType(mixed $value): TransactionType
    {
        $type = TransactionType::tryFrom((string) $value);
        if ($type === null) {
            throw new ValidationException('Type de transaction invalide.');
        }

        return $type;
    }

    // Kept as a string to match DECIMAL(10,2) without float rounding
    private function parseAmount(mixed $value): string
    {
        $amount = (string) $value;

        if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $amount)) {
            throw new ValidationException('Le montant doit être un nombre positif avec 2 décimales maximum.');
        }

        if ((float) $amount <= 0) {
            throw new ValidationException('Le montant doit être supérieur à 0.');
        }

        return $amount;
    }

    private function parseDate(mixed $value, string $field): \DateTimeImmutable
    {
        $text = (string) $value;
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $text);

        // Rejects impossible dates such as 2026-02-30
        if ($date === false || $date->format('Y-m-d') !== $text) {
            throw new ValidationException(sprintf('Le champ "%s" doit être une date au format AAAA-MM-JJ.', $field));
        }

        return $date;
    }

    private function parseOptionalDate(mixed $value): ?\DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        return $this->parseDate($value, 'commitmentEndDate');
    }

    private function parseLabel(mixed $value): string
    {
        $label = trim((string) $value);

        if ($label === '') {
            throw new ValidationException('Le libellé est obligatoire.');
        }

        if (mb_strlen($label) > 255) {
            throw new ValidationException('Le libellé ne doit pas dépasser 255 caractères.');
        }

        return $label;
    }

    private function parseBoolean(mixed $value): bool
    {
        if (!is_bool($value)) {
            throw new ValidationException('Le champ "isRecurring" doit être true ou false.');
        }

        return $value;
    }

    private function parseFrequency(mixed $value): ?TransactionFrequency
    {
        if ($value === null) {
            return null;
        }

        $frequency = TransactionFrequency::tryFrom((string) $value);
        if ($frequency === null) {
            throw new ValidationException('Fréquence invalide.');
        }

        return $frequency;
    }

    private function assertRecurrenceIsConsistent(Transaction $transaction): void
    {
        if ($transaction->isRecurring() && $transaction->getFrequency() === null) {
            throw new ValidationException('Une transaction récurrente doit avoir une fréquence.');
        }

        if (!$transaction->isRecurring()) {
            $transaction->setFrequency(null);
            $transaction->setCommitmentEndDate(null);
        }
    }
}