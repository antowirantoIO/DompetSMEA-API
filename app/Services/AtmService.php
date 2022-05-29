<?php

namespace App\Services;

use App\Internal\Assembler\TransactionQueryAssemblerInterface;
use App\Internal\Assembler\TransferQueryAssemblerInterface;
use App\Internal\Dto\TransactionDtoInterface;
use App\Internal\Dto\TransferDtoInterface;
use App\Internal\Repository\TransactionRepositoryInterface;
use App\Internal\Repository\TransferRepositoryInterface;
use App\Models\Transaction;
use App\Models\Transfer;

/** @internal */
final class AtmService implements AtmServiceInterface
{
    public function __construct(
        private TransactionQueryAssemblerInterface $transactionQueryAssembler,
        private TransferQueryAssemblerInterface $transferQueryAssembler,
        private TransactionRepositoryInterface $transactionRepository,
        private TransferRepositoryInterface $transferRepository,
        private AssistantServiceInterface $assistantService
    ) {
    }

    /**
     * @param non-empty-array<array-key, TransactionDtoInterface> $objects
     *
     * @return non-empty-array<string, Transaction>
     */
    public function makeTransactions(array $objects): array
    {
        if (count($objects) === 1) {
            $items = [$this->transactionRepository->insertOne(reset($objects))];
        } else {
            $this->transactionRepository->insert($objects);
            $uuids = $this->assistantService->getUuids($objects);
            $query = $this->transactionQueryAssembler->create($uuids);
            $items = $this->transactionRepository->findBy($query);
        }

        assert($items !== []);

        $results = [];
        foreach ($items as $item) {
            $results[$item->uuid] = $item;
        }

        return $results;
    }

    /**
     * @param non-empty-array<array-key, TransferDtoInterface> $objects
     *
     * @return non-empty-array<string, Transfer>
     */
    public function makeTransfers(array $objects): array
    {
        if (count($objects) === 1) {
            $items = [$this->transferRepository->insertOne(reset($objects))];
        } else {
            $this->transferRepository->insert($objects);
            $uuids = $this->assistantService->getUuids($objects);
            $query = $this->transferQueryAssembler->create($uuids);
            $items = $this->transferRepository->findBy($query);
        }

        assert($items !== []);

        $results = [];
        foreach ($items as $item) {
            $results[$item->uuid] = $item;
        }

        return $results;
    }
}
