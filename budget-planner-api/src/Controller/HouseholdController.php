<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\Household\HouseholdPresenter;
use App\Service\Household\HouseholdService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/households', name: 'app_household_')]
final class HouseholdController extends ApiController
{
    public function __construct(
        private HouseholdService $householdService,
        private HouseholdPresenter $householdPresenter,
    )
    {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): JsonResponse
    {
        $households = $this->householdService->listForUser($user);

        return $this->json(
            $this->householdPresenter->toList($households)
        );
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = $this->decodeJson($request);
        $household = $this->householdService->create($data, $user);

        return $this->json(
            $this->householdPresenter->toArray($household),
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, #[CurrentUser] User $user): JsonResponse
    {
        $household = $this->householdService->getForUser($id, $user);

        return $this->json($this->householdPresenter->toArray($household));
    }

    #[Route('/{id}', name: 'edit', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = $this->decodeJson($request);
        $household = $this->householdService->update($id, $data, $user);

        return $this->json($this->householdPresenter->toArray($household));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id, #[CurrentUser] User $user): JsonResponse
    {
        $this->householdService->delete($id, $user);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
