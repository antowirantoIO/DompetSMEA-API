<?php

namespace App\Internal\Assembler;

use App\Internal\Dto\TransactionDtoInterface;
use Illuminate\Database\Eloquent\Model;

interface TransactionDtoAssemblerInterface
{
    public function create(
        Model $payable,
        int $walletId,
        string $type,
        float|int|string $amount,
        bool $confirmed,
        ?array $meta
    ): TransactionDtoInterface;
}
