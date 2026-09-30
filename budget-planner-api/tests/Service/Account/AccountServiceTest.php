<?php

namespace App\Tests\Service\Account;

use App\Entity\Account;
use App\Entity\Household;
use App\Entity\Transaction;
use App\Entity\User;
use App\Exception\ConflictException;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Repository\AccountRepository;
use App\Repository\HouseholdMemberRepository;
use App\Repository\HouseholdRepository;
use App\Service\Account\AccountHydrator;
use App\Service\Account\AccountService;
use App\Service\Household\HouseholdAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class AccountServiceTest extends TestCase
{
    private User $user;
    private Household $household;

    protected function setUp(): void
    {
        $this->user = new User();
        $this->household = new Household();
    }

    public function testGetForUserThrowsWhenTheAccountIsNotVisible(): void
    {
        $accountRepository = $this->createStub(AccountRepository::class);
        $accountRepository->method('findOneForUser')->willReturn(null);

        $service = $this->createService(
            $accountRepository,
            $this->createMemberRepository(true),
            $this->createStub(EntityManagerInterface::class)
        );

        $this->expectException(NotFoundException::class);

        $service->getForUser(42, $this->user);
    }

    public function testAdminCanCreateAnAccount(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(Account::class));
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService(
            $this->createStub(AccountRepository::class),
            $this->createMemberRepository(true),
            $entityManager
        );

        $account = $service->create(['householdId' => 1, 'name' => 'Livret A', 'type' => 'savings'], $this->user);

        self::assertSame('Livret A', $account->getName());
        self::assertSame($this->household, $account->getHousehold());
    }

    public function testViewerCannotCreateAnAccount(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $service = $this->createService(
            $this->createStub(AccountRepository::class),
            $this->createMemberRepository(false),
            $entityManager
        );

        $this->expectException(ForbiddenException::class);

        $service->create(['householdId' => 1, 'name' => 'Livret A', 'type' => 'savings'], $this->user);
    }

    public function testAdminCanUpdateAnAccount(): void
    {
        $account = $this->createAccount();
        $accountRepository = $this->createStub(AccountRepository::class);
        $accountRepository->method('findOneForUser')->willReturn($account);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService($accountRepository, $this->createMemberRepository(true), $entityManager);

        $service->update(1, ['initialBalance' => '-50'], $this->user);

        self::assertSame('-50.00', $account->getInitialBalance());
    }

    public function testViewerCannotUpdateAnAccount(): void
    {
        $account = $this->createAccount();
        $accountRepository = $this->createStub(AccountRepository::class);
        $accountRepository->method('findOneForUser')->willReturn($account);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $service = $this->createService($accountRepository, $this->createMemberRepository(false), $entityManager);

        try {
            $service->update(1, ['name' => 'Nouveau nom'], $this->user);
            self::fail('Une ForbiddenException était attendue.');
        } catch (ForbiddenException) {
            // The account must not have been modified before the permission check
            self::assertSame('Compte courant', $account->getName());
        }
    }

    public function testUpdateIsRefusedWhenTheAccountIsMovedToAReadOnlyHousehold(): void
    {
        $account = $this->createAccount();
        $accountRepository = $this->createStub(AccountRepository::class);
        $accountRepository->method('findOneForUser')->willReturn($account);

        // Admin of the current household, but only viewer of the target household
        $memberRepository = $this->createStub(HouseholdMemberRepository::class);
        $memberRepository->method('isAdmin')->willReturn(true, false);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $service = $this->createService($accountRepository, $memberRepository, $entityManager);

        $this->expectException(ForbiddenException::class);

        $service->update(1, ['householdId' => 2], $this->user);
    }

    public function testAdminCanDeleteAnEmptyAccount(): void
    {
        $account = $this->createAccount();
        $accountRepository = $this->createStub(AccountRepository::class);
        $accountRepository->method('findOneForUser')->willReturn($account);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($account);
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService($accountRepository, $this->createMemberRepository(true), $entityManager);

        $service->delete(1, $this->user);
    }

    public function testAccountWithTransactionsCannotBeDeleted(): void
    {
        $account = $this->createAccount();
        $account->addTransaction(new Transaction());

        $accountRepository = $this->createStub(AccountRepository::class);
        $accountRepository->method('findOneForUser')->willReturn($account);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('remove');

        $service = $this->createService($accountRepository, $this->createMemberRepository(true), $entityManager);

        $this->expectException(ConflictException::class);

        $service->delete(1, $this->user);
    }

    public function testViewerCannotDeleteAnAccount(): void
    {
        $account = $this->createAccount();
        $accountRepository = $this->createStub(AccountRepository::class);
        $accountRepository->method('findOneForUser')->willReturn($account);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('remove');

        $service = $this->createService($accountRepository, $this->createMemberRepository(false), $entityManager);

        $this->expectException(ForbiddenException::class);

        $service->delete(1, $this->user);
    }

    private function createAccount(): Account
    {
        $account = new Account();
        $account->setHousehold($this->household);
        $account->setName('Compte courant');
        $account->setType('checking');

        return $account;
    }

    private function createMemberRepository(bool $isAdmin): HouseholdMemberRepository
    {
        $memberRepository = $this->createStub(HouseholdMemberRepository::class);
        $memberRepository->method('isAdmin')->willReturn($isAdmin);

        return $memberRepository;
    }

    // Uses the real hydrator: only the database access is replaced by a stub
    private function createService(
        AccountRepository $accountRepository,
        HouseholdMemberRepository $memberRepository,
        EntityManagerInterface $entityManager
    ): AccountService {
        $householdRepository = $this->createStub(HouseholdRepository::class);
        $householdRepository->method('findOneForUser')->willReturn($this->household);

        $hydrator = new AccountHydrator($householdRepository);

        return new AccountService(
            $accountRepository,
            new HouseholdAccessChecker($memberRepository),
            $hydrator,
            $entityManager
        );
    }
}
