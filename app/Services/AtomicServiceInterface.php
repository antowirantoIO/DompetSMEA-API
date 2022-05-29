<?php

namespace App\Services;

use App\Interfaces\Wallet;
use App\Internal\Exceptions\ExceptionInterface;
use App\Internal\Exceptions\LockProviderNotFoundException;
use App\Internal\Exceptions\TransactionFailedException;
use Illuminate\Database\RecordsNotFoundException;

interface AtomicServiceInterface
{
    /**
     * @throws LockProviderNotFoundException
     * @throws RecordsNotFoundException
     * @throws TransactionFailedException
     * @throws ExceptionInterface
     */
    public function block(Wallet $object, callable $callback): mixed;
}
