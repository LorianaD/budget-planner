<?php
// src/Service/Household/HouseholdHydrator.php

namespace App\Service\Household;

use App\Entity\Household;
use App\Exception\ValidationException;

class HouseholdHydrator
{
    private const REQUIRED_FIELDS = ['name'];

    // On creation every required field must be sent, on PATCH only the sent fields are applied
    public function hydrate(Household $household, array $data, bool $isPartial): void
    {
        if (!$isPartial) {
            $this->assertRequiredFields($data);
        }

        if (array_key_exists('name', $data)) {
            $household->setName($this->parseName($data['name']));
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

    private function parseName(mixed $value): string
    {
        $name = trim((string) $value);

        if ($name === '') {
            throw new ValidationException('Le nom du foyer est obligatoire.');
        }

        // Matches the length: 100 column
        if (mb_strlen($name) > 100) {
            throw new ValidationException('Le nom du foyer ne doit pas dépasser 100 caractères.');
        }

        return $name;
    }
}
