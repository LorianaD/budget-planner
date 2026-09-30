<?php

namespace App\Tests\Controller;

use App\Entity\Household;
use App\Entity\User;
use App\Enum\HouseholdMemberRole;
use Symfony\Component\HttpFoundation\Response;

final class AccountControllerTest extends ApiTestCase
{
    private User $admin;
    private User $viewer;
    private Household $household;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createUser('admin@example.com', 'Admin');
        $this->viewer = $this->createUser('viewer@example.com', 'Viewer');
        $this->household = $this->createHousehold($this->admin, 'Famille');
        $this->addMember($this->household, $this->viewer, HouseholdMemberRole::Viewer);
    }

    public function testListRequiresAToken(): void
    {
        $this->requestJson('GET', '/api/accounts');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListOnlyReturnsTheAccountsOfTheUserHouseholds(): void
    {
        $this->createAccount($this->household, 'Livret A');
        $this->createAccount($this->household, 'Compte courant');

        $stranger = $this->createUser('stranger@example.com');
        $otherHousehold = $this->createHousehold($stranger, 'Autre foyer');
        $this->createAccount($otherHousehold, 'Compte secret');

        $this->loginAs($this->viewer);
        $this->requestJson('GET', '/api/accounts');

        self::assertResponseIsSuccessful();
        $data = $this->responseData();
        self::assertCount(2, $data);
        // Sorted by name
        self::assertSame('Compte courant', $data[0]['name']);
        self::assertSame('Livret A', $data[1]['name']);
    }

    public function testAdminCanCreateAnAccount(): void
    {
        $this->loginAs($this->admin);

        $this->requestJson('POST', '/api/accounts', [
            'householdId' => $this->household->getId(),
            'name' => 'Compte joint',
            'type' => 'joint',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = $this->responseData();
        self::assertIsInt($data['id']);
        self::assertSame('Compte joint', $data['name']);
        self::assertSame('joint', $data['type']);
        self::assertSame('0.00', $data['initialBalance']);
        self::assertSame($this->household->getId(), $data['household']['id']);
    }

    public function testInvalidTypeIsRejected(): void
    {
        $this->loginAs($this->admin);

        $this->requestJson('POST', '/api/accounts', [
            'householdId' => $this->household->getId(),
            'name' => 'Portefeuille',
            'type' => 'crypto',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseMessage('Type de compte invalide (checking, savings ou joint).');
    }

    public function testViewerCannotCreateAnAccount(): void
    {
        $this->loginAs($this->viewer);

        $this->requestJson('POST', '/api/accounts', [
            'householdId' => $this->household->getId(),
            'name' => 'Livret A',
            'type' => 'savings',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAccountOfAnotherHouseholdIsNotFound(): void
    {
        $account = $this->createAccount($this->household);
        $stranger = $this->createUser('stranger@example.com');
        $this->loginAs($stranger);

        $this->requestJson('GET', '/api/accounts/' . $account->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testAdminCanUpdateTheInitialBalance(): void
    {
        $account = $this->createAccount($this->household);
        $this->loginAs($this->admin);

        $this->requestJson('PATCH', '/api/accounts/' . $account->getId(), ['initialBalance' => '-120.50']);

        self::assertResponseIsSuccessful();
        $data = $this->responseData();
        self::assertSame('-120.50', $data['initialBalance']);
    }

    public function testViewerCannotUpdateAnAccount(): void
    {
        $account = $this->createAccount($this->household);
        $this->loginAs($this->viewer);

        $this->requestJson('PATCH', '/api/accounts/' . $account->getId(), ['name' => 'Nouveau nom']);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAdminCanDeleteAnEmptyAccount(): void
    {
        $account = $this->createAccount($this->household);
        $accountId = $account->getId();
        $this->loginAs($this->admin);

        $this->requestJson('DELETE', '/api/accounts/' . $accountId);
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->requestJson('GET', '/api/accounts/' . $accountId);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testAccountWithTransactionsCannotBeDeleted(): void
    {
        $account = $this->createAccount($this->household);
        $category = $this->createCategory($this->household);
        $this->createTransaction($this->admin, $account, $category);
        $this->loginAs($this->admin);

        $this->requestJson('DELETE', '/api/accounts/' . $account->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseMessage('Ce compte contient des transactions et ne peut pas être supprimé.');
    }
}
