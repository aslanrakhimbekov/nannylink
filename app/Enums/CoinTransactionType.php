<?php

namespace App\Enums;

enum CoinTransactionType: string
{
    case DEPOSIT = 'deposit';
    case SPEND = 'spend';
    case REFUND = 'refund';
}
