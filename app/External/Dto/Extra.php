<?php

namespace App\External\Dto;

use App\External\Contracts\ExtraDtoInterface;
use App\External\Contracts\OptionDtoInterface;

final class Extra implements ExtraDtoInterface
{
    private OptionDtoInterface $deposit;
    private OptionDtoInterface $withdraw;

    public function __construct(OptionDtoInterface|array|null $deposit, OptionDtoInterface|array|null $withdraw)
    {
        $this->deposit = $deposit instanceof OptionDtoInterface ? $deposit : new Option($deposit);
        $this->withdraw = $withdraw instanceof OptionDtoInterface ? $withdraw : new Option($withdraw);
    }

    public function getDepositOption(): OptionDtoInterface
    {
        return $this->deposit;
    }

    public function getWithdrawOption(): OptionDtoInterface
    {
        return $this->withdraw;
    }
}
