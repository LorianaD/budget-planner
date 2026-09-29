<?php
// src/Controller/ApiController.php

namespace App\Controller;

use App\Exception\ValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

abstract class ApiController extends AbstractController
{
    protected function decodeJson(Request $request): array
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            throw new ValidationException(
                'Le corps de la requête doit être du JSON.'
            );
        }

        return $data;
    }
}