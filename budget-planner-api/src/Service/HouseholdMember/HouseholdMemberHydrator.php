<?php
// src/Service/HouseholdMember/HouseholdMemberHydrator.php

namespace App\Service\HouseholdMember;

use App\Entity\HouseholdMember;
use App\Entity\User;
use App\Enum\HouseholdMemberRole;
use App\Exception\ValidationException;
use App\Repository\UserRepository;

class HouseholdMemberHydrator
{
    private const REQUIRED_FIELDS = ['email'];

    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    // On creation the email designates the user to add, on PATCH only the role can change
    public function hydrate(HouseholdMember $member, array $data, bool $isPartial): void
    {
        if ($isPartial) {
            $this->assertEmailIsNotSent($data);
        } else {
            $this->assertRequiredFields($data);
            $member->setUser($this->findUser($data['email']));
            // A shared access is read-only unless the admin explicitly asks for more
            $member->setRole(HouseholdMemberRole::Viewer);
        }

        if (array_key_exists('role', $data)) {
            $member->setRole($this->parseRole($data['role']));
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

    private function assertEmailIsNotSent(array $data): void
    {
        if (array_key_exists('email', $data)) {
            throw new ValidationException('Le membre ne peut pas être remplacé : retirez-le puis ajoutez la nouvelle personne.');
        }
    }

    // The person must already have an account: there is no invitation by email yet
    private function findUser(mixed $value): User
    {
        $email = trim((string) $value);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Email invalide.');
        }

        $user = $this->userRepository->findOneByEmail($email);
        if ($user === null) {
            throw new ValidationException('Aucun compte n\'est associé à cet email.');
        }

        return $user;
    }

    private function parseRole(mixed $value): HouseholdMemberRole
    {
        $role = HouseholdMemberRole::tryFrom((string) $value);
        if ($role === null) {
            throw new ValidationException('Rôle invalide (admin ou viewer).');
        }

        return $role;
    }
}
