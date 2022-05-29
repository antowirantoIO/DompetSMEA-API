<?php

namespace App\Providers;

/**
 * Class WalletConfigure.
 *
 * @codeCoverageIgnore
 */
final class WalletConfigure
{
    private static bool $runsMigrations = true;

    /**
     * Configure Wallet to not register its migrations.
     */
    public static function ignoreMigrations(): void
    {
        self::$runsMigrations = false;
    }

    /**
     * Indicates if Wallet migrations will be run.
     */
    public static function isRunsMigrations(): bool
    {
        return self::$runsMigrations;
    }
}
