<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\User\UserProfile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserProfileController extends AbstractController
{
    // Under /api so the JWT firewall authenticates the user
    #[Route('/api/me', name: 'app_user_profile', methods: ['GET'])]
    public function index(UserProfile $userProfile): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'message' => 'Non authentifié.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json(
            $userProfile->toArray($user),
        );
    }
}
