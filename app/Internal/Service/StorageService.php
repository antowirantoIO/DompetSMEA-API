<?php

namespace App\Internal\Service;

use App\Internal\Exceptions\ExceptionInterface;
use App\Internal\Exceptions\LockProviderNotFoundException;
use App\Internal\Exceptions\RecordNotFoundException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

final class StorageService implements StorageServiceInterface
{
    public function __construct(
        private LockServiceInterface $lockService,
        private MathServiceInterface $mathService,
        private CacheRepository $cacheRepository
    ) {
    }

    public function flush(): bool
    {
        return $this->cacheRepository->clear();
    }

    public function missing(string $key): bool
    {
        return $this->cacheRepository->forget($key);
    }

    /**
     * @throws RecordNotFoundException
     */
    public function get(string $key): string
    {
        $value = $this->cacheRepository->get($key);
        if ($value === null) {
            throw new RecordNotFoundException(
                'The repository did not find the object',
                ExceptionInterface::RECORD_NOT_FOUND
            );
        }

        return $this->mathService->round($value);
    }

    public function sync(string $key, float|int|string $value): bool
    {
        return $this->cacheRepository->set($key, $value);
    }

    /**
     * @throws LockProviderNotFoundException
     * @throws RecordNotFoundException
     */
    public function increase(string $key, float|int|string $value): string
    {
        return $this->lockService->block(
            $key.'::increase',
            function () use ($key, $value): string {
                $result = $this->mathService->add($this->get($key), $value);
                $this->sync($key, $result);

                return $this->mathService->round($result);
            }
        );
    }
}
