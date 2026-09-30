<?php

namespace App\Tests\Unit\Service\Category;

use App\Entity\Category;
use App\Entity\Household;
use App\Enum\CategoryEnvelope;
use App\Service\Category\CategoryPresenter;
use PHPUnit\Framework\TestCase;

final class CategoryPresenterTest extends TestCase
{
    public function testToArrayExposesTheCategoryAndItsHousehold(): void
    {
        $category = $this->createCategory('Courses', CategoryEnvelope::Essential);
        $presenter = new CategoryPresenter();

        $result = $presenter->toArray($category);

        $expected = [
            'id' => null,
            'name' => 'Courses',
            'envelope' => CategoryEnvelope::Essential,
            'household' => [
                'id' => 7,
                'name' => 'Famille Martin',
            ],
        ];
        self::assertSame($expected, $result);
    }

    public function testToListKeepsTheOrderOfTheCategories(): void
    {
        $first = $this->createCategory('Courses', null);
        $second = $this->createCategory('Épargne', CategoryEnvelope::Savings);
        $presenter = new CategoryPresenter();

        $result = $presenter->toList([$first, $second]);

        self::assertCount(2, $result);
        self::assertSame('Courses', $result[0]['name']);
        self::assertNull($result[0]['envelope']);
        self::assertSame('Épargne', $result[1]['name']);
    }

    public function testToListReturnsAnEmptyArrayWhenThereIsNoCategory(): void
    {
        $presenter = new CategoryPresenter();

        self::assertSame([], $presenter->toList([]));
    }

    private function createCategory(string $name, ?CategoryEnvelope $envelope): Category
    {
        $household = $this->createStub(Household::class);
        $household->method('getId')->willReturn(7);
        $household->method('getName')->willReturn('Famille Martin');

        $category = new Category();
        $category->setHousehold($household);
        $category->setName($name);
        $category->setEnvelope($envelope);

        return $category;
    }
}
