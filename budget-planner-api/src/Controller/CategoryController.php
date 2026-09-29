<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\Category\CategoryPresenter;
use App\Service\Category\CategoryService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/categories', name: 'app_category_')]
final class CategoryController extends ApiController
{
    public function __construct(
        private CategoryService $categoryService,
        private CategoryPresenter $categoryPresenter,
    )
    {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(#[CurrentUser] User $user): JsonResponse
    {
        $categories = $this->categoryService->listForUser($user);

        return $this->json(
            $this->categoryPresenter->toList($categories)
        );
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = $this->decodeJson($request);
        $category = $this->categoryService->create($data, $user);

        return $this->json(
            $this->categoryPresenter->toArray($category),
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, #[CurrentUser] User $user): JsonResponse
    {
        $category = $this->categoryService->getForUser($id, $user);

        return $this->json($this->categoryPresenter->toArray($category));
    }

    #[Route('/{id}', name: 'edit', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = $this->decodeJson($request);
        $category = $this->categoryService->update($id, $data, $user);

        return $this->json($this->categoryPresenter->toArray($category));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id, #[CurrentUser] User $user): JsonResponse
    {
        $this->categoryService->delete($id, $user);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
