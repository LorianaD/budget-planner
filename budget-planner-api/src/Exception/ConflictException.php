<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

// Thrown when a valid request conflicts with the current state of the data (HTTP 409)
class ConflictException extends ApiException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_CONFLICT;
    }
}