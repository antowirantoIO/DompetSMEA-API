<?php

namespace App\Services;

use App\Internal\Assembler\TransferDtoAssemblerInterface;
use App\Internal\Dto\TransferLazyDtoInterface;
use App\Internal\Exceptions\ExceptionInterface;
use App\Internal\Exceptions\LockProviderNotFoundException;
use App\Internal\Exceptions\RecordNotFoundException;
use App\Internal\Exceptions\TransactionFailedException;
use App\Internal\Repository\TransferRepositoryInterface;
use App\Internal\Service\DatabaseServiceInterface;
use App\Models\Transfer;
use Illuminate\Database\RecordsNotFoundException;

final class TransferService implements TransferServiceInterface
{
    public function __construct(
        private TransferDtoAssemblerInterface $transferDtoAssembler,
        private TransferRepositoryInterface $transferRepository,
        private TransactionServiceInterface $transactionService,
        private DatabaseServiceInterface $databaseService,
        private CastServiceInterface $castService,
        private AtmServiceInterface $atmService,
    ) {
    }

    /**
     * @param int[] $ids
     */
    public function updateStatusByIds(string $status, array $ids): bool
    {
        return $ids !== [] && count($ids) === $this->transferRepository->updateStatusByIds($status, $ids);
    }

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
    public function apply(array $objects): array
    {
        return $this->databaseService->transaction(function () use ($objects): array {
            $wallets = [];
            $operations = [];
            foreach ($objects as $object) {
                $fromWallet = $this->castService->getWallet($object->getFromWallet());
                $wallets[$fromWallet->getKey()] = $fromWallet;

                $toWallet = $this->castService->getWallet($object->getToWallet());
                $wallets[$toWallet->getKey()] = $toWallet;

                $operations[] = $object->getWithdrawDto();
                $operations[] = $object->getDepositDto();
            }

            $transactions = $this->transactionService->apply($wallets, $operations);

            $links = [];
            $transfers = [];
            foreach ($objects as $object) {
                $withdraw = $transactions[$object->getWithdrawDto()->getUuid()] ?? null;
                assert($withdraw !== null);

                $deposit = $transactions[$object->getDepositDto()->getUuid()] ?? null;
                assert($deposit !== null);

                $fromWallet = $this->castService->getWallet($object->getFromWallet());
                $toWallet = $this->castService->getWallet($object->getToWallet());

                $transfer = $this->transferDtoAssembler->create(
                    $deposit->getKey(),
                    $withdraw->getKey(),
                    $object->getStatus(),
                    $fromWallet,
                    $toWallet,
                    $object->getDiscount(),
                    $object->getFee()
                );

                $transfers[] = $transfer;
                $links[$transfer->getUuid()] = [
                    'deposit' => $deposit,
                    'withdraw' => $withdraw,
                    'from' => $fromWallet->withoutRelations(),
                    'to' => $toWallet->withoutRelations(),
                ];
            }

            $models = $this->atmService->makeTransfers($transfers);
            foreach ($models as $model) {
                $model->setRelations($links[$model->uuid] ?? []);
            }

            return $models;
        });
    }
}
