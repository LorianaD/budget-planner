<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

// Thrown when the email already belongs to another user (HTTP 409)
class EmailAlreadyUsedException extends ApiException
{
    public function __construct()
    {
        parent::__construct(
            'Cet email est déjà utilisé.'
        );
    }

        public function getStatusCode(): int
    {
        return Response::HTTP_CONFLICT;
    }
}