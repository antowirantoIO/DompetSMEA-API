<?php

namespace App\Services;

use App\Internal\Assembler\BalanceUpdatedEventAssemblerInterface;
use App\Internal\Exceptions\RecordNotFoundException;
use App\Internal\Service\DispatcherServiceInterface;
use App\Internal\Service\MathServiceInterface;
use App\Internal\Service\StorageServiceInterface;
use App\Internal\Service\UuidFactoryServiceInterface;
use App\Models\Wallet;

final class RegulatorService implements RegulatorServiceInterface
{
    private string $idempotentKey;

    /**
     * @var Wallet[]
     */
    private array $wallets = [];

    public function __construct(
        private BalanceUpdatedEventAssemblerInterface $balanceUpdatedEventAssembler,
        UuidFactoryServiceInterface $uuidFactoryService,
        private BookkeeperServiceInterface $bookkeeperService,
        private DispatcherServiceInterface $dispatcherService,
        private StorageServiceInterface $storageService,
        private MathServiceInterface $mathService
    ) {
        $this->idempotentKey = $uuidFactoryService->uuid4();
    }

    public function missing(Wallet $wallet): bool
    {
        unset($this->wallets[$wallet->uuid]);

        return $this->storageService->missing($this->getKey($wallet->uuid));
    }

    public function diff(Wallet $wallet): string
    {
        try {
            return $this->mathService->round($this->storageService->get($this->getKey($wallet->uuid)));
        } catch (RecordNotFoundException) {
            return '0';
        }
    }

    public function amount(Wallet $wallet): string
    {
        return $this->mathService->round(
            $this->mathService->add($this->bookkeeperService->amount($wallet), $this->diff($wallet))
        );
    }

    public function sync(Wallet $wallet, float|int|string $value): bool
    {
        $this->persist($wallet);

        return $this->storageService->sync(
            $this->getKey($wallet->uuid),
            $this->mathService->round(
                $this->mathService->negative($this->mathService->sub($this->amount($wallet), $value))
            )
        );
    }

    public function increase(Wallet $wallet, float|int|string $value): string
    {
        $this->persist($wallet);

        try {
            $this->storageService->increase($this->getKey($wallet->uuid), $value);
        } catch (RecordNotFoundException) {
            $value = $this->mathService->round($value);
            $this->storageService->sync($this->getKey($wallet->uuid), $value);
        }

        return $this->amount($wallet);
    }

    public function decrease(Wallet $wallet, float|int|string $value): string
    {
        return $this->increase($wallet, $this->mathService->negative($value));
    }

    public function approve(): void
    {
        foreach ($this->wallets as $wallet) {
            $diffValue = $this->diff($wallet);
            if ($this->mathService->compare($diffValue, 0) === 0) {
                continue;
            }

            $balance = $this->bookkeeperService->increase($wallet, $diffValue);
            $wallet->newQuery()
                ->whereKey($wallet->getKey())
                ->update([
                    'balance' => $balance,
                ]) // ?qN
            ;
            $wallet->fill([
                'balance' => $balance,
            ])->syncOriginalAttribute('balance');

            $event = $this->balanceUpdatedEventAssembler->create($wallet);
            $this->dispatcherService->dispatch($event);
        }

        $this->dispatcherService->flush();
        $this->purge();
    }

    public function purge(): void
    {
        foreach ($this->wallets as $wallet) {
            $this->missing($wallet);
        }

        $this->dispatcherService->forgot();
    }

    private function persist(Wallet $wallet): void
    {
        $this->wallets[$wallet->uuid] = $wallet;
    }

    private function getKey(string $uuid): string
    {
        return $this->idempotentKey.'::'.$uuid;
    }
}
