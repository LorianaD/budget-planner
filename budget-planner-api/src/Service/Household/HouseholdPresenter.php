<?php
// src/Service/Household/HouseholdPresenter.php

namespace App\Service\Household;

use App\Entity\Household;
use App\Service\HouseholdMember\HouseholdMemberPresenter;

// Single place defining which household fields are exposed to the front
class HouseholdPresenter
{
    public function __construct(
        private HouseholdMemberPresenter $householdMemberPresenter,
    ) {
    }

    public function toArray(Household $household): array
    {
        $members = $this->householdMemberPresenter->toList($household->getHouseholdMembers());

        return [
            'id' => $household->getId(),
            'name' => $household->getName(),
            'createdAt' => $household->getCreatedAt()->format('Y-m-d'),
            'members' => $members,
        ];
    }

    /**
     * @param Household[] $households
     */
    public function toList(array $households): array
    {
        $list = [];

        foreach ($households as $household) {
            $list[] = $this->toArray($household);
        }

        return $list;
    }
}
