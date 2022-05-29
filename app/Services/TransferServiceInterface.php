<?php

namespace App\Services;

use App\Internal\Dto\TransferLazyDtoInterface;
use App\Internal\Exceptions\ExceptionInterface;
use App\Internal\Exceptions\LockProviderNotFoundException;
use App\Internal\Exceptions\RecordNotFoundException;
use App\Internal\Exceptions\TransactionFailedException;
use App\Models\Transfer;
use Illuminate\Database\RecordsNotFoundException;

interface TransferServiceInterface
{
    /**
     * @param int[] $ids
     */
    public function updateStatusByIds(string $status, array $ids): bool;

    /**
     * @param non-empty-array<TransferLazyDtoInterface> $objects
     *
     * @throws LockProviderNotFoundException
     * @throws RecordNotFoundException
     * @throws RecordsNotFoundException
     * @throws TransactionFailedException
     * @throws ExceptionInterface
     *
     * @return non-empty-array<Transfer>
     */
    public function apply(array $objects): array;
}
