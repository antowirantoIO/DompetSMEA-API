<?php

namespace App\Internal\Dto;

use App\Interfaces\Customer;

/** @psalm-immutable */
final class AvailabilityDto implements AvailabilityDtoInterface
{
    public function __construct(
        private Customer $customer,
        private BasketDtoInterface $basketDto,
        private bool $force
    ) {
    }

    public function getBasketDto(): BasketDtoInterface
    {
        return $this->basketDto;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function isForce(): bool
    {
        return $this->force;
    }
}
