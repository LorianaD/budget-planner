<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\HouseholdMember\HouseholdMemberPresenter;
use App\Service\HouseholdMember\HouseholdMemberService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/households/{householdId}/members', name: 'app_household_member_', requirements: ['householdId' => '\d+'])]
final class HouseholdMemberController extends ApiController
{
    public function __construct(
        private HouseholdMemberService $householdMemberService,
        private HouseholdMemberPresenter $householdMemberPresenter,
    )
    {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(int $householdId, #[CurrentUser] User $user): JsonResponse
    {
        $members = $this->householdMemberService->listForHousehold($householdId, $user);

        return $this->json(
            $this->householdMemberPresenter->toList($members)
        );
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(int $householdId, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = $this->decodeJson($request);
        $member = $this->householdMemberService->add($householdId, $data, $user);

        return $this->json(
            $this->householdMemberPresenter->toArray($member),
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'edit', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function edit(int $householdId, int $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = $this->decodeJson($request);
        $member = $this->householdMemberService->update($householdId, $id, $data, $user);

        return $this->json($this->householdMemberPresenter->toArray($member));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $householdId, int $id, #[CurrentUser] User $user): JsonResponse
    {
        $this->householdMemberService->remove($householdId, $id, $user);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
