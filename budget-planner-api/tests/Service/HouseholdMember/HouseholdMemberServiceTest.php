<?php

namespace App\Tests\Service\HouseholdMember;

use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\User;
use App\Enum\HouseholdMemberRole;
use App\Exception\ConflictException;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Repository\HouseholdMemberRepository;
use App\Repository\UserRepository;
use App\Service\Household\HouseholdAccessChecker;
use App\Service\Household\HouseholdService;
use App\Service\HouseholdMember\HouseholdMemberHydrator;
use App\Service\HouseholdMember\HouseholdMemberService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class HouseholdMemberServiceTest extends TestCase
{
    private User $user;
    private User $invitedUser;
    private Household $household;

    protected function setUp(): void
    {
        $this->user = new User();
        $this->invitedUser = new User();
        $this->household = new Household();
    }

    public function testListThrowsWhenTheHouseholdIsNotVisible(): void
    {
        $householdService = $this->createStub(HouseholdService::class);
        $householdService->method('getForUser')->willThrowException(new NotFoundException('Foyer introuvable.'));

        $memberRepository = $this->createStub(HouseholdMemberRepository::class);
        $service = new HouseholdMemberService(
            $householdService,
            new HouseholdAccessChecker($memberRepository),
            $memberRepository,
            $this->createHydrator(),
            $this->createStub(EntityManagerInterface::class)
        );

        $this->expectException(NotFoundException::class);

        $service->listForHousehold(42, $this->user);
    }

    public function testAdminCanAddAMember(): void
    {
        $memberRepository = $this->createStub(HouseholdMemberRepository::class);
        $memberRepository->method('isAdmin')->willReturn(true);
        $memberRepository->method('isMember')->willReturn(false);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(HouseholdMember::class));
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService($memberRepository, $entityManager);

        $member = $service->add(1, ['email' => 'proche@example.com'], $this->user);

        self::assertSame($this->invitedUser, $member->getUser());
        self::assertSame($this->household, $member->getHousehold());
        self::assertSame(HouseholdMemberRole::Viewer, $member->getRole());
    }

    public function testDoubleClickOnAddGivesAClearMessage(): void
    {
        // Both requests passed the "already member?" check, the database refuses the second insert
        $memberRepository = $this->createStub(HouseholdMemberRepository::class);
        $memberRepository->method('isAdmin')->willReturn(true);
        $memberRepository->method('isMember')->willReturn(false);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('flush')->willThrowException(
            $this->createStub(UniqueConstraintViolationException::class)
        );

        $service = $this->createService($memberRepository, $entityManager);

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIs('Cette personne fait déjà partie du foyer.');

        $service->add(1, ['email' => 'proche@example.com'], $this->user);
    }

    public function testViewerCannotAddAMember(): void
    {
        $memberRepository = $this->createStub(HouseholdMemberRepository::class);
        $memberRepository->method('isAdmin')->willReturn(false);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $service = $this->createService($memberRepository, $entityManager);

        $this->expectException(ForbiddenException::class);

        $service->add(1, ['email' => 'proche@example.com'], $this->user);
    }

    public function testSamePersonCannotBeAddedTwice(): void
    {
        $memberRepository = $this->createStub(HouseholdMemberRepository::class);
        $memberRepository->method('isAdmin')->willReturn(true);
        $memberRepository->method('isMember')->willReturn(true);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $service = $this->createService($memberRepository, $entityManager);

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIs('Cette personne fait déjà partie du foyer.');

        $service->add(1, ['email' => 'proche@example.com'], $this->user);
    }

    public function testAdminCanPromoteAViewer(): void
    {
        $member = $this->createMember($this->invitedUser, HouseholdMemberRole::Viewer);
        $memberRepository = $this->createStub(HouseholdMemberRepository::class);
        $memberRepository->method('isAdmin')->willReturn(true);
        $memberRepository->method('findOneInHousehold')->willReturn($member);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService($memberRepository, $entityManager);

        $service->update(1, 5, ['role' => 'admin'], $this->user);

        self::assertSame(HouseholdMemberRole::Admin, $member->getRole());
    }

    public function testUpdateThrowsWhenTheMemberIsNotInTheHousehold(): void
    {
        $memberRepository = $this->createStub(HouseholdMemberRepository::class);
        $memberRepository->method('isAdmin')->willReturn(true);
        $memberRepository->method('findOneInHousehold')->willReturn(null);

        $service = $this->createService($memberRepository, $this->createStub(EntityManagerInterface::class));

        $this->expectException(NotFoundException::class);

        $service->update(1, 999, ['role' => 'admin'], $this->user);
    }

    public function testViewerCannotChangeARole(): void
    {
        $memberRepository = $this->createStub(HouseholdMemberRepository::class);
        $memberRepository->method('isAdmin')->willReturn(false);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $service = $this->createService($memberRepository, $entityManager);

        $this->expectException(ForbiddenException::class);

        $service->update(1, 5, ['role' => 'admin'], $this->user);
    }

    public function testLastAdminCannotBeDemoted(): void
    {
        $member = $this->createMember($this->user, HouseholdMemberRole::Admin);
        $memberRepository = $this->createStub(HouseholdMemberRepository::class);
        $memberRepository->method('isAdmin')->willReturn(true);
        $memberRepository->method('findOneInHousehold')->willReturn($member);
        $memberRepository->method('countAdmins')->willReturn(1);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $service = $this->createService($memberRepository, $entityManager);

        $this->expectException(ConflictException::class);
        $this->expectExceptionMessageIs('Le foyer doit garder au moins un administrateur.');

        $service->update(1, 5, ['role' => 'viewer'], $this->user);
    }

    public function testAdminCanBeDemotedWhenAnotherAdminRemains(): void
    {
        $member = $this->createMember($this->invitedUser, HouseholdMemberRole::Admin);
        $memberRepository = $this->createStub(HouseholdMemberRepository::class);
        $memberRepository->method('isAdmin')->willReturn(true);
        $memberRepository->method('findOneInHousehold')->willReturn($member);
        $memberRepository->method('countAdmins')->willReturn(2);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService($memberRepository, $entityManager);

        $service->update(1, 5, ['role' => 'viewer'], $this->user);

        self::assertSame(HouseholdMemberRole::Viewer, $member->getRole());
    }

    public function testAdminCanRemoveAViewer(): void
    {
        $member = $this->createMember($this->invitedUser, HouseholdMemberRole::Viewer);
        $memberRepository = $this->createStub(HouseholdMemberRepository::class);
        $memberRepository->method('isAdmin')->willReturn(true);
        $memberRepository->method('findOneInHousehold')->willReturn($member);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($member);
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService($memberRepository, $entityManager);

        $service->remove(1, 5, $this->user);
    }

    public function testViewerCannotRemoveSomeoneElse(): void
    {
        $member = $this->createMember($this->invitedUser, HouseholdMemberRole::Viewer);
        $memberRepository = $this->createStub(HouseholdMemberRepository::class);
        $memberRepository->method('isAdmin')->willReturn(false);
        $memberRepository->method('findOneInHousehold')->willReturn($member);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('remove');

        $service = $this->createService($memberRepository, $entityManager);

        $this->expectException(ForbiddenException::class);

        $service->remove(1, 5, $this->user);
    }

    public function testViewerCanLeaveTheHousehold(): void
    {
        $member = $this->createMember($this->user, HouseholdMemberRole::Viewer);
        $memberRepository = $this->createStub(HouseholdMemberRepository::class);
        $memberRepository->method('isAdmin')->willReturn(false);
        $memberRepository->method('findOneInHousehold')->willReturn($member);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($member);
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService($memberRepository, $entityManager);

        $service->remove(1, 5, $this->user);
    }

    public function testLastAdminCannotLeaveTheHousehold(): void
    {
        $member = $this->createMember($this->user, HouseholdMemberRole::Admin);
        $memberRepository = $this->createStub(HouseholdMemberRepository::class);
        $memberRepository->method('isAdmin')->willReturn(true);
        $memberRepository->method('findOneInHousehold')->willReturn($member);
        $memberRepository->method('countAdmins')->willReturn(1);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('remove');

        $service = $this->createService($memberRepository, $entityManager);

        $this->expectException(ConflictException::class);

        $service->remove(1, 5, $this->user);
    }

    private function createMember(User $user, HouseholdMemberRole $role): HouseholdMember
    {
        $member = new HouseholdMember();
        $member->setUser($user);
        $member->setRole($role);
        $this->household->addHouseholdMember($member);

        return $member;
    }

    private function createHydrator(): HouseholdMemberHydrator
    {
        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('findOneByEmail')->willReturn($this->invitedUser);

        return new HouseholdMemberHydrator($userRepository);
    }

    // Uses the real hydrator: only the database access and the household lookup are replaced
    private function createService(
        HouseholdMemberRepository $memberRepository,
        EntityManagerInterface $entityManager
    ): HouseholdMemberService {
        $householdService = $this->createStub(HouseholdService::class);
        $householdService->method('getForUser')->willReturn($this->household);

        return new HouseholdMemberService(
            $householdService,
            new HouseholdAccessChecker($memberRepository),
            $memberRepository,
            $this->createHydrator(),
            $entityManager
        );
    }
}
