<?php
// src/Service/Household/HouseholdPresenter.php

namespace App\Service\Household;

use App\Entity\Household;
use App\Entity\HouseholdMember;

// Single place defining which household fields are exposed to the front
class HouseholdPresenter
{
    public function toArray(Household $household): array
    {
        $members = [];
        foreach ($household->getHouseholdMembers() as $member) {
            $members[] = $this->memberToArray($member);
        }

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

    // The email is not exposed: a viewer does not need it
    private function memberToArray(HouseholdMember $member): array
    {
        return [
            'userId' => $member->getUser()->getId(),
            'name' => $member->getUser()->getName(),
            'role' => $member->getRole()->value,
        ];
    }
}
