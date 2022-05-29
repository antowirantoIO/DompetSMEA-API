<?php

namespace App\Internal\Assembler;

use App\Internal\Dto\TransferDto;
use App\Internal\Dto\TransferDtoInterface;
use App\Internal\Service\UuidFactoryServiceInterface;
use Illuminate\Database\Eloquent\Model;

final class TransferDtoAssembler implements TransferDtoAssemblerInterface
{
    public function __construct(
        private UuidFactoryServiceInterface $uuidService
    ) {
    }

    public function create(
        int $depositId,
        int $withdrawId,
        string $status,
        Model $fromModel,
        Model $toModel,
        int $discount,
        string $fee
    ): TransferDtoInterface {
        return new TransferDto(
            $this->uuidService->uuid4(),
            $depositId,
            $withdrawId,
            $status,
            $fromModel->getMorphClass(),
            $fromModel->getKey(),
            $toModel->getMorphClass(),
            $toModel->getKey(),
            $discount,
            $fee
        );
    }
}
