<?php

namespace App\Internal\Transform;

use App\Internal\Dto\TransactionDtoInterface;

interface TransactionDtoTransformerInterface
{
    public function extract(TransactionDtoInterface $dto): array;
}
