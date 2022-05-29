<?php

namespace App\Internal\Dto;

use App\Interfaces\ProductInterface;
use Countable;

interface ItemDtoInterface extends Countable
{
    /**
     * @return ProductInterface[]
     */
    public function getItems(): array;

    public function getPricePerItem(): int|string|null;

    public function getProduct(): ProductInterface;
}
