<?php

namespace App\External\Contracts;

interface OptionDtoInterface
{
    public function getMeta(): ?array;

    public function isConfirmed(): bool;
}
