<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\Transaction\TransactionPresenter;
use App\Service\Transaction\TransactionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/transactions', name: 'app_transaction_')]
final class TransactionController extends ApiController
{
    public function __construct(
        private TransactionService $transactionService,
        private TransactionPresenter $transactionPresenter,
    )
    {}

    #[Route('', name: 'index', methods: 'GET')]
    public function index(#[CurrentUser] User $user): JsonResponse
    {
        $transactions = $this->transactionService->listForUser($user);

        return $this->json(
            $this->transactionPresenter->toList($transactions)
        );
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = $this->decodeJson($request);
        $transaction = $this->transactionService->create($data, $user);

        return $this->json(
            $this->transactionPresenter->toArray($transaction),
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, #[CurrentUser] User $user): JsonResponse
    {
        $transaction = $this->transactionService->getForUser($id, $user);

        return $this->json($this->transactionPresenter->toArray($transaction));
    }

    #[Route('/{id}', name: 'edit', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = $this->decodeJson($request);
        $transaction = $this->transactionService->update($id, $data, $user);

        return $this->json($this->transactionPresenter->toArray($transaction));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id, #[CurrentUser] User $user): JsonResponse
    {
        $this->transactionService->delete($id, $user);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
