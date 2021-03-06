<?php

namespace App\Internal\Dto;

use App\Interfaces\ProductInterface;
use Countable;
use Generator;

interface BasketDtoInterface extends Countable
{
    public function total(): int;

    public function meta(): array;

    /**
     * @return non-empty-array<int|string, ItemDtoInterface>
     */
    public function items(): array;

    /**
     * @return Generator<array-key, ProductInterface, mixed, void>
     */
    public function cursor(): Generator;
}
