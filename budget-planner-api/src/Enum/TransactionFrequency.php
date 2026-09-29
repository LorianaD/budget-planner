<?php

namespace App\Enum;

enum TransactionFrequency : string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';
}