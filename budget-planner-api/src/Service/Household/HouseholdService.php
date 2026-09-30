<?php
// src/Service/Household/HouseholdService.php

namespace App\Service\Household;

use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\User;
use App\Enum\HouseholdMemberRole;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\HouseholdRepository;

class HouseholdService
{
    public function __construct(
        private HouseholdRepository $householdRepository,
        private HouseholdAccessChecker $householdAccessChecker,
        private HouseholdHydrator $householdHydrator
    ) {
    }

    /**
     * @return Household[]
     */
    public function listForUser(User $user): array
    {
        return $this->householdRepository->findAllForUser($user);
    }

    public function getForUser(int $id, User $user): Household
    {
        $household = $this->householdRepository->findOneForUser($id, $user);

        if ($household === null) {
            throw new NotFoundException('Foyer introuvable.');
        }

        return $household;
    }

    // The user who creates the household automatically becomes its admin
    public function create(array $data, User $user): Household
    {
        $household = new Household();
        $this->householdHydrator->hydrate($household, $data, false);

        $member = new HouseholdMember();
        $member->setUser($user);
        $member->setRole(HouseholdMemberRole::Admin);
        $household->addHouseholdMember($member);

        $this->householdRepository->saveWithMember($household, $member);

        return $household;
    }

    public function update(int $id, array $data, User $user): Household
    {
        $household = $this->getForUser($id, $user);
        $this->householdAccessChecker->assertIsAdmin($user, $household);

        $this->householdHydrator->hydrate($household, $data, true);

        $this->householdRepository->save($household);

        return $household;
    }

    public function delete(int $id, User $user): void
    {
        $household = $this->getForUser($id, $user);
        $this->householdAccessChecker->assertIsAdmin($user, $household);
        $this->assertIsEmpty($household);

        $this->householdRepository->remove($household);
    }

    // Deleting the accounts, categories and scenarios must be an explicit choice of the user
    private function assertIsEmpty(Household $household): void
    {
        $hasAccounts = !$household->getAccounts()->isEmpty();
        $hasCategories = !$household->getCategories()->isEmpty();
        $hasScenarios = !$household->getScenarios()->isEmpty();

        if ($hasAccounts || $hasCategories || $hasScenarios) {
            throw new ValidationException('Ce foyer contient encore des comptes, catégories ou scénarios et ne peut pas être supprimé.');
        }
    }
}
