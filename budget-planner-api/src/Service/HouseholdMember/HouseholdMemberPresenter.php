<?php
// src/Service/HouseholdMember/HouseholdMemberPresenter.php

namespace App\Service\HouseholdMember;

use App\Entity\HouseholdMember;

// Single place defining which member fields are exposed to the front
class HouseholdMemberPresenter
{
    // The email is not exposed: a viewer does not need it
    public function toArray(HouseholdMember $member): array
    {
        $user = $member->getUser();

        return [
            'id' => $member->getId(),
            'userId' => $user->getId(),
            'name' => $user->getName(),
            'colour' => $user->getColour(),
            'role' => $member->getRole()->value,
        ];
    }

    /**
     * @param iterable<HouseholdMember> $members
     */
    public function toList(iterable $members): array
    {
        $list = [];

        foreach ($members as $member) {
            $list[] = $this->toArray($member);
        }

        return $list;
    }
}
