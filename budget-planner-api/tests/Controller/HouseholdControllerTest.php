<?php

namespace App\Tests\Controller;

use App\Entity\Household;
use App\Entity\User;
use App\Enum\HouseholdMemberRole;
use Symfony\Component\HttpFoundation\Response;

final class HouseholdControllerTest extends ApiTestCase
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
        $this->requestJson('GET', '/api/households');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListOnlyReturnsTheHouseholdsOfTheUser(): void
    {
        $stranger = $this->createUser('stranger@example.com');
        $this->createHousehold($stranger, 'Autre foyer');

        $this->loginAs($this->viewer);
        $this->requestJson('GET', '/api/households');

        self::assertResponseIsSuccessful();
        $data = $this->responseData();
        self::assertCount(1, $data);
        self::assertSame('Famille', $data[0]['name']);
        self::assertCount(2, $data[0]['members']);
    }

    public function testCreatorBecomesAdminOfTheNewHousehold(): void
    {
        $this->loginAs($this->viewer);

        $this->requestJson('POST', '/api/households', ['name' => 'Mon foyer']);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = $this->responseData();
        self::assertIsInt($data['id']);
        self::assertSame('Mon foyer', $data['name']);
        self::assertCount(1, $data['members']);
        self::assertSame($this->viewer->getId(), $data['members'][0]['userId']);
        self::assertSame('admin', $data['members'][0]['role']);
    }

    public function testCreationWithoutNameIsRejected(): void
    {
        $this->loginAs($this->admin);

        $this->requestJson('POST', '/api/households', []);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseMessage('Le champ "name" est obligatoire.');
    }

    public function testMemberCanSeeTheHousehold(): void
    {
        $this->loginAs($this->viewer);

        $this->requestJson('GET', '/api/households/' . $this->household->getId());

        self::assertResponseIsSuccessful();
        $data = $this->responseData();
        self::assertSame('Famille', $data['name']);
    }

    public function testHouseholdOfSomeoneElseIsNotFound(): void
    {
        $stranger = $this->createUser('stranger@example.com');
        $this->loginAs($stranger);

        $this->requestJson('GET', '/api/households/' . $this->household->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testAdminCanRenameTheHousehold(): void
    {
        $this->loginAs($this->admin);

        $this->requestJson('PATCH', '/api/households/' . $this->household->getId(), ['name' => 'Famille Martin']);

        self::assertResponseIsSuccessful();
        $data = $this->responseData();
        self::assertSame('Famille Martin', $data['name']);
    }

    public function testViewerCannotRenameTheHousehold(): void
    {
        $this->loginAs($this->viewer);

        $this->requestJson('PATCH', '/api/households/' . $this->household->getId(), ['name' => 'Famille Martin']);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAdminCanDeleteAnEmptyHousehold(): void
    {
        $householdId = $this->household->getId();
        $this->loginAs($this->admin);

        $this->requestJson('DELETE', '/api/households/' . $householdId);
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->requestJson('GET', '/api/households/' . $householdId);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testHouseholdWithAnAccountCannotBeDeleted(): void
    {
        $this->createAccount($this->household);
        $this->loginAs($this->admin);

        $this->requestJson('DELETE', '/api/households/' . $this->household->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testViewerCannotDeleteTheHousehold(): void
    {
        $this->loginAs($this->viewer);

        $this->requestJson('DELETE', '/api/households/' . $this->household->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
