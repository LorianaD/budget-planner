<?php

namespace App\Service;

use App\Entity\User;

// Single place defining which user fields are exposed to the front
class UserProfile
{
    public function toArray(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'color' => $user->getColour(),
        ];
    }
}