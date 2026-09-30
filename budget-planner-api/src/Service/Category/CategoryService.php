<?php
// src/Service/Category/CategoryService.php

namespace App\Service\Category;

use App\Entity\Category;
use App\Entity\User;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;
use App\Repository\CategoryRepository;
use App\Service\Household\HouseholdAccessChecker;

class CategoryService
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private HouseholdAccessChecker $householdAccessChecker,
        private CategoryHydrator $categoryHydrator
    ) {
    }

    /**
     * @return Category[]
     */
    public function listForUser(User $user): array
    {
        return $this->categoryRepository->findAllForUser($user);
    }

    public function getForUser(int $id, User $user): Category
    {
        $category = $this->categoryRepository->findOneForUser($id, $user);

        if ($category === null) {
            throw new NotFoundException('Catégorie introuvable.');
        }

        return $category;
    }

    public function create(array $data, User $user): Category
    {
        $category = new Category();

        $this->categoryHydrator->hydrate($category, $data, $user, false);
        $this->assertCanEdit($category, $user);

        $this->categoryRepository->save($category);

        return $category;
    }

    public function update(int $id, array $data, User $user): Category
    {
        $category = $this->getForUser($id, $user);
        $this->assertCanEdit($category, $user);

        $this->categoryHydrator->hydrate($category, $data, $user, true);
        // Checked again in case the category was moved to another household
        $this->assertCanEdit($category, $user);

        $this->categoryRepository->save($category);

        return $category;
    }

    public function delete(int $id, User $user): void
    {
        $category = $this->getForUser($id, $user);
        $this->assertCanEdit($category, $user);
        $this->assertIsNotUsed($category);

        $this->categoryRepository->remove($category);
    }

    private function assertCanEdit(Category $category, User $user): void
    {
        $this->householdAccessChecker->assertIsAdmin($user, $category->getHousehold());
    }

    // The foreign keys would make the DELETE fail with a 500 otherwise
    private function assertIsNotUsed(Category $category): void
    {
        $hasTransactions = !$category->getTransactions()->isEmpty();
        $hasScenarioTransactions = !$category->getScenarioTransactions()->isEmpty();

        if ($hasTransactions || $hasScenarioTransactions) {
            throw new ValidationException('Cette catégorie est utilisée par des transactions et ne peut pas être supprimée.');
        }
    }
}
