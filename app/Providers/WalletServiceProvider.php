<?php

namespace App\Providers;

use App\Console\Commands\TransferFixCommand;
use App\Internal\Assembler\AvailabilityDtoAssembler;
use App\Internal\Assembler\AvailabilityDtoAssemblerInterface;
use App\Internal\Assembler\BalanceUpdatedEventAssembler;
use App\Internal\Assembler\BalanceUpdatedEventAssemblerInterface;
use App\Internal\Assembler\ExtraDtoAssembler;
use App\Internal\Assembler\ExtraDtoAssemblerInterface;
use App\Internal\Assembler\OptionDtoAssembler;
use App\Internal\Assembler\OptionDtoAssemblerInterface;
use App\Internal\Assembler\TransactionDtoAssembler;
use App\Internal\Assembler\TransactionDtoAssemblerInterface;
use App\Internal\Assembler\TransactionQueryAssembler;
use App\Internal\Assembler\TransactionQueryAssemblerInterface;
use App\Internal\Assembler\TransferDtoAssembler;
use App\Internal\Assembler\TransferDtoAssemblerInterface;
use App\Internal\Assembler\TransferLazyDtoAssembler;
use App\Internal\Assembler\TransferLazyDtoAssemblerInterface;
use App\Internal\Assembler\TransferQueryAssembler;
use App\Internal\Assembler\TransferQueryAssemblerInterface;
use App\Internal\Assembler\WalletCreatedEventAssembler;
use App\Internal\Assembler\WalletCreatedEventAssemblerInterface;
use App\Internal\Events\BalanceUpdatedEvent;
use App\Internal\Events\BalanceUpdatedEventInterface;
use App\Internal\Events\WalletCreatedEvent;
use App\Internal\Events\WalletCreatedEventInterface;
use App\Internal\Repository\TransactionRepository;
use App\Internal\Repository\TransactionRepositoryInterface;
use App\Internal\Repository\TransferRepository;
use App\Internal\Repository\TransferRepositoryInterface;
use App\Internal\Repository\WalletRepository;
use App\Internal\Repository\WalletRepositoryInterface;
use App\Internal\Service\ClockService;
use App\Internal\Service\ClockServiceInterface;
use App\Internal\Service\DatabaseService;
use App\Internal\Service\DatabaseServiceInterface;
use App\Internal\Service\DispatcherService;
use App\Internal\Service\DispatcherServiceInterface;
use App\Internal\Service\JsonService;
use App\Internal\Service\JsonServiceInterface;
use App\Internal\Service\LockService;
use App\Internal\Service\LockServiceInterface;
use App\Internal\Service\MathService;
use App\Internal\Service\MathServiceInterface;
use App\Internal\Service\StorageService;
use App\Internal\Service\StorageServiceInterface;
use App\Internal\Service\TranslatorService;
use App\Internal\Service\TranslatorServiceInterface;
use App\Internal\Service\UuidFactoryService;
use App\Internal\Service\UuidFactoryServiceInterface;
use App\Internal\Transform\TransactionDtoTransformer;
use App\Internal\Transform\TransactionDtoTransformerInterface;
use App\Internal\Transform\TransferDtoTransformer;
use App\Internal\Transform\TransferDtoTransformerInterface;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\Wallet;
use App\Services\AssistantService;
use App\Services\AssistantServiceInterface;
use App\Services\AtmService;
use App\Services\AtmServiceInterface;
use App\Services\AtomicService;
use App\Services\AtomicServiceInterface;
use App\Services\BasketService;
use App\Services\BasketServiceInterface;
use App\Services\BookkeeperService;
use App\Services\BookkeeperServiceInterface;
use App\Services\CastService;
use App\Services\CastServiceInterface;
use App\Services\ConsistencyService;
use App\Services\ConsistencyServiceInterface;
use App\Services\DiscountService;
use App\Services\DiscountServiceInterface;
use App\Services\EagerLoaderService;
use App\Services\EagerLoaderServiceInterface;
use App\Services\ExchangeService;
use App\Services\ExchangeServiceInterface;
use App\Services\PrepareService;
use App\Services\PrepareServiceInterface;
use App\Services\PurchaseService;
use App\Services\PurchaseServiceInterface;
use App\Services\RegulatorService;
use App\Services\RegulatorServiceInterface;
use App\Services\TaxService;
use App\Services\TaxServiceInterface;
use App\Services\TransactionService;
use App\Services\TransactionServiceInterface;
use App\Services\TransferService;
use App\Services\TransferServiceInterface;
use App\Services\WalletService;
use App\Services\WalletServiceInterface;
use function config;
use function dirname;
use function function_exists;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\ServiceProvider;

final class WalletServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @codeCoverageIgnore
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->commands([TransferFixCommand::class]);

        $configure = config('wallet', []);

        $this->internal($configure['internal'] ?? []);
        $this->services($configure['services'] ?? [], $configure['cache'] ?? []);

        $this->repositories($configure['repositories'] ?? []);
        $this->transformers($configure['transformers'] ?? []);
        $this->assemblers($configure['assemblers'] ?? []);
        $this->events($configure['events'] ?? []);

        $this->bindObjects($configure);
    }

    private function repositories(array $configure): void
    {
        $this->app->singleton(
            TransactionRepositoryInterface::class,
            $configure['transaction'] ?? TransactionRepository::class
        );

        $this->app->singleton(
            TransferRepositoryInterface::class,
            $configure['transfer'] ?? TransferRepository::class
        );

        $this->app->singleton(WalletRepositoryInterface::class, $configure['wallet'] ?? WalletRepository::class);
    }

    private function internal(array $configure): void
    {
        $this->app->bind(StorageServiceInterface::class, $configure['storage'] ?? StorageService::class);

        $this->app->singleton(ClockServiceInterface::class, $configure['clock'] ?? ClockService::class);
        $this->app->singleton(DatabaseServiceInterface::class, $configure['database'] ?? DatabaseService::class);
        $this->app->singleton(DispatcherServiceInterface::class, $configure['dispatcher'] ?? DispatcherService::class);
        $this->app->singleton(JsonServiceInterface::class, $configure['json'] ?? JsonService::class);
        $this->app->singleton(LockServiceInterface::class, $configure['lock'] ?? LockService::class);
        $this->app->singleton(MathServiceInterface::class, $configure['math'] ?? MathService::class);
        $this->app->singleton(TranslatorServiceInterface::class, $configure['translator'] ?? TranslatorService::class);
        $this->app->singleton(UuidFactoryServiceInterface::class, $configure['uuid'] ?? UuidFactoryService::class);
    }

    private function services(array $configure, array $cache): void
    {
        $this->app->singleton(AssistantServiceInterface::class, $configure['assistant'] ?? AssistantService::class);
        $this->app->singleton(AtmServiceInterface::class, $configure['atm'] ?? AtmService::class);
        $this->app->singleton(AtomicServiceInterface::class, $configure['atomic'] ?? AtomicService::class);
        $this->app->singleton(BasketServiceInterface::class, $configure['basket'] ?? BasketService::class);
        $this->app->singleton(CastServiceInterface::class, $configure['cast'] ?? CastService::class);
        $this->app->singleton(
            ConsistencyServiceInterface::class,
            $configure['consistency'] ?? ConsistencyService::class
        );
        $this->app->singleton(DiscountServiceInterface::class, $configure['discount'] ?? DiscountService::class);
        $this->app->singleton(
            EagerLoaderServiceInterface::class,
            $configure['eager_loader'] ?? EagerLoaderService::class
        );
        $this->app->singleton(ExchangeServiceInterface::class, $configure['exchange'] ?? ExchangeService::class);
        $this->app->singleton(PrepareServiceInterface::class, $configure['prepare'] ?? PrepareService::class);
        $this->app->singleton(PurchaseServiceInterface::class, $configure['purchase'] ?? PurchaseService::class);
        $this->app->singleton(TaxServiceInterface::class, $configure['tax'] ?? TaxService::class);
        $this->app->singleton(
            TransactionServiceInterface::class,
            $configure['transaction'] ?? TransactionService::class
        );
        $this->app->singleton(TransferServiceInterface::class, $configure['transfer'] ?? TransferService::class);
        $this->app->singleton(WalletServiceInterface::class, $configure['wallet'] ?? WalletService::class);

        $this->app->singleton(BookkeeperServiceInterface::class, fn () => $this->app->make(
            $configure['bookkeeper'] ?? BookkeeperService::class,
            [
                'storageService' => $this->app->make(
                    StorageServiceInterface::class,
                    [
                        'cacheRepository' => $this->app->make(CacheManager::class)
                            ->driver($cache['driver'] ?? 'array'),
                    ],
                ),
            ]
        ));

        $this->app->singleton(RegulatorServiceInterface::class, fn () => $this->app->make(
            $configure['regulator'] ?? RegulatorService::class,
            [
                'storageService' => $this->app->make(
                    StorageServiceInterface::class,
                    [
                        'cacheRepository' => $this->app->make(CacheManager::class)
                            ->driver('array'),
                    ],
                ),
            ]
        ));
    }

    private function assemblers(array $configure): void
    {
        $this->app->singleton(
            AvailabilityDtoAssemblerInterface::class,
            $configure['availability'] ?? AvailabilityDtoAssembler::class
        );

        $this->app->singleton(
            BalanceUpdatedEventAssemblerInterface::class,
            $configure['balance_updated_event'] ?? BalanceUpdatedEventAssembler::class
        );

        $this->app->singleton(ExtraDtoAssemblerInterface::class, $configure['extra'] ?? ExtraDtoAssembler::class);

        $this->app->singleton(
            OptionDtoAssemblerInterface::class,
            $configure['option'] ?? OptionDtoAssembler::class
        );

        $this->app->singleton(
            TransactionDtoAssemblerInterface::class,
            $configure['transaction'] ?? TransactionDtoAssembler::class
        );

        $this->app->singleton(
            TransferLazyDtoAssemblerInterface::class,
            $configure['transfer_lazy'] ?? TransferLazyDtoAssembler::class
        );

        $this->app->singleton(
            TransferDtoAssemblerInterface::class,
            $configure['transfer'] ?? TransferDtoAssembler::class
        );

        $this->app->singleton(
            TransactionQueryAssemblerInterface::class,
            $configure['transaction_query'] ?? TransactionQueryAssembler::class
        );

        $this->app->singleton(
            TransferQueryAssemblerInterface::class,
            $configure['transfer_query'] ?? TransferQueryAssembler::class
        );

        $this->app->singleton(
            WalletCreatedEventAssemblerInterface::class,
            $configure['wallet_created_event'] ?? WalletCreatedEventAssembler::class
        );
    }

    private function transformers(array $configure): void
    {
        $this->app->singleton(
            TransactionDtoTransformerInterface::class,
            $configure['transaction'] ?? TransactionDtoTransformer::class
        );

        $this->app->singleton(
            TransferDtoTransformerInterface::class,
            $configure['transfer'] ?? TransferDtoTransformer::class
        );
    }

    private function events(array $configure): void
    {
        $this->app->bind(
            BalanceUpdatedEventInterface::class,
            $configure['balance_updated'] ?? BalanceUpdatedEvent::class
        );

        $this->app->bind(
            WalletCreatedEventInterface::class,
            $configure['wallet_created'] ?? WalletCreatedEvent::class
        );
    }

    private function bindObjects(array $configure): void
    {
        $this->app->bind(Transaction::class, $configure['transaction']['model'] ?? null);
        $this->app->bind(Transfer::class, $configure['transfer']['model'] ?? null);
        $this->app->bind(Wallet::class, $configure['wallet']['model'] ?? null);
    }
}
