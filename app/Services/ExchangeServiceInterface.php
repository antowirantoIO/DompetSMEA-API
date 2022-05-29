<?php

namespace App\Services;

interface ExchangeServiceInterface
{
    public function convertTo(string $fromCurrency, string $toCurrency, float|int|string $amount): string;
}
