<?php

namespace App\Tests\Controller;

use App\Entity\Account;
use App\Entity\Category;
use App\Entity\Household;
use App\Entity\User;
use App\Enum\HouseholdMemberRole;
use Symfony\Component\HttpFoundation\Response;

final class TransactionControllerTest extends ApiTestCase
{
    private User $admin;
    private User $viewer;
    private Household $household;
    private Account $account;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createUser('admin@example.com', 'Admin');
        $this->viewer = $this->createUser('viewer@example.com', 'Viewer');
        $this->household = $this->createHousehold($this->admin, 'Famille');
        $this->addMember($this->household, $this->viewer, HouseholdMemberRole::Viewer);
        $this->account = $this->createAccount($this->household);
        $this->category = $this->createCategory($this->household);
    }

    /**
     * @return array<string, mixed>
     */
    private function validData(): array
    {
        return [
            'accountId' => $this->account->getId(),
            'categoryId' => $this->category->getId(),
            'type' => 'expense',
            'amount' => '45.90',
            'date' => '2026-09-15',
            'label' => 'Supermarché',
            'isRecurring' => false,
        ];
    }

    public function testListRequiresAToken(): void
    {
        $this->requestJson('GET', '/api/transactions');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListOnlyReturnsTheTransactionsOfTheUserHouseholds(): void
    {
        $this->createTransaction($this->admin, $this->account, $this->category, 'Supermarché');

        $stranger = $this->createUser('stranger@example.com');
        $otherHousehold = $this->createHousehold($stranger, 'Autre foyer');
        $otherAccount = $this->createAccount($otherHousehold);
        $otherCategory = $this->createCategory($otherHousehold);
        $this->createTransaction($stranger, $otherAccount, $otherCategory, 'Transaction secrète');

        $this->loginAs($this->viewer);
        $this->requestJson('GET', '/api/transactions');

        self::assertResponseIsSuccessful();
        $data = $this->responseData();
        self::assertCount(1, $data);
        self::assertSame('Supermarché', $data[0]['label']);
    }

    public function testAdminCanCreateATransaction(): void
    {
        $this->loginAs($this->admin);

        $this->requestJson('POST', '/api/transactions', $this->validData());

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = $this->responseData();
        self::assertIsInt($data['id']);
        self::assertSame('expense', $data['type']);
        self::assertSame('45.90', $data['amount']);
        self::assertSame('2026-09-15', $data['date']);
        self::assertSame($this->account->getId(), $data['account']['id']);
        self::assertSame($this->category->getId(), $data['category']['id']);
        // The author is the logged-in user
        self::assertSame($this->admin->getId(), $data['userId']);
    }

    public function testRecurringTransactionCanBeCreated(): void
    {
        $data = $this->validData();
        $data['label'] = 'Loyer';
        $data['isRecurring'] = true;
        $data['frequency'] = 'monthly';
        $data['commitmentEndDate'] = '2027-08-31';
        $this->loginAs($this->admin);

        $this->requestJson('POST', '/api/transactions', $data);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $result = $this->responseData();
        self::assertTrue($result['isRecurring']);
        self::assertSame('monthly', $result['frequency']);
        self::assertSame('2027-08-31', $result['commitmentEndDate']);
    }

    public function testTransactionCanBeCreatedWithoutIsRecurring(): void
    {
        $data = $this->validData();
        unset($data['isRecurring']);
        $this->loginAs($this->admin);

        $this->requestJson('POST', '/api/transactions', $data);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $result = $this->responseData();
        self::assertFalse($result['isRecurring']);
    }

    public function testInvalidAmountIsRejected(): void
    {
        $data = $this->validData();
        $data['amount'] = '-10';
        $this->loginAs($this->admin);

        $this->requestJson('POST', '/api/transactions', $data);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testViewerCannotCreateATransaction(): void
    {
        $this->loginAs($this->viewer);

        $this->requestJson('POST', '/api/transactions', $this->validData());

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testTransactionOfAnotherHouseholdIsNotFound(): void
    {
        $transaction = $this->createTransaction($this->admin, $this->account, $this->category);
        $stranger = $this->createUser('stranger@example.com');
        $this->loginAs($stranger);

        $this->requestJson('GET', '/api/transactions/' . $transaction->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testAdminCanUpdateATransaction(): void
    {
        $transaction = $this->createTransaction($this->admin, $this->account, $this->category);
        $this->loginAs($this->admin);

        $this->requestJson('PATCH', '/api/transactions/' . $transaction->getId(), ['amount' => '60.50']);

        self::assertResponseIsSuccessful();
        $data = $this->responseData();
        self::assertSame('60.50', $data['amount']);
        self::assertSame('Supermarché', $data['label']);
    }

    public function testViewerCannotUpdateATransaction(): void
    {
        $transaction = $this->createTransaction($this->admin, $this->account, $this->category);
        $this->loginAs($this->viewer);

        $this->requestJson('PATCH', '/api/transactions/' . $transaction->getId(), ['amount' => '60']);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAdminCanDeleteATransaction(): void
    {
        $transaction = $this->createTransaction($this->admin, $this->account, $this->category);
        $transactionId = $transaction->getId();
        $this->loginAs($this->admin);

        $this->requestJson('DELETE', '/api/transactions/' . $transactionId);
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->requestJson('GET', '/api/transactions/' . $transactionId);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testViewerCannotDeleteATransaction(): void
    {
        $transaction = $this->createTransaction($this->admin, $this->account, $this->category);
        $this->loginAs($this->viewer);

        $this->requestJson('DELETE', '/api/transactions/' . $transaction->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
