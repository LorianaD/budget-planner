<?php

namespace App\Tests\Service\Household;

use App\Entity\Household;
use App\Entity\HouseholdMember;
use App\Entity\User;
use App\Enum\HouseholdMemberRole;
use App\Service\Household\HouseholdPresenter;
use App\Service\HouseholdMember\HouseholdMemberPresenter;
use PHPUnit\Framework\TestCase;

final class HouseholdPresenterTest extends TestCase
{
    public function testToArrayExposesTheHouseholdAndItsMembers(): void
    {
        $household = new Household();
        $household->setName('Famille Martin');
        $household->setCreatedAt(new \DateTimeImmutable('2026-09-01 14:30:00'));
        $this->addMember($household, 'Loriana', '#FF8800', HouseholdMemberRole::Admin);
        $this->addMember($household, 'Camille', null, HouseholdMemberRole::Viewer);

        $presenter = new HouseholdPresenter(new HouseholdMemberPresenter());
        $result = $presenter->toArray($household);

        self::assertSame('Famille Martin', $result['name']);
        // Only the day is exposed, not the time
        self::assertSame('2026-09-01', $result['createdAt']);
        self::assertCount(2, $result['members']);
        self::assertSame('Loriana', $result['members'][0]['name']);
        self::assertSame('#FF8800', $result['members'][0]['colour']);
        self::assertSame('admin', $result['members'][0]['role']);
        self::assertSame('viewer', $result['members'][1]['role']);
    }

    public function testMembersEmailIsNeverExposed(): void
    {
        $household = new Household();
        $household->setName('Famille Martin');
        $this->addMember($household, 'Loriana', null, HouseholdMemberRole::Admin);

        $presenter = new HouseholdPresenter(new HouseholdMemberPresenter());
        $result = $presenter->toArray($household);

        self::assertArrayNotHasKey('email', $result['members'][0]);
    }

    private function addMember(Household $household, string $name, ?string $colour, HouseholdMemberRole $role): void
    {
        $user = new User();
        $user->setEmail(strtolower($name) . '@example.com');
        $user->setName($name);
        $user->setColour($colour);

        $member = new HouseholdMember();
        $member->setUser($user);
        $member->setRole($role);
        $household->addHouseholdMember($member);
    }
}
