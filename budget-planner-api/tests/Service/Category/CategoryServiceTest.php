<?php

namespace App\Tests\Service\Category;

use App\Entity\Category;
use App\Entity\Household;
use App\Entity\Transaction;
use App\Entity\User;
use App\Exception\ConflictException;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundException;
use App\Repository\CategoryRepository;
use App\Repository\HouseholdMemberRepository;
use App\Repository\HouseholdRepository;
use App\Service\Category\CategoryHydrator;
use App\Service\Category\CategoryService;
use App\Service\Household\HouseholdAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class CategoryServiceTest extends TestCase
{
    private User $user;
    private Household $household;

    protected function setUp(): void
    {
        $this->user = new User();
        $this->household = new Household();
    }

    public function testGetForUserReturnsTheCategory(): void
    {
        $category = $this->createCategory();
        $categoryRepository = $this->createStub(CategoryRepository::class);
        $categoryRepository->method('findOneForUser')->willReturn($category);

        $service = $this->createService(
            $categoryRepository,
            $this->createMemberRepository(true),
            $this->createStub(EntityManagerInterface::class)
        );

        self::assertSame($category, $service->getForUser(1, $this->user));
    }

    public function testGetForUserThrowsWhenTheCategoryIsNotVisible(): void
    {
        $categoryRepository = $this->createStub(CategoryRepository::class);
        $categoryRepository->method('findOneForUser')->willReturn(null);

        $service = $this->createService(
            $categoryRepository,
            $this->createMemberRepository(true),
            $this->createStub(EntityManagerInterface::class)
        );

        $this->expectException(NotFoundException::class);

        $service->getForUser(42, $this->user);
    }

    public function testAdminCanCreateACategory(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(Category::class));
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService(
            $this->createStub(CategoryRepository::class),
            $this->createMemberRepository(true),
            $entityManager
        );

        $category = $service->create(['householdId' => 1, 'name' => 'Loisirs'], $this->user);

        self::assertSame('Loisirs', $category->getName());
        self::assertSame($this->household, $category->getHousehold());
    }

    public function testViewerCannotCreateACategory(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $service = $this->createService(
            $this->createStub(CategoryRepository::class),
            $this->createMemberRepository(false),
            $entityManager
        );

        $this->expectException(ForbiddenException::class);

        $service->create(['householdId' => 1, 'name' => 'Loisirs'], $this->user);
    }

    public function testAdminCanUpdateACategory(): void
    {
        $category = $this->createCategory();
        $categoryRepository = $this->createStub(CategoryRepository::class);
        $categoryRepository->method('findOneForUser')->willReturn($category);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService($categoryRepository, $this->createMemberRepository(true), $entityManager);

        $updatedCategory = $service->update(1, ['name' => 'Nouveau nom'], $this->user);

        self::assertSame('Nouveau nom', $updatedCategory->getName());
    }

    public function testViewerCannotUpdateACategory(): void
    {
        $category = $this->createCategory();
        $categoryRepository = $this->createStub(CategoryRepository::class);
        $categoryRepository->method('findOneForUser')->willReturn($category);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $service = $this->createService($categoryRepository, $this->createMemberRepository(false), $entityManager);

        try {
            $service->update(1, ['name' => 'Nouveau nom'], $this->user);
            self::fail('Une ForbiddenException était attendue.');
        } catch (ForbiddenException) {
            // The category must not have been modified before the permission check
            self::assertSame('Courses', $category->getName());
        }
    }

    public function testUpdateIsRefusedWhenTheCategoryIsMovedToAReadOnlyHousehold(): void
    {
        $category = $this->createCategory();
        $categoryRepository = $this->createStub(CategoryRepository::class);
        $categoryRepository->method('findOneForUser')->willReturn($category);

        // Admin of the current household, but only viewer of the target household
        $memberRepository = $this->createStub(HouseholdMemberRepository::class);
        $memberRepository->method('isAdmin')->willReturn(true, false);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('flush');

        $service = $this->createService($categoryRepository, $memberRepository, $entityManager);

        $this->expectException(ForbiddenException::class);

        $service->update(1, ['householdId' => 2], $this->user);
    }

    public function testAdminCanDeleteAnUnusedCategory(): void
    {
        $category = $this->createCategory();
        $categoryRepository = $this->createStub(CategoryRepository::class);
        $categoryRepository->method('findOneForUser')->willReturn($category);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($category);
        $entityManager->expects($this->once())->method('flush');

        $service = $this->createService($categoryRepository, $this->createMemberRepository(true), $entityManager);

        $service->delete(1, $this->user);
    }

    public function testCategoryUsedByTransactionsCannotBeDeleted(): void
    {
        $category = $this->createCategory();
        $category->addTransaction(new Transaction());

        $categoryRepository = $this->createStub(CategoryRepository::class);
        $categoryRepository->method('findOneForUser')->willReturn($category);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('remove');

        $service = $this->createService($categoryRepository, $this->createMemberRepository(true), $entityManager);

        $this->expectException(ConflictException::class);

        $service->delete(1, $this->user);
    }

    public function testViewerCannotDeleteACategory(): void
    {
        $category = $this->createCategory();
        $categoryRepository = $this->createStub(CategoryRepository::class);
        $categoryRepository->method('findOneForUser')->willReturn($category);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('remove');

        $service = $this->createService($categoryRepository, $this->createMemberRepository(false), $entityManager);

        $this->expectException(ForbiddenException::class);

        $service->delete(1, $this->user);
    }

    private function createCategory(): Category
    {
        $category = new Category();
        $category->setHousehold($this->household);
        $category->setName('Courses');

        return $category;
    }

    private function createMemberRepository(bool $isAdmin): HouseholdMemberRepository
    {
        $memberRepository = $this->createStub(HouseholdMemberRepository::class);
        $memberRepository->method('isAdmin')->willReturn($isAdmin);

        return $memberRepository;
    }

    // Uses the real hydrator: only the database access is replaced by a stub
    private function createService(
        CategoryRepository $categoryRepository,
        HouseholdMemberRepository $memberRepository,
        EntityManagerInterface $entityManager
    ): CategoryService {
        $householdRepository = $this->createStub(HouseholdRepository::class);
        $householdRepository->method('findOneForUser')->willReturn($this->household);

        $hydrator = new CategoryHydrator($householdRepository);

        return new CategoryService(
            $categoryRepository,
            new HouseholdAccessChecker($memberRepository),
            $hydrator,
            $entityManager
        );
    }
}
