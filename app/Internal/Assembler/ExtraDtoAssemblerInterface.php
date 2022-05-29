<?php

namespace App\Internal\Assembler;

use App\External\Contracts\ExtraDtoInterface;

interface ExtraDtoAssemblerInterface
{
    public function create(ExtraDtoInterface|array|null $data): ExtraDtoInterface;
}
