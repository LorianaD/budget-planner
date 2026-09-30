<?php

namespace App\Tests\Service\Household;

use App\Entity\Household;
use App\Entity\User;
use App\Exception\ForbiddenException;
use App\Repository\HouseholdMemberRepository;
use App\Service\Household\HouseholdAccessChecker;
use PHPUnit\Framework\TestCase;

final class HouseholdAccessCheckerTest extends TestCase
{
    public function testAdminIsAllowed(): void
    {
        $checker = $this->createChecker(true);

        $checker->assertIsAdmin(new User(), new Household());

        // No exception means the admin is allowed
        $this->addToAssertionCount(1);
    }

    public function testViewerIsRefused(): void
    {
        $checker = $this->createChecker(false);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessageIs('Vous avez un accès en lecture seule à ce foyer.');

        $checker->assertIsAdmin(new User(), new Household());
    }

    private function createChecker(bool $isAdmin): HouseholdAccessChecker
    {
        $memberRepository = $this->createStub(HouseholdMemberRepository::class);
        $memberRepository->method('isAdmin')->willReturn($isAdmin);

        return new HouseholdAccessChecker($memberRepository);
    }
}
