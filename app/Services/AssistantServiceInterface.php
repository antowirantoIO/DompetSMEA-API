<?php

namespace App\Services;

use App\Interfaces\ProductInterface;
use App\Internal\Dto\BasketDtoInterface;
use App\Internal\Dto\TransactionDtoInterface;
use App\Internal\Dto\TransferDtoInterface;

interface AssistantServiceInterface
{
    /**
     * @param non-empty-array<array-key, TransactionDtoInterface|TransferDtoInterface> $objects
     *
     * @return non-empty-array<array-key, string>
     */
    public function getUuids(array $objects): array;

    /**
     * @param non-empty-array<TransactionDtoInterface> $transactions
     *
     * @return array<int, string>
     */
    public function getSums(array $transactions): array;

    public function getMeta(BasketDtoInterface $basketDto, ProductInterface $product): ?array;
}
