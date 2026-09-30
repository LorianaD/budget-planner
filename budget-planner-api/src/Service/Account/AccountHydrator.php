<?php
// src/Service/Account/AccountHydrator.php

namespace App\Service\Account;

use App\Entity\Account;
use App\Entity\Household;
use App\Entity\User;
use App\Exception\ValidationException;
use App\Repository\HouseholdRepository;
use App\Util\DecimalFormatter;

class AccountHydrator
{
    private const REQUIRED_FIELDS = ['householdId', 'name', 'type'];

    // Will be replaced by the AccountType enum (see the comment in the Account entity)
    private const ALLOWED_TYPES = ['checking', 'savings', 'joint'];

    public function __construct(
        private HouseholdRepository $householdRepository,
    ) {
    }

    // On creation every required field must be sent, on PATCH only the sent fields are applied
    public function hydrate(Account $account, array $data, User $user, bool $isPartial): void
    {
        if (!$isPartial) {
            $this->assertRequiredFields($data);
        }

        if (array_key_exists('householdId', $data)) {
            $account->setHousehold($this->findHousehold($data['householdId'], $user));
        }

        if (array_key_exists('name', $data)) {
            $account->setName($this->parseName($data['name']));
        }

        if (array_key_exists('type', $data)) {
            $account->setType($this->parseType($data['type']));
        }

        if (array_key_exists('initialBalance', $data)) {
            $account->setInitialBalance($this->parseInitialBalance($data['initialBalance']));
        }
    }

    private function assertRequiredFields(array $data): void
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $data)) {
                throw new ValidationException(sprintf('Le champ "%s" est obligatoire.', $field));
            }
        }
    }

    private function findHousehold(mixed $householdId, User $user): Household
    {
        if (!is_int($householdId)) {
            throw new ValidationException('Le foyer est invalide.');
        }

        $household = $this->householdRepository->findOneForUser($householdId, $user);
        if ($household === null) {
            throw new ValidationException('Foyer introuvable.');
        }

        return $household;
    }

    private function parseName(mixed $value): string
    {
        $name = trim((string) $value);

        if ($name === '') {
            throw new ValidationException('Le nom du compte est obligatoire.');
        }

        // Matches the length: 100 column
        if (mb_strlen($name) > 100) {
            throw new ValidationException('Le nom du compte ne doit pas dépasser 100 caractères.');
        }

        return $name;
    }

    private function parseType(mixed $value): string
    {
        $type = (string) $value;

        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new ValidationException('Type de compte invalide (checking, savings ou joint).');
        }

        return $type;
    }

    // Kept as a string to match DECIMAL(10,2) without float rounding.
    // Unlike a transaction amount, it can be negative (overdrawn account) or zero.
    private function parseInitialBalance(mixed $value): string
    {
        $initialBalance = (string) $value;

        if (!preg_match('/^-?\d{1,8}(\.\d{1,2})?$/', $initialBalance)) {
            throw new ValidationException('Le solde initial doit être un nombre avec 2 décimales maximum.');
        }

        return DecimalFormatter::withTwoDecimals($initialBalance);
    }
}
