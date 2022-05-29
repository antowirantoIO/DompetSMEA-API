<?php

namespace App\Internal\Service;

interface JsonServiceInterface
{
    public function encode(?array $data): ?string;
}
