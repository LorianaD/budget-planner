<?php

namespace App\Enum;

enum ScenarioTransactionAction : string
{
    case Add = 'add';
    case Update = 'update';
    case Delete = 'delete';
}