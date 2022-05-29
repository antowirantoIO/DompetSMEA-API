<?php

namespace App\Internal\Assembler;

use App\External\Contracts\OptionDtoInterface;

interface OptionDtoAssemblerInterface
{
    public function create(array|null $data): OptionDtoInterface;
}
