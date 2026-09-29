<?php

namespace App\Controller;

use App\Exception\EmailAlreadyUsedException;
use App\Exception\ValidationException;
use App\Service\User\UserProfile;
use App\Service\User\UserRegisterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterController extends AbstractController
{
    #[Route('/api/register', name: 'app_register', methods: ['POST'])]
    public function index(Request $request, UserRegisterService $userRegisterService, UserProfile $userProfile): JsonResponse
    {   
        $data = json_decode(
            $request->getContent(), 
            true
        );

        if (!is_array($data)) {
            return $this->json([
                'message' => 'Le corps de la requête doit être du JSON.'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $userRegisterService->register($data);
        } catch (ValidationException $exception) {
            return $this->json([
                'message' => $exception->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        } catch (EmailAlreadyUsedException $exception) {
            return $this->json([
                'message' => $exception->getMessage()
            ], Response::HTTP_CONFLICT);
        }

        return $this->json(
            $userProfile->toArray($user),
            Response::HTTP_CREATED
        );
    }
}
