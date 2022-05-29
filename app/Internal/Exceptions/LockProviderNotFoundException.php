<?php

namespace App\Internal\Exceptions;

use UnexpectedValueException;

final class LockProviderNotFoundException extends UnexpectedValueException implements UnexpectedValueExceptionInterface
{
}
