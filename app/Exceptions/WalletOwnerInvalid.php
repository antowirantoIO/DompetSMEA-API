<?php

namespace App\Exceptions;

use App\Internal\Exceptions\InvalidArgumentExceptionInterface;
use InvalidArgumentException;

final class WalletOwnerInvalid extends InvalidArgumentException implements InvalidArgumentExceptionInterface
{
}
