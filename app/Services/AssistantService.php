<?php

namespace App\Services;

use App\Interfaces\ProductInterface;
use App\Internal\Dto\BasketDtoInterface;
use App\Internal\Dto\TransactionDtoInterface;
use App\Internal\Dto\TransferDtoInterface;
use App\Internal\Service\MathServiceInterface;

final class AssistantService implements AssistantServiceInterface
{
    public function __construct(
        private MathServiceInterface $mathService
    ) {
    }

    /**
     * @param non-empty-array<array-key, TransactionDtoInterface|TransferDtoInterface> $objects
     *
     * @return non-empty-array<array-key, string>
     */
    public function getUuids(array $objects): array
    {
        return array_map(static fn ($object): string => $object->getUuid(), $objects);
    }

    /**
     * @param non-empty-array<array-key, TransactionDtoInterface> $transactions
     *
     * @return array<int, string>
     */
    public function getSums(array $transactions): array
    {
        $amounts = [];
        foreach ($transactions as $transaction) {
            if ($transaction->isConfirmed()) {
                $amounts[$transaction->getWalletId()] = $this->mathService->add(
                    $amounts[$transaction->getWalletId()] ?? 0,
                    $transaction->getAmount()
                );
            }
        }

        return array_filter($amounts, fn (string $amount): bool => $this->mathService->compare($amount, 0) !== 0);
    }

    public function getMeta(BasketDtoInterface $basketDto, ProductInterface $product): ?array
    {
        $metaBasket = $basketDto->meta();
        $metaProduct = $product->getMetaProduct();

        if ($metaProduct !== null) {
            return array_merge($metaBasket, $metaProduct);
        }

        if ($metaBasket !== []) {
            return $metaBasket;
        }

        return null;
    }
}
