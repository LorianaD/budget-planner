<?php
// src/Service/Household/HouseholdAccessChecker.php

namespace App\Service\Household;

use App\Entity\Household;
use App\Entity\User;
use App\Exception\ForbiddenException;
use App\Repository\HouseholdMemberRepository;

// Single place deciding who can modify the data of a household
class HouseholdAccessChecker
{
    public function __construct(
        private HouseholdMemberRepository $householdMemberRepository,
    ) {
    }

    // Viewers can read the household data but never modify it
    public function assertIsAdmin(User $user, Household $household): void
    {
        if (!$this->householdMemberRepository->isAdmin($user, $household)) {
            throw new ForbiddenException('Vous avez un accès en lecture seule à ce foyer.');
        }
    }
}
