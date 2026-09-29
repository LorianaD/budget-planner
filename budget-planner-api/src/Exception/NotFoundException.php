<?php
// src/Exception/NotFoundException.php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

class NotFoundException extends ApiException
{
    public function __construct(string $message = 'Ressource introuvable.')
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}