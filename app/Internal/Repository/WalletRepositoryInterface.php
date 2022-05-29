<?php

namespace App\Internal\Repository;

use App\Internal\Exceptions\ModelNotFoundException;
use App\Models\Wallet;

interface WalletRepositoryInterface
{
    public function create(array $attributes): Wallet;

    public function findById(int $id): ?Wallet;

    public function findByUuid(string $uuid): ?Wallet;

    public function findBySlug(string $holderType, int|string $holderId, string $slug): ?Wallet;

    /**
     * @param array<int|string> $holderIds
     *
     * @return Wallet[]
     */
    public function findDefaultAll(string $holderType, array $holderIds): array;

    /**
     * @throws ModelNotFoundException
     */
    public function getById(int $id): Wallet;

    /**
     * @throws ModelNotFoundException
     */
    public function getByUuid(string $uuid): Wallet;

    /**
     * @throws ModelNotFoundException
     */
    public function getBySlug(string $holderType, int|string $holderId, string $slug): Wallet;
}
