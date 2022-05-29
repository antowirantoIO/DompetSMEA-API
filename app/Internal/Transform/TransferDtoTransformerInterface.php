<?php

namespace App\Internal\Transform;

use App\Internal\Dto\TransferDtoInterface;

interface TransferDtoTransformerInterface
{
    public function extract(TransferDtoInterface $dto): array;
}
