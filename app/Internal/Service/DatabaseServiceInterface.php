<?php

namespace App\Internal\Service;

use App\Internal\Exceptions\ExceptionInterface;
use App\Internal\Exceptions\TransactionFailedException;
use App\Internal\Exceptions\TransactionStartException;
use Illuminate\Database\RecordsNotFoundException;

interface DatabaseServiceInterface
{
    /**
     * @throws RecordsNotFoundException
     * @throws TransactionStartException
     * @throws TransactionFailedException
     * @throws ExceptionInterface
     */
    public function transaction(callable $callback): mixed;
}
