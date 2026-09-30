<?php

namespace App\Tests\Service\Household;

use App\Entity\Account;
use App\Entity\Category;
use App\Entity\Household;
use App\Entity\HouseholdMember;
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
use Doctrine\ORM\EntityManagerInterface;
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

        $service = $this->createService(
            $householdRepository,
            $this->createMemberRepository(true),
            $this->createStub(EntityManagerInterface::class)
        );

        $this->expectException(NotFoundException::class);

        $service->getForUser(42, $this->user);
    }

    public function testCreatorBecomesAdminOfTheNewHousehold(): void
    {
        // The household and its first member are persisted, then saved in a single flush
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(2))->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService(
            $this->createStub(HouseholdRepository::class),
            $this->createMemberRepository(false),
            $entityManager
        );

        $household = $service->create(['name' => 'Famille Martin'], $this->user);

        self::assertSame('Famille Martin', $household->getName());

        $members = $household->getHouseholdMembers();
        self::assertCount(1, $members);
        self::assertSame($this->user, $members->first()->getUser());
        self::assertSame(HouseholdMemberRole::Admin, $members->first()->getRole());
    }

    public function testCreationWithoutNameIsNotSaved(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $service = $this->createService(
            $this->createStub(HouseholdRepository::class),
            $this->createMemberRepository(true),
            $entityManager
        );

        $this->expectException(ValidationException::class);

        $service->create([], $this->user);
    }

    public function testAdminCanRenameTheHousehold(): void
    {
        $household = $this->createHousehold();
        $householdRepository = $this->createStub(HouseholdRepository::class);
        $householdRepository->method('findOneForUser')->willReturn($household);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService($householdRepository, $this->createMemberRepository(true), $entityManager);

        $service->update(1, ['name' => 'Famille Dupont'], $this->user);

        self::assertSame('Famille Dupont', $household->getName());
    }

    public function testViewerCannotRenameTheHousehold(): void
    {
        $household = $this->createHousehold();
        $householdRepository = $this->createStub(HouseholdRepository::class);
        $householdRepository->method('findOneForUser')->willReturn($household);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $service = $this->createService($householdRepository, $this->createMemberRepository(false), $entityManager);

        $this->expectException(ForbiddenException::class);

        $service->update(1, ['name' => 'Famille Dupont'], $this->user);
    }

    public function testAdminCanDeleteAnEmptyHousehold(): void
    {
        $household = $this->createHousehold();
        $member = new HouseholdMember();
        $household->addHouseholdMember($member);

        $householdRepository = $this->createStub(HouseholdRepository::class);
        $householdRepository->method('findOneForUser')->willReturn($household);

        // The member is removed before the household, both in the same flush
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(2))->method('remove');
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService($householdRepository, $this->createMemberRepository(true), $entityManager);

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

        $householdRepository = $this->createStub(HouseholdRepository::class);
        $householdRepository->method('findOneForUser')->willReturn($household);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('remove');

        $service = $this->createService($householdRepository, $this->createMemberRepository(true), $entityManager);

        $this->expectException(ValidationException::class);

        $service->delete(1, $this->user);
    }

    public function testViewerCannotDeleteTheHousehold(): void
    {
        $household = $this->createHousehold();
        $householdRepository = $this->createStub(HouseholdRepository::class);
        $householdRepository->method('findOneForUser')->willReturn($household);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('remove');

        $service = $this->createService($householdRepository, $this->createMemberRepository(false), $entityManager);

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
        HouseholdMemberRepository $memberRepository,
        EntityManagerInterface $entityManager
    ): HouseholdService {
        return new HouseholdService(
            $householdRepository,
            new HouseholdAccessChecker($memberRepository),
            new HouseholdHydrator(),
            $entityManager
        );
    }
}
