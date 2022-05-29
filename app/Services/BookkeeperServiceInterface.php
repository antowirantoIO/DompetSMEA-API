<?php

namespace App\Services;

use App\Internal\Exceptions\LockProviderNotFoundException;
use App\Internal\Exceptions\RecordNotFoundException;
use App\Models\Wallet;

interface BookkeeperServiceInterface
{
    public function missing(Wallet $wallet): bool;

    public function amount(Wallet $wallet): string;

    /**
     * @throws LockProviderNotFoundException
     * @throws RecordNotFoundException
     */
    public function sync(Wallet $wallet, float|int|string $value): bool;

    /**
     * @throws LockProviderNotFoundException
     * @throws RecordNotFoundException
     */
    public function increase(Wallet $wallet, float|int|string $value): string;
}
