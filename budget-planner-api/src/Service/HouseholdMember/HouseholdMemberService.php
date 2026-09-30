<?php
// src/Service/HouseholdMember/HouseholdMemberService.php

namespace App\Service\HouseholdMember;

use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\User;
use App\Enum\HouseholdMemberRole;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\HouseholdMemberRepository;
use App\Service\Household\HouseholdService;

class HouseholdMemberService
{
    public function __construct(
        private HouseholdService $householdService,
        private HouseholdMemberRepository $householdMemberRepository,
        private HouseholdMemberHydrator $householdMemberHydrator
    ) {
    }

    /**
     * @return HouseholdMember[]
     */
    public function listForHousehold(int $householdId, User $user): array
    {
        $household = $this->householdService->getForUser($householdId, $user);

        return $this->householdMemberRepository->findAllInHousehold($household);
    }

    public function add(int $householdId, array $data, User $user): HouseholdMember
    {
        $household = $this->householdService->getForUser($householdId, $user);
        $this->assertIsAdmin($household, $user);

        $member = new HouseholdMember();
        $this->householdMemberHydrator->hydrate($member, $data, false);
        $this->assertIsNotAlreadyMember($member->getUser(), $household);

        $household->addHouseholdMember($member);
        $this->householdMemberRepository->save($member);

        return $member;
    }

    public function update(int $householdId, int $memberId, array $data, User $user): HouseholdMember
    {
        $household = $this->householdService->getForUser($householdId, $user);
        $this->assertIsAdmin($household, $user);
        $member = $this->getMember($memberId, $household);

        $wasAdmin = $member->getRole() === HouseholdMemberRole::Admin;
        $this->householdMemberHydrator->hydrate($member, $data, true);
        $isStillAdmin = $member->getRole() === HouseholdMemberRole::Admin;

        if ($wasAdmin && !$isStillAdmin) {
            $this->assertIsNotLastAdmin($household);
        }

        $this->householdMemberRepository->save($member);

        return $member;
    }

    // An admin can remove anyone, any member can leave the household by removing themself
    public function remove(int $householdId, int $memberId, User $user): void
    {
        $household = $this->householdService->getForUser($householdId, $user);
        $member = $this->getMember($memberId, $household);

        // Same Doctrine identity map: the logged-in user is the same object as the member's user
        $isLeaving = $member->getUser() === $user;
        if (!$isLeaving) {
            $this->assertIsAdmin($household, $user);
        }

        if ($member->getRole() === HouseholdMemberRole::Admin) {
            $this->assertIsNotLastAdmin($household);
        }

        $this->householdMemberRepository->remove($member);
    }

    private function getMember(int $memberId, Household $household): HouseholdMember
    {
        $member = $this->householdMemberRepository->findOneInHousehold($memberId, $household);

        if ($member === null) {
            throw new NotFoundException('Membre introuvable.');
        }

        return $member;
    }

    // Viewers can read the household data but never modify it
    private function assertIsAdmin(Household $household, User $user): void
    {
        if (!$this->householdMemberRepository->isAdmin($user, $household)) {
            throw new ForbiddenException('Vous avez un accès en lecture seule à ce foyer.');
        }
    }

    // The unique pair (user, household) is not enforced in the database
    private function assertIsNotAlreadyMember(User $user, Household $household): void
    {
        if ($this->householdMemberRepository->isMember($user, $household)) {
            throw new ValidationException('Cette personne fait déjà partie du foyer.');
        }
    }

    // Without an admin nobody could manage the household anymore
    private function assertIsNotLastAdmin(Household $household): void
    {
        $adminCount = $this->householdMemberRepository->countAdmins($household);

        if ($adminCount <= 1) {
            throw new ValidationException('Le foyer doit garder au moins un administrateur.');
        }
    }
}
