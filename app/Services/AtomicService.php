<?php

namespace App\Services;

use App\Interfaces\Wallet;
use App\Internal\Exceptions\ExceptionInterface;
use App\Internal\Exceptions\LockProviderNotFoundException;
use App\Internal\Exceptions\TransactionFailedException;
use App\Internal\Service\DatabaseServiceInterface;
use App\Internal\Service\LockServiceInterface;
use Illuminate\Database\RecordsNotFoundException;

final class AtomicService implements AtomicServiceInterface
{
    private const PREFIX = 'wallet_atomic::';

    public function __construct(
        private DatabaseServiceInterface $databaseService,
        private LockServiceInterface $lockService,
        private CastServiceInterface $castService
    ) {
    }

    /**
     * @throws LockProviderNotFoundException
     * @throws RecordsNotFoundException
     * @throws TransactionFailedException
     * @throws ExceptionInterface
     */
    public function block(Wallet $object, callable $callback): mixed
    {
        return $this->lockService->block(
            $this->key($object),
            fn () => $this->databaseService->transaction($callback)
        );
    }

    private function key(Wallet $object): string
    {
        $wallet = $this->castService->getWallet($object);

        return self::PREFIX.'::'.$wallet::class.'::'.$wallet->uuid;
    }
}
