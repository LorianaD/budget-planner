<?php
// src/Exception/ForbiddenException.php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

class ForbiddenException extends ApiException
{
    public function __construct(string $message = 'Action non autorisée.')
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_FORBIDDEN;
    }
}