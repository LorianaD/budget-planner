<?php
// src/Exception/ApiException.php

namespace App\Exception;

abstract class ApiException extends \RuntimeException
{
    abstract public function getStatusCode(): int;
}