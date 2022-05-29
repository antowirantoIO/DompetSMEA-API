<?php

namespace App\Services;

use App\Exceptions\AmountInvalid;
use App\External\Contracts\ExtraDtoInterface;
use App\Interfaces\Wallet;
use App\Internal\Dto\TransactionDtoInterface;
use App\Internal\Dto\TransferLazyDtoInterface;

interface PrepareServiceInterface
{
    /**
     * @throws AmountInvalid
     */
    public function deposit(
        Wallet $wallet,
        float|int|string $amount,
        ?array $meta,
        bool $confirmed = true
    ): TransactionDtoInterface;

    /**
     * @throws AmountInvalid
     */
    public function withdraw(
        Wallet $wallet,
        float|int|string $amount,
        ?array $meta,
        bool $confirmed = true
    ): TransactionDtoInterface;

    /**
     * @throws AmountInvalid
     */
    public function transferLazy(
        Wallet $from,
        Wallet $to,
        string $status,
        float|int|string $amount,
        ExtraDtoInterface|array|null $meta = null
    ): TransferLazyDtoInterface;
}
