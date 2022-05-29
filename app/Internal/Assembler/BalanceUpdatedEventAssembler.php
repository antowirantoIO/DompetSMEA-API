<?php

namespace App\Internal\Assembler;

use App\Internal\Events\BalanceUpdatedEvent;
use App\Internal\Events\BalanceUpdatedEventInterface;
use App\Internal\Service\ClockServiceInterface;
use App\Models\Wallet;

final class BalanceUpdatedEventAssembler implements BalanceUpdatedEventAssemblerInterface
{
    public function __construct(
        private ClockServiceInterface $clockService
    ) {
    }

    public function create(Wallet $wallet): BalanceUpdatedEventInterface
    {
        return new BalanceUpdatedEvent(
            (int) $wallet->getKey(),
            $wallet->uuid,
            $wallet->getOriginalBalanceAttribute(),
            $this->clockService->now()
        );
    }
}
