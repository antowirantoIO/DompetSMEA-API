<?php

namespace App\Interfaces;

interface ProductInterface extends Wallet
{
    public function getAmountProduct(Customer $customer): int|string;

    public function getMetaProduct(): ?array;
}
