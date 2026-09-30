<?php
// src/Service/HouseholdMember/HouseholdMemberService.php

namespace App\Service\HouseholdMember;

use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\User;
use App\Enum\HouseholdMemberRole;
use App\Exception\ConflictException;
use App\Exception\NotFoundException;
use App\Repository\HouseholdMemberRepository;
use App\Service\Household\HouseholdAccessChecker;
use App\Service\Household\HouseholdService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

class HouseholdMemberService
{
    private const ALREADY_MEMBER_MESSAGE = 'Cette personne fait déjà partie du foyer.';

    public function __construct(
        private HouseholdService $householdService,
        private HouseholdAccessChecker $householdAccessChecker,
        private HouseholdMemberRepository $householdMemberRepository,
        private HouseholdMemberHydrator $householdMemberHydrator,
        private EntityManagerInterface $entityManager
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
        $this->householdAccessChecker->assertIsAdmin($user, $household);

        $member = new HouseholdMember();
        $this->householdMemberHydrator->hydrate($member, $data, false);
        $this->assertIsNotAlreadyMember($member->getUser(), $household);

        $household->addHouseholdMember($member);
        $this->entityManager->persist($member);

        try {
            // The SQL insert, and so the unique index check, happens on flush
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            // Two requests at the same time (double click): the database refused the second one
            throw new ConflictException(self::ALREADY_MEMBER_MESSAGE);
        }

        return $member;
    }

    public function update(int $householdId, int $memberId, array $data, User $user): HouseholdMember
    {
        $household = $this->householdService->getForUser($householdId, $user);
        $this->householdAccessChecker->assertIsAdmin($user, $household);
        $member = $this->getMember($memberId, $household);

        $wasAdmin = $member->getRole() === HouseholdMemberRole::Admin;
        $this->householdMemberHydrator->hydrate($member, $data, true);
        $isStillAdmin = $member->getRole() === HouseholdMemberRole::Admin;

        if ($wasAdmin && !$isStillAdmin) {
            $this->assertIsNotLastAdmin($household);
        }

        // Already tracked by Doctrine since it was loaded: flush is enough
        $this->entityManager->flush();

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
            $this->householdAccessChecker->assertIsAdmin($user, $household);
        }

        if ($member->getRole() === HouseholdMemberRole::Admin) {
            $this->assertIsNotLastAdmin($household);
        }

        $this->entityManager->remove($member);
        $this->entityManager->flush();
    }

    private function getMember(int $memberId, Household $household): HouseholdMember
    {
        $member = $this->householdMemberRepository->findOneInHousehold($memberId, $household);

        if ($member === null) {
            throw new NotFoundException('Membre introuvable.');
        }

        return $member;
    }

    // Checked before saving to answer with a clear message; the database unique index is the last safety net
    private function assertIsNotAlreadyMember(User $user, Household $household): void
    {
        if ($this->householdMemberRepository->isMember($user, $household)) {
            throw new ConflictException(self::ALREADY_MEMBER_MESSAGE);
        }
    }

    // Without an admin nobody could manage the household anymore
    private function assertIsNotLastAdmin(Household $household): void
    {
        $adminCount = $this->householdMemberRepository->countAdmins($household);

        if ($adminCount <= 1) {
            throw new ConflictException('Le foyer doit garder au moins un administrateur.');
        }
    }
}
