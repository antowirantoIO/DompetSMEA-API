<?php

namespace App\Services;

use App\Internal\Dto\AvailabilityDtoInterface;

interface BasketServiceInterface
{
    public function availability(AvailabilityDtoInterface $availabilityDto): bool;
}
