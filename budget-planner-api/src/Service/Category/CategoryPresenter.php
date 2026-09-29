<?php

namespace App\Service\Category;

use App\Entity\Category;

class CategoryPresenter
{
    public function toArray(Category $category) : array
    {
        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'envelope' => $category->getEnvelope(),
            'household' => [
                'id' => $category->getHousehold()->getId(),
                'name' => $category->getHousehold()->getName(),
            ],
        ];
    }

    /**
     * @param Category[] $categories
     */
    public function toList(array $categories): array
    {
        $list = [];

        foreach ($categories as $category) {
            $list[] = $this->toArray($category);
        }

        return $list;
    }
}