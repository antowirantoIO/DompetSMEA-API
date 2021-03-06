<?php

namespace App\Services;

use App\Exceptions\AmountInvalid;
use App\External\Contracts\ExtraDtoInterface;
use App\Interfaces\Wallet;
use App\Internal\Assembler\ExtraDtoAssemblerInterface;
use App\Internal\Assembler\TransactionDtoAssemblerInterface;
use App\Internal\Assembler\TransferLazyDtoAssemblerInterface;
use App\Internal\Dto\TransactionDtoInterface;
use App\Internal\Dto\TransferLazyDtoInterface;
use App\Internal\Service\MathServiceInterface;
use App\Models\Transaction;

final class PrepareService implements PrepareServiceInterface
{
    public function __construct(
        private TransferLazyDtoAssemblerInterface $transferLazyDtoAssembler,
        private TransactionDtoAssemblerInterface $transactionDtoAssembler,
        private DiscountServiceInterface $personalDiscountService,
        private ConsistencyServiceInterface $consistencyService,
        private ExtraDtoAssemblerInterface $extraDtoAssembler,
        private CastServiceInterface $castService,
        private MathServiceInterface $mathService,
        private TaxServiceInterface $taxService
    ) {
    }

    /**
     * @throws AmountInvalid
     */
    public function deposit(
        Wallet $wallet,
        float|int|string $amount,
        ?array $meta,
        bool $confirmed = true
    ): TransactionDtoInterface {
        $this->consistencyService->checkPositive($amount);

        return $this->transactionDtoAssembler->create(
            $this->castService->getHolder($wallet),
            $this->castService->getWallet($wallet)
                ->getKey(),
            Transaction::TYPE_DEPOSIT,
            $amount,
            $confirmed,
            $meta
        );
    }

    /**
     * @throws AmountInvalid
     */
    public function withdraw(
        Wallet $wallet,
        float|int|string $amount,
        ?array $meta,
        bool $confirmed = true
    ): TransactionDtoInterface {
        $this->consistencyService->checkPositive($amount);

        return $this->transactionDtoAssembler->create(
            $this->castService->getHolder($wallet),
            $this->castService->getWallet($wallet)
                ->getKey(),
            Transaction::TYPE_WITHDRAW,
            $this->mathService->negative($amount),
            $confirmed,
            $meta
        );
    }

    /**
     * @throws AmountInvalid
     */
    public function transferLazy(
        Wallet $from,
        Wallet $to,
        string $status,
        float|int|string $amount,
        ExtraDtoInterface|array|null $meta = null
    ): TransferLazyDtoInterface {
        $discount = $this->personalDiscountService->getDiscount($from, $to);
        $toWallet = $this->castService->getWallet($to);
        $from = $this->castService->getWallet($from);
        $fee = $this->taxService->getFee($to, $amount);

        $amountWithoutDiscount = $this->mathService->sub($amount, $discount, $toWallet->decimal_places);
        $depositAmount = $this->mathService->compare($amountWithoutDiscount, 0) === -1 ? '0' : $amountWithoutDiscount;
        $withdrawAmount = $this->mathService->add($depositAmount, $fee, $from->decimal_places);
        $extra = $this->extraDtoAssembler->create($meta);
        $withdrawOption = $extra->getWithdrawOption();
        $depositOption = $extra->getDepositOption();

        return $this->transferLazyDtoAssembler->create(
            $from,
            $toWallet,
            $discount,
            $fee,
            $this->withdraw($from, $withdrawAmount, $withdrawOption->getMeta(), $withdrawOption->isConfirmed()),
            $this->deposit($toWallet, $depositAmount, $depositOption->getMeta(), $depositOption->isConfirmed()),
            $status
        );
    }
}
