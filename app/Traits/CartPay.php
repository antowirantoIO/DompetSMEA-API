<?php

namespace App\Traits;

use App\Exceptions\BalanceIsEmpty;
use App\Exceptions\InsufficientFunds;
use App\Exceptions\ProductEnded;
use App\Interfaces\CartInterface;
use App\Interfaces\ProductInterface;
use App\Internal\Assembler\AvailabilityDtoAssemblerInterface;
use App\Internal\Exceptions\ExceptionInterface;
use App\Internal\Exceptions\LockProviderNotFoundException;
use App\Internal\Exceptions\ModelNotFoundException;
use App\Internal\Exceptions\RecordNotFoundException;
use App\Internal\Exceptions\TransactionFailedException;
use App\Internal\Service\TranslatorServiceInterface;
use App\Models\Transfer;
use App\Objects\Cart;
use App\Services\AssistantServiceInterface;
use App\Services\AtomicServiceInterface;
use App\Services\BasketServiceInterface;
use App\Services\CastServiceInterface;
use App\Services\ConsistencyServiceInterface;
use App\Services\EagerLoaderServiceInterface;
use App\Services\PrepareServiceInterface;
use App\Services\PurchaseServiceInterface;
use App\Services\TransferServiceInterface;
use function count;
use Illuminate\Database\RecordsNotFoundException;

/**
 * @psalm-require-extends \Illuminate\Database\Eloquent\Model
 * @psalm-require-implements \App\Interfaces\Customer
 */
trait CartPay
{
    use HasWallet;

    /**
     * @throws ProductEnded
     * @throws BalanceIsEmpty
     * @throws InsufficientFunds
     * @throws LockProviderNotFoundException
     * @throws RecordNotFoundException
     * @throws RecordsNotFoundException
     * @throws TransactionFailedException
     * @throws ExceptionInterface
     *
     * @return non-empty-array<Transfer>
     */
    public function payFreeCart(CartInterface $cart): array
    {
        return app(AtomicServiceInterface::class)->block($this, function () use ($cart) {
            $basketDto = $cart->getBasketDto();
            $basketService = app(BasketServiceInterface::class);
            $availabilityAssembler = app(AvailabilityDtoAssemblerInterface::class);
            app(EagerLoaderServiceInterface::class)->loadWalletsByBasket($basketDto);
            if (!$basketService->availability($availabilityAssembler->create($this, $basketDto, false))) {
                throw new ProductEnded(
                    app(TranslatorServiceInterface::class)->get('wallet::errors.product_stock'),
                    ExceptionInterface::PRODUCT_ENDED
                );
            }

            app(ConsistencyServiceInterface::class)->checkPotential($this, 0, true);

            $transfers = [];
            $prepareService = app(PrepareServiceInterface::class);
            $assistantService = app(AssistantServiceInterface::class);
            foreach ($basketDto->cursor() as $product) {
                $transfers[] = $prepareService->transferLazy(
                    $this,
                    $product,
                    Transfer::STATUS_PAID,
                    0,
                    $assistantService->getMeta($basketDto, $product)
                );
            }

            return app(TransferServiceInterface::class)->apply($transfers);
        });
    }

    /**
     * @return Transfer[]
     */
    public function safePayCart(CartInterface $cart, bool $force = false): array
    {
        try {
            return $this->payCart($cart, $force);
        } catch (ExceptionInterface) {
            return [];
        }
    }

    /**
     * @throws ProductEnded
     * @throws BalanceIsEmpty
     * @throws InsufficientFunds
     * @throws LockProviderNotFoundException
     * @throws RecordNotFoundException
     * @throws RecordsNotFoundException
     * @throws TransactionFailedException
     * @throws ExceptionInterface
     *
     * @return non-empty-array<Transfer>
     */
    public function payCart(CartInterface $cart, bool $force = false): array
    {
        return app(AtomicServiceInterface::class)->block($this, function () use ($cart, $force) {
            $basketDto = $cart->getBasketDto();
            $basketService = app(BasketServiceInterface::class);
            $availabilityAssembler = app(AvailabilityDtoAssemblerInterface::class);
            app(EagerLoaderServiceInterface::class)->loadWalletsByBasket($basketDto);
            if (!$basketService->availability($availabilityAssembler->create($this, $basketDto, $force))) {
                throw new ProductEnded(
                    app(TranslatorServiceInterface::class)->get('wallet::errors.product_stock'),
                    ExceptionInterface::PRODUCT_ENDED
                );
            }

            $prices = [];
            $transfers = [];
            $castService = app(CastServiceInterface::class);
            $prepareService = app(PrepareServiceInterface::class);
            $assistantService = app(AssistantServiceInterface::class);
            foreach ($cart->getBasketDto()->items() as $item) {
                foreach ($item->getItems() as $product) {
                    $productId = $product::class.':'.$castService->getModel($product)->getKey();
                    $pricePerItem = $item->getPricePerItem();
                    if ($pricePerItem === null) {
                        $prices[$productId] ??= $product->getAmountProduct($this);
                        $pricePerItem = $prices[$productId];
                    }

                    $transfers[] = $prepareService->transferLazy(
                        $this,
                        $product,
                        Transfer::STATUS_PAID,
                        $pricePerItem,
                        $assistantService->getMeta($basketDto, $product)
                    );
                }
            }

            if (!$force) {
                app(ConsistencyServiceInterface::class)->checkTransfer($transfers);
            }

            return app(TransferServiceInterface::class)->apply($transfers);
        });
    }

    /**
     * @throws ProductEnded
     * @throws LockProviderNotFoundException
     * @throws RecordNotFoundException
     * @throws RecordsNotFoundException
     * @throws TransactionFailedException
     * @throws ExceptionInterface
     *
     * @return non-empty-array<Transfer>
     */
    public function forcePayCart(CartInterface $cart): array
    {
        return $this->payCart($cart, true);
    }

    public function safeRefundCart(CartInterface $cart, bool $force = false, bool $gifts = false): bool
    {
        try {
            return $this->refundCart($cart, $force, $gifts);
        } catch (ExceptionInterface) {
            return false;
        }
    }

    /**
     * @throws BalanceIsEmpty
     * @throws InsufficientFunds
     * @throws LockProviderNotFoundException
     * @throws RecordNotFoundException
     * @throws RecordsNotFoundException
     * @throws TransactionFailedException
     * @throws ModelNotFoundException
     * @throws ExceptionInterface
     */
    public function refundCart(CartInterface $cart, bool $force = false, bool $gifts = false): bool
    {
        return app(AtomicServiceInterface::class)->block($this, function () use ($cart, $force, $gifts) {
            $basketDto = $cart->getBasketDto();
            app(EagerLoaderServiceInterface::class)->loadWalletsByBasket($basketDto);
            $transfers = app(PurchaseServiceInterface::class)->already($this, $basketDto, $gifts);
            if (count($transfers) !== $basketDto->total()) {
                throw new ModelNotFoundException(
                    "No query results for model [{$this->transfers()
                        ->getMorphClass()}]",
                    ExceptionInterface::MODEL_NOT_FOUND
                );
            }

            $index = 0;
            $objects = [];
            $transferIds = [];
            $transfers = array_values($transfers);
            $prepareService = app(PrepareServiceInterface::class);
            $assistantService = app(AssistantServiceInterface::class);
            foreach ($basketDto->cursor() as $product) {
                $transferIds[] = $transfers[$index]->getKey();
                $objects[] = $prepareService->transferLazy(
                    $product,
                    $transfers[$index]->withdraw->wallet,
                    Transfer::STATUS_TRANSFER,
                    $transfers[$index]->deposit->amount,
                    $assistantService->getMeta($basketDto, $product)
                );

                ++$index;
            }

            if (!$force) {
                app(ConsistencyServiceInterface::class)->checkTransfer($objects);
            }

            $transferService = app(TransferServiceInterface::class);

            $transferService->apply($objects);

            return $transferService
                ->updateStatusByIds(Transfer::STATUS_REFUND, $transferIds)
            ;
        });
    }

    /**
     * @throws LockProviderNotFoundException
     * @throws RecordNotFoundException
     * @throws RecordsNotFoundException
     * @throws TransactionFailedException
     * @throws ModelNotFoundException
     * @throws ExceptionInterface
     */
    public function forceRefundCart(CartInterface $cart, bool $gifts = false): bool
    {
        return $this->refundCart($cart, true, $gifts);
    }

    public function safeRefundGiftCart(CartInterface $cart, bool $force = false): bool
    {
        try {
            return $this->refundGiftCart($cart, $force);
        } catch (ExceptionInterface) {
            return false;
        }
    }

    /**
     * @throws BalanceIsEmpty
     * @throws InsufficientFunds
     * @throws LockProviderNotFoundException
     * @throws RecordNotFoundException
     * @throws RecordsNotFoundException
     * @throws TransactionFailedException
     * @throws ModelNotFoundException
     * @throws ExceptionInterface
     */
    public function refundGiftCart(CartInterface $cart, bool $force = false): bool
    {
        return $this->refundCart($cart, $force, true);
    }

    /**
     * @throws LockProviderNotFoundException
     * @throws RecordNotFoundException
     * @throws RecordsNotFoundException
     * @throws TransactionFailedException
     * @throws ModelNotFoundException
     * @throws ExceptionInterface
     */
    public function forceRefundGiftCart(CartInterface $cart): bool
    {
        return $this->refundGiftCart($cart, true);
    }

    /**
     * Checks acquired product your wallet.
     *
     * @deprecated The method is slow and will be removed in the future
     * @see PurchaseServiceInterface
     */
    public function paid(ProductInterface $product, bool $gifts = false): ?Transfer
    {
        $cart = app(Cart::class)->withItem($product);
        $purchases = app(PurchaseServiceInterface::class)
            ->already($this, $cart->getBasketDto(), $gifts)
        ;

        return current($purchases) ?: null;
    }
}
