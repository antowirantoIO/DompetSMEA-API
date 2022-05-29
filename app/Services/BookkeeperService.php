<?php

namespace App\Services;

use App\Internal\Exceptions\LockProviderNotFoundException;
use App\Internal\Exceptions\RecordNotFoundException;
use App\Internal\Service\LockServiceInterface;
use App\Internal\Service\StorageServiceInterface;
use App\Models\Wallet;

final class BookkeeperService implements BookkeeperServiceInterface
{
    public function __construct(
        private StorageServiceInterface $storageService,
        private LockServiceInterface $lockService
    ) {
    }

    public function missing(Wallet $wallet): bool
    {
        return $this->storageService->missing($this->getKey($wallet));
    }

    /**
     * @throws LockProviderNotFoundException
     * @throws RecordNotFoundException
     */
    public function amount(Wallet $wallet): string
    {
        try {
            return $this->storageService->get($this->getKey($wallet));
        } catch (RecordNotFoundException) {
            $this->lockService->block(
                $this->getKey($wallet),
                fn () => $this->sync($wallet, $wallet->getOriginalBalanceAttribute()),
            );
        }

        return $this->storageService->get($this->getKey($wallet));
    }

    public function sync(Wallet $wallet, float|int|string $value): bool
    {
        return $this->storageService->sync($this->getKey($wallet), $value);
    }

    /**
     * @throws LockProviderNotFoundException
     * @throws RecordNotFoundException
     */
    public function increase(Wallet $wallet, float|int|string $value): string
    {
        try {
            return $this->storageService->increase($this->getKey($wallet), $value);
        } catch (RecordNotFoundException) {
            $this->amount($wallet);
        }

        return $this->storageService->increase($this->getKey($wallet), $value);
    }

    private function getKey(Wallet $wallet): string
    {
        return __CLASS__.'::'.$wallet->uuid;
    }
}
