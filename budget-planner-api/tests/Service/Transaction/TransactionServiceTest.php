<?php

namespace App\Tests\Unit\Service\Transaction;

use App\Entity\Account;
use App\Entity\Category;
use App\Entity\Household;
use App\Entity\Transaction;
use App\Entity\User;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Repository\AccountRepository;
use App\Repository\CategoryRepository;
use App\Repository\HouseholdMemberRepository;
use App\Repository\TransactionRepository;
use App\Service\Transaction\TransactionHydrator;
use App\Service\Transaction\TransactionService;
use PHPUnit\Framework\TestCase;

final class TransactionServiceTest extends TestCase
{
    private User $user;
    private Account $account;

    protected function setUp(): void
    {
        $this->user = new User();

        $this->account = new Account();
        $this->account->setHousehold(new Household());
    }

    public function testGetForUserThrowsWhenTheTransactionIsNotVisible(): void
    {
        $transactionRepository = $this->createStub(TransactionRepository::class);
        $transactionRepository->method('findOneForUser')->willReturn(null);

        $service = $this->createService($transactionRepository, $this->createMemberRepository(true));

        $this->expectException(NotFoundException::class);

        $service->getForUser(42, $this->user);
    }

    public function testAdminCanCreateATransaction(): void
    {
        $transactionRepository = $this->createMock(TransactionRepository::class);
        $transactionRepository->expects($this->once())->method('save');

        $service = $this->createService($transactionRepository, $this->createMemberRepository(true));

        $transaction = $service->create($this->validData(), $this->user);

        // The author is always the logged-in user, never a value sent by the client
        self::assertSame($this->user, $transaction->getUser());
        self::assertSame($this->account, $transaction->getAccount());
    }

    public function testViewerCannotCreateATransaction(): void
    {
        $transactionRepository = $this->createMock(TransactionRepository::class);
        $transactionRepository->expects($this->never())->method('save');

        $service = $this->createService($transactionRepository, $this->createMemberRepository(false));

        $this->expectException(ForbiddenException::class);

        $service->create($this->validData(), $this->user);
    }

    public function testAdminCanUpdateATransaction(): void
    {
        $transaction = $this->createTransaction();
        $transactionRepository = $this->createMock(TransactionRepository::class);
        $transactionRepository->method('findOneForUser')->willReturn($transaction);
        $transactionRepository->expects($this->once())->method('save')->with($transaction);

        $service = $this->createService($transactionRepository, $this->createMemberRepository(true));

        $service->update(1, ['label' => 'Loyer'], $this->user);

        self::assertSame('Loyer', $transaction->getLabel());
    }

    public function testViewerCannotUpdateATransaction(): void
    {
        $transaction = $this->createTransaction();
        $transactionRepository = $this->createMock(TransactionRepository::class);
        $transactionRepository->method('findOneForUser')->willReturn($transaction);
        $transactionRepository->expects($this->never())->method('save');

        $service = $this->createService($transactionRepository, $this->createMemberRepository(false));

        try {
            $service->update(1, ['label' => 'Loyer'], $this->user);
            self::fail('Une ForbiddenException était attendue.');
        } catch (ForbiddenException $exception) {
            // The transaction must not have been modified before the permission check
            self::assertSame('Supermarché', $transaction->getLabel());
        }
    }

    public function testUpdateIsRefusedWhenTheTransactionIsMovedToAReadOnlyAccount(): void
    {
        $transaction = $this->createTransaction();
        $transactionRepository = $this->createMock(TransactionRepository::class);
        $transactionRepository->method('findOneForUser')->willReturn($transaction);
        $transactionRepository->expects($this->never())->method('save');

        // Admin of the current household, but only viewer of the target account's household
        $memberRepository = $this->createStub(HouseholdMemberRepository::class);
        $memberRepository->method('isAdmin')->willReturn(true, false);

        $service = $this->createService($transactionRepository, $memberRepository);

        $this->expectException(ForbiddenException::class);

        $service->update(1, ['accountId' => 2], $this->user);
    }

    public function testAdminCanDeleteATransaction(): void
    {
        $transaction = $this->createTransaction();
        $transactionRepository = $this->createMock(TransactionRepository::class);
        $transactionRepository->method('findOneForUser')->willReturn($transaction);
        $transactionRepository->expects($this->once())->method('remove')->with($transaction);

        $service = $this->createService($transactionRepository, $this->createMemberRepository(true));

        $service->delete(1, $this->user);
    }

    public function testViewerCannotDeleteATransaction(): void
    {
        $transaction = $this->createTransaction();
        $transactionRepository = $this->createMock(TransactionRepository::class);
        $transactionRepository->method('findOneForUser')->willReturn($transaction);
        $transactionRepository->expects($this->never())->method('remove');

        $service = $this->createService($transactionRepository, $this->createMemberRepository(false));

        $this->expectException(ForbiddenException::class);

        $service->delete(1, $this->user);
    }

    /**
     * @return array<string, mixed>
     */
    private function validData(): array
    {
        return [
            'accountId' => 1,
            'categoryId' => 2,
            'type' => 'expense',
            'amount' => '45.90',
            'date' => '2026-09-15',
            'label' => 'Supermarché',
            'isRecurring' => false,
        ];
    }

    private function createTransaction(): Transaction
    {
        $transaction = new Transaction();
        $transaction->setAccount($this->account);
        $transaction->setLabel('Supermarché');
        $transaction->setIsRecurring(false);

        return $transaction;
    }

    private function createMemberRepository(bool $isAdmin): HouseholdMemberRepository
    {
        $memberRepository = $this->createStub(HouseholdMemberRepository::class);
        $memberRepository->method('isAdmin')->willReturn($isAdmin);

        return $memberRepository;
    }

    // Uses the real hydrator: only the database access is replaced by stubs
    private function createService(
        TransactionRepository $transactionRepository,
        HouseholdMemberRepository $memberRepository
    ): TransactionService {
        $accountRepository = $this->createStub(AccountRepository::class);
        $accountRepository->method('findOneForUser')->willReturn($this->account);

        $categoryRepository = $this->createStub(CategoryRepository::class);
        $categoryRepository->method('findOneForUser')->willReturn(new Category());

        $hydrator = new TransactionHydrator($accountRepository, $categoryRepository);

        return new TransactionService($transactionRepository, $memberRepository, $hydrator);
    }
}
