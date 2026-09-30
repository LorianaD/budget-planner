<?php

namespace App\Tests\Controller;

use App\Entity\Household;
use App\Entity\User;
use App\Enum\HouseholdMemberRole;
use Symfony\Component\HttpFoundation\Response;

final class CategoryControllerTest extends ApiTestCase
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
        $this->requestJson('GET', '/api/categories');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListOnlyReturnsTheCategoriesOfTheUserHouseholds(): void
    {
        $this->createCategory($this->household, 'Loisirs');
        $this->createCategory($this->household, 'Courses');

        $stranger = $this->createUser('stranger@example.com');
        $otherHousehold = $this->createHousehold($stranger, 'Autre foyer');
        $this->createCategory($otherHousehold, 'Catégorie secrète');

        $this->loginAs($this->viewer);
        $this->requestJson('GET', '/api/categories');

        self::assertResponseIsSuccessful();
        $data = $this->responseData();
        self::assertCount(2, $data);
        // Sorted by name
        self::assertSame('Courses', $data[0]['name']);
        self::assertSame('Loisirs', $data[1]['name']);
        self::assertSame($this->household->getId(), $data[0]['household']['id']);
    }

    public function testAdminCanCreateACategory(): void
    {
        $this->loginAs($this->admin);

        $this->requestJson('POST', '/api/categories', [
            'householdId' => $this->household->getId(),
            'name' => 'Épargne',
            'envelope' => 'savings',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = $this->responseData();
        self::assertIsInt($data['id']);
        self::assertSame('Épargne', $data['name']);
        self::assertSame('savings', $data['envelope']);
    }

    public function testViewerCannotCreateACategory(): void
    {
        $this->loginAs($this->viewer);

        $this->requestJson('POST', '/api/categories', [
            'householdId' => $this->household->getId(),
            'name' => 'Épargne',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCannotCreateACategoryInAnotherHousehold(): void
    {
        $stranger = $this->createUser('stranger@example.com');
        $otherHousehold = $this->createHousehold($stranger, 'Autre foyer');
        $this->loginAs($this->admin);

        $this->requestJson('POST', '/api/categories', [
            'householdId' => $otherHousehold->getId(),
            'name' => 'Intrusion',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseMessage('Foyer introuvable.');
    }

    public function testCreationWithoutNameIsRejected(): void
    {
        $this->loginAs($this->admin);

        $this->requestJson('POST', '/api/categories', [
            'householdId' => $this->household->getId(),
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseMessage('Le champ "name" est obligatoire.');
    }

    public function testMemberCanSeeACategory(): void
    {
        $category = $this->createCategory($this->household, 'Courses');
        $this->loginAs($this->viewer);

        $this->requestJson('GET', '/api/categories/' . $category->getId());

        self::assertResponseIsSuccessful();
        $data = $this->responseData();
        self::assertSame('Courses', $data['name']);
    }

    public function testCategoryOfAnotherHouseholdIsNotFound(): void
    {
        $category = $this->createCategory($this->household, 'Courses');
        $stranger = $this->createUser('stranger@example.com');
        $this->loginAs($stranger);

        $this->requestJson('GET', '/api/categories/' . $category->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testAdminCanRenameACategory(): void
    {
        $category = $this->createCategory($this->household, 'Courses');
        $this->loginAs($this->admin);

        $this->requestJson('PATCH', '/api/categories/' . $category->getId(), ['name' => 'Alimentation']);

        self::assertResponseIsSuccessful();
        $data = $this->responseData();
        self::assertSame('Alimentation', $data['name']);
    }

    public function testViewerCannotRenameACategory(): void
    {
        $category = $this->createCategory($this->household, 'Courses');
        $this->loginAs($this->viewer);

        $this->requestJson('PATCH', '/api/categories/' . $category->getId(), ['name' => 'Alimentation']);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAdminCanDeleteAnUnusedCategory(): void
    {
        $category = $this->createCategory($this->household, 'Courses');
        $categoryId = $category->getId();
        $this->loginAs($this->admin);

        $this->requestJson('DELETE', '/api/categories/' . $categoryId);
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->requestJson('GET', '/api/categories/' . $categoryId);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCategoryUsedByATransactionCannotBeDeleted(): void
    {
        $category = $this->createCategory($this->household, 'Courses');
        $account = $this->createAccount($this->household);
        $this->createTransaction($this->admin, $account, $category);
        $this->loginAs($this->admin);

        $this->requestJson('DELETE', '/api/categories/' . $category->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
