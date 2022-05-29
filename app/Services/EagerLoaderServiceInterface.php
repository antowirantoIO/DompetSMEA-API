<?php

namespace App\Services;

use App\Internal\Dto\BasketDtoInterface;

interface EagerLoaderServiceInterface
{
    public function loadWalletsByBasket(BasketDtoInterface $basketDto): void;
}
