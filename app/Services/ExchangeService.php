<?php

namespace App\Services;

final class ExchangeService implements ExchangeServiceInterface
{
    public function convertTo(string $fromCurrency, string $toCurrency, float|int|string $amount): string
    {
        return (string) $amount;
    }
}
