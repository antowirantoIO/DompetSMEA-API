<?php

namespace App\Services;

use App\Models\Wallet;

interface RegulatorServiceInterface
{
    public function missing(Wallet $wallet): bool;

    public function diff(Wallet $wallet): string;

    public function amount(Wallet $wallet): string;

    public function sync(Wallet $wallet, float|int|string $value): bool;

    public function increase(Wallet $wallet, float|int|string $value): string;

    public function decrease(Wallet $wallet, float|int|string $value): string;

    public function approve(): void;

    public function purge(): void;
}
