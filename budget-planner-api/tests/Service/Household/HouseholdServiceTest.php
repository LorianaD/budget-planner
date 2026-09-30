<?php

namespace App\Tests\Service\Household;

use App\Entity\Account;
use App\Entity\Category;
use App\Entity\Household;
use App\Entity\User;
use App\Enum\HouseholdMemberRole;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\HouseholdMemberRepository;
use App\Repository\HouseholdRepository;
use App\Service\Household\HouseholdAccessChecker;
use App\Service\Household\HouseholdHydrator;
use App\Service\Household\HouseholdService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HouseholdServiceTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testGetForUserThrowsWhenTheHouseholdIsNotVisible(): void
    {
        $householdRepository = $this->createStub(HouseholdRepository::class);
        $householdRepository->method('findOneForUser')->willReturn(null);

        $service = $this->createService($householdRepository, $this->createMemberRepository(true));

        $this->expectException(NotFoundException::class);

        $service->getForUser(42, $this->user);
    }

    public function testCreatorBecomesAdminOfTheNewHousehold(): void
    {
        $householdRepository = $this->createMock(HouseholdRepository::class);
        $householdRepository->expects($this->once())->method('saveWithMember');

        $service = $this->createService($householdRepository, $this->createMemberRepository(false));

        $household = $service->create(['name' => 'Famille Martin'], $this->user);

        self::assertSame('Famille Martin', $household->getName());

        $members = $household->getHouseholdMembers();
        self::assertCount(1, $members);
        self::assertSame($this->user, $members->first()->getUser());
        self::assertSame(HouseholdMemberRole::Admin, $members->first()->getRole());
    }

    public function testCreationWithoutNameIsNotSaved(): void
    {
        $householdRepository = $this->createMock(HouseholdRepository::class);
        $householdRepository->expects($this->never())->method('saveWithMember');

        $service = $this->createService($householdRepository, $this->createMemberRepository(true));

        $this->expectException(ValidationException::class);

        $service->create([], $this->user);
    }

    public function testAdminCanRenameTheHousehold(): void
    {
        $household = $this->createHousehold();
        $householdRepository = $this->createMock(HouseholdRepository::class);
        $householdRepository->method('findOneForUser')->willReturn($household);
        $householdRepository->expects($this->once())->method('save')->with($household);

        $service = $this->createService($householdRepository, $this->createMemberRepository(true));

        $service->update(1, ['name' => 'Famille Dupont'], $this->user);

        self::assertSame('Famille Dupont', $household->getName());
    }

    public function testViewerCannotRenameTheHousehold(): void
    {
        $household = $this->createHousehold();
        $householdRepository = $this->createMock(HouseholdRepository::class);
        $householdRepository->method('findOneForUser')->willReturn($household);
        $householdRepository->expects($this->never())->method('save');

        $service = $this->createService($householdRepository, $this->createMemberRepository(false));

        $this->expectException(ForbiddenException::class);

        $service->update(1, ['name' => 'Famille Dupont'], $this->user);
    }

    public function testAdminCanDeleteAnEmptyHousehold(): void
    {
        $household = $this->createHousehold();
        $householdRepository = $this->createMock(HouseholdRepository::class);
        $householdRepository->method('findOneForUser')->willReturn($household);
        $householdRepository->expects($this->once())->method('remove')->with($household);

        $service = $this->createService($householdRepository, $this->createMemberRepository(true));

        $service->delete(1, $this->user);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function householdContentProvider(): array
    {
        return [
            'avec un compte' => ['account'],
            'avec une catégorie' => ['category'],
        ];
    }

    #[DataProvider('householdContentProvider')]
    public function testHouseholdWithContentCannotBeDeleted(string $content): void
    {
        $household = $this->createHousehold();
        if ($content === 'account') {
            $household->addAccount(new Account());
        } else {
            $household->addCategory(new Category());
        }

        $householdRepository = $this->createMock(HouseholdRepository::class);
        $householdRepository->method('findOneForUser')->willReturn($household);
        $householdRepository->expects($this->never())->method('remove');

        $service = $this->createService($householdRepository, $this->createMemberRepository(true));

        $this->expectException(ValidationException::class);

        $service->delete(1, $this->user);
    }

    public function testViewerCannotDeleteTheHousehold(): void
    {
        $household = $this->createHousehold();
        $householdRepository = $this->createMock(HouseholdRepository::class);
        $householdRepository->method('findOneForUser')->willReturn($household);
        $householdRepository->expects($this->never())->method('remove');

        $service = $this->createService($householdRepository, $this->createMemberRepository(false));

        $this->expectException(ForbiddenException::class);

        $service->delete(1, $this->user);
    }

    private function createHousehold(): Household
    {
        $household = new Household();
        $household->setName('Famille Martin');

        return $household;
    }

    private function createMemberRepository(bool $isAdmin): HouseholdMemberRepository
    {
        $memberRepository = $this->createStub(HouseholdMemberRepository::class);
        $memberRepository->method('isAdmin')->willReturn($isAdmin);

        return $memberRepository;
    }

    private function createService(
        HouseholdRepository $householdRepository,
        HouseholdMemberRepository $memberRepository
    ): HouseholdService {
        return new HouseholdService(
            $householdRepository,
            new HouseholdAccessChecker($memberRepository),
            new HouseholdHydrator()
        );
    }
}
