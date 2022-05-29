<?php

namespace App\Services;

use App\Internal\Exceptions\ModelNotFoundException;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Model;

interface WalletServiceInterface
{
    public function create(Model $model, array $data): Wallet;

    public function findBySlug(Model $model, string $slug): ?Wallet;

    public function findByUuid(string $uuid): ?Wallet;

    public function findById(int $id): ?Wallet;

    /**
     * @throws ModelNotFoundException
     */
    public function getBySlug(Model $model, string $slug): Wallet;

    /**
     * @throws ModelNotFoundException
     */
    public function getByUuid(string $uuid): Wallet;

    /**
     * @throws ModelNotFoundException
     */
    public function getById(int $id): Wallet;
}
