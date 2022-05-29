<?php

namespace App\Objects;

use App\Interfaces\CartInterface;
use App\Interfaces\Customer;
use App\Interfaces\ProductInterface;
use App\Internal\Dto\BasketDto;
use App\Internal\Dto\BasketDtoInterface;
use App\Internal\Dto\ItemDto;
use App\Internal\Dto\ItemDtoInterface;
use App\Internal\Exceptions\CartEmptyException;
use App\Internal\Exceptions\ExceptionInterface;
use App\Internal\Service\MathServiceInterface;
use App\Services\CastServiceInterface;
use function count;
use Countable;

final class Cart implements Countable, CartInterface
{
    /**
     * @var array<string, ItemDtoInterface[]>
     */
    private array $items = [];

    private array $meta = [];

    public function __construct(
        private CastServiceInterface $castService,
        private MathServiceInterface $math
    ) {
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function withMeta(array $meta): self
    {
        $self = clone $this;
        $self->meta = $meta;

        return $self;
    }

    /**
     * @param positive-int $quantity
     */
    public function withItem(ProductInterface $product, int $quantity = 1, int|string|null $pricePerItem = null): self
    {
        $self = clone $this;

        $productId = $self->productId($product);

        $self->items[$productId] ??= [];
        $self->items[$productId][] = new ItemDto($product, $quantity, $pricePerItem);

        return $self;
    }

    public function withItems(iterable $products): self
    {
        $self = clone $this;
        foreach ($products as $product) {
            $self = $self->withItem($product);
        }

        return $self;
    }

    /**
     * @return ProductInterface[]
     */
    public function getItems(): array
    {
        $items = [];
        foreach ($this->items as $item) {
            foreach ($item as $datum) {
                $items[] = $datum->getItems();
            }
        }

        return array_merge(...$items);
    }

    public function getTotal(Customer $customer): string
    {
        $result = 0;
        $prices = [];
        foreach ($this->items as $productId => $_items) {
            foreach ($_items as $item) {
                $product = $item->getProduct();
                $pricePerItem = $item->getPricePerItem();
                if ($pricePerItem === null) {
                    $prices[$productId] ??= $product->getAmountProduct($customer);
                    $pricePerItem = $prices[$productId];
                }

                $price = $this->math->mul(count($item), $pricePerItem);
                $result = $this->math->add($result, $price);
            }
        }

        return (string) $result;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getQuantity(ProductInterface $product): int
    {
        $quantity = 0;
        $items = $this->items[$this->productId($product)] ?? [];
        foreach ($items as $item) {
            $quantity += $item->count();
        }

        return $quantity;
    }

    /**
     * @throws CartEmptyException
     */
    public function getBasketDto(): BasketDtoInterface
    {
        $items = array_merge(...array_values($this->items));

        if ($items === []) {
            throw new CartEmptyException('Cart is empty', ExceptionInterface::CART_EMPTY);
        }

        return new BasketDto($items, $this->getMeta());
    }

    private function productId(ProductInterface $product): string
    {
        return $product::class.':'.$this->castService->getModel($product)->getKey();
    }
}
