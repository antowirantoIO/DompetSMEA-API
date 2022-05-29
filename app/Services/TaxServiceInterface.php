<?php

namespace App\Services;

use App\Interfaces\Wallet;

interface TaxServiceInterface
{
    public function getFee(Wallet $wallet, float|int|string $amount): string;
}
