<?php

namespace App\Tests\Controller;

use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\User;
use App\Enum\HouseholdMemberRole;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpFoundation\Response;

final class HouseholdMemberControllerTest extends ApiTestCase
{
    private User $admin;
    private User $viewer;
    private Household $household;
    private HouseholdMember $adminMember;
    private HouseholdMember $viewerMember;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->createUser('admin@example.com', 'Admin');
        $this->viewer = $this->createUser('viewer@example.com', 'Viewer');
        $this->household = $this->createHousehold($this->admin, 'Famille');
        $this->adminMember = $this->household->getHouseholdMembers()->first();
        $this->viewerMember = $this->addMember($this->household, $this->viewer, HouseholdMemberRole::Viewer);
    }

    private function membersUri(): string
    {
        return '/api/households/' . $this->household->getId() . '/members';
    }

    private function memberUri(HouseholdMember $member): string
    {
        return $this->membersUri() . '/' . $member->getId();
    }

    // ---------- List ----------

    public function testMemberCanListTheMembers(): void
    {
        $this->loginAs($this->viewer);

        $this->requestJson('GET', $this->membersUri());

        self::assertResponseIsSuccessful();
        $data = $this->responseData();
        self::assertCount(2, $data);
        // Admins first
        self::assertSame('Admin', $data[0]['name']);
        self::assertSame('admin', $data[0]['role']);
        self::assertSame('viewer', $data[1]['role']);
        self::assertArrayNotHasKey('email', $data[0]);
    }

    public function testStrangerCannotListTheMembers(): void
    {
        $stranger = $this->createUser('stranger@example.com');
        $this->loginAs($stranger);

        $this->requestJson('GET', $this->membersUri());

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // ---------- Add ----------

    public function testAdminCanShareTheHouseholdWithARelative(): void
    {
        $relative = $this->createUser('proche@example.com', 'Proche');
        $this->loginAs($this->admin);

        $this->requestJson('POST', $this->membersUri(), ['email' => 'proche@example.com']);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = $this->responseData();
        self::assertSame($relative->getId(), $data['userId']);
        self::assertSame('viewer', $data['role']);

        // The relative can now read the household
        $this->loginAs($relative);
        $this->requestJson('GET', '/api/households/' . $this->household->getId());
        self::assertResponseIsSuccessful();
    }

    public function testUnknownEmailIsRejected(): void
    {
        $this->loginAs($this->admin);

        $this->requestJson('POST', $this->membersUri(), ['email' => 'inconnu@example.com']);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseMessage('Aucun compte n\'est associé à cet email.');
    }

    public function testSamePersonCannotBeAddedTwice(): void
    {
        $this->loginAs($this->admin);

        $this->requestJson('POST', $this->membersUri(), ['email' => 'viewer@example.com']);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        $this->assertResponseMessage('Cette personne fait déjà partie du foyer.');
    }

    public function testDatabaseRefusesADuplicateMember(): void
    {
        // Bypasses the API on purpose: the unique index must block the duplicate on its own
        $this->expectException(UniqueConstraintViolationException::class);

        $this->addMember($this->household, $this->viewer, HouseholdMemberRole::Viewer);
    }

    public function testViewerCannotAddAMember(): void
    {
        $this->createUser('proche@example.com');
        $this->loginAs($this->viewer);

        $this->requestJson('POST', $this->membersUri(), ['email' => 'proche@example.com']);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    // ---------- Change the role ----------

    public function testAdminCanPromoteAViewer(): void
    {
        $this->loginAs($this->admin);

        $this->requestJson('PATCH', $this->memberUri($this->viewerMember), ['role' => 'admin']);

        self::assertResponseIsSuccessful();
        $data = $this->responseData();
        self::assertSame('admin', $data['role']);
    }

    public function testLastAdminCannotDemoteThemself(): void
    {
        $this->loginAs($this->admin);

        $this->requestJson('PATCH', $this->memberUri($this->adminMember), ['role' => 'viewer']);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        $this->assertResponseMessage('Le foyer doit garder au moins un administrateur.');
    }

    public function testViewerCannotChangeARole(): void
    {
        $this->loginAs($this->viewer);

        $this->requestJson('PATCH', $this->memberUri($this->viewerMember), ['role' => 'admin']);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testMemberOfAnotherHouseholdIsNotFound(): void
    {
        $stranger = $this->createUser('stranger@example.com');
        $otherHousehold = $this->createHousehold($stranger, 'Autre foyer');
        $strangerMember = $otherHousehold->getHouseholdMembers()->first();
        $this->loginAs($this->admin);

        // Valid member id, but it belongs to another household
        $this->requestJson('PATCH', $this->membersUri() . '/' . $strangerMember->getId(), ['role' => 'viewer']);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // ---------- Remove ----------

    public function testAdminCanRemoveAViewer(): void
    {
        $this->loginAs($this->admin);

        $this->requestJson('DELETE', $this->memberUri($this->viewerMember));
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // The removed person has lost access to the household
        $this->loginAs($this->viewer);
        $this->requestJson('GET', '/api/households/' . $this->household->getId());
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testViewerCanLeaveTheHousehold(): void
    {
        $this->loginAs($this->viewer);

        $this->requestJson('DELETE', $this->memberUri($this->viewerMember));

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testViewerCannotRemoveTheAdmin(): void
    {
        $this->loginAs($this->viewer);

        $this->requestJson('DELETE', $this->memberUri($this->adminMember));

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testLastAdminCannotLeaveTheHousehold(): void
    {
        $this->loginAs($this->admin);

        $this->requestJson('DELETE', $this->memberUri($this->adminMember));

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }
}
