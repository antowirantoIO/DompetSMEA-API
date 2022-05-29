<?php

namespace App\Internal\Exceptions;

use LogicException;

final class TransactionStartException extends LogicException implements LogicExceptionInterface
{
}
