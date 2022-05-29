<?php

namespace App\Internal\Service;

use App\Internal\Exceptions\LockProviderNotFoundException;
use App\Internal\Exceptions\RecordNotFoundException;

interface StorageServiceInterface
{
    public function flush(): bool;

    public function missing(string $key): bool;

    /**
     * @throws RecordNotFoundException
     */
    public function get(string $key): string;

    public function sync(string $key, float|int|string $value): bool;

    /**
     * @throws LockProviderNotFoundException
     * @throws RecordNotFoundException
     */
    public function increase(string $key, float|int|string $value): string;
}
