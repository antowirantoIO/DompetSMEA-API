<?php

namespace App\Internal\Service;

use App\Internal\Exceptions\LockProviderNotFoundException;

interface LockServiceInterface
{
    /**
     * @throws LockProviderNotFoundException
     */
    public function block(string $key, callable $callback): mixed;
}
