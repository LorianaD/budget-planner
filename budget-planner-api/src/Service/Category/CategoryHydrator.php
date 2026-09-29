<?php
// src/Service/Category/CategoryHydrator.php

namespace App\Service\Category;

use App\Entity\Category;
use App\Entity\Household;
use App\Entity\User;
use App\Enum\CategoryEnvelope;
use App\Exception\ValidationException;
use App\Repository\HouseholdRepository;

class CategoryHydrator
{
    private const REQUIRED_FIELDS = ['householdId', 'name'];

    public function __construct(
        private HouseholdRepository $householdRepository,
    ) {
    }

    // On creation every required field must be sent, on PATCH only the sent fields are applied
    public function hydrate(Category $category, array $data, User $user, bool $isPartial): void
    {
        if (!$isPartial) {
            $this->assertRequiredFields($data);
        }

        if (array_key_exists('householdId', $data)) {
            $category->setHousehold($this->findHousehold($data['householdId'], $user));
        }

        if (array_key_exists('name', $data)) {
            $category->setName($this->parseName($data['name']));
        }

        if (array_key_exists('envelope', $data)) {
            $category->setEnvelope($this->parseEnvelope($data['envelope']));
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
            throw new ValidationException('Le nom de la catégorie est obligatoire.');
        }

        // Matches the length: 100 column
        if (mb_strlen($name) > 100) {
            throw new ValidationException('Le nom de la catégorie ne doit pas dépasser 100 caractères.');
        }

        return $name;
    }

    // The envelope is optional: null means the category is not linked to the 50/30/20 rule
    private function parseEnvelope(mixed $value): ?CategoryEnvelope
    {
        if ($value === null) {
            return null;
        }

        $envelope = CategoryEnvelope::tryFrom((string) $value);
        if ($envelope === null) {
            throw new ValidationException('Enveloppe invalide (essential, leisure ou savings).');
        }

        return $envelope;
    }
}
