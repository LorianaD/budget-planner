<?php

namespace App\Enum;

enum TransactionType : string
{
    case Expense = 'expense';
    case Income = 'income';
}