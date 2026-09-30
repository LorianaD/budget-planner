<?php

namespace App\Exception;

// Thrown when the email already belongs to another user (HTTP 409)
class EmailAlreadyUsedException extends ConflictException
{
    public function __construct()
    {
        parent::__construct(
            'Cet email est déjà utilisé.'
        );
    }
}
