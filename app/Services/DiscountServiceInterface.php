<?php

namespace App\Services;

use App\Interfaces\Wallet;

interface DiscountServiceInterface
{
    public function getDiscount(Wallet $customer, Wallet $product): int;
}
