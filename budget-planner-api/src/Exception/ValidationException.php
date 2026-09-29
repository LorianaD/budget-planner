<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

// Thrown when the submitted data is invalid (HTTP 400)
class ValidationException extends ApiException
{
    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}