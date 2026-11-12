<?php

declare(strict_types=1);

namespace LaraLinky;

use Illuminate\Config\Repository;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\ServiceProvider;
use LaraLinky\Repositories\DailyConsumptionRepository;
use LaraLinky\Repositories\HourlyConsumptionRepository;
use LaraLinky\Repositories\MeterRepository;
use LaraLinky\Storage\DatabaseStorage;
use LaraLinky\Sync\SyncService;

final class LinkyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laralinky.php', 'laralinky');
        $this->app->singleton(Linky::class, fn (Application $app) => new Linky(
            $app->make(Repository::class)->string('laralinky.token'),
            $app->make(Repository::class)->string('laralinky.prm'),
            $app->make(Repository::class)->string('laralinky.base_url'),
            $app->make(Repository::class)->float('laralinky.timeout'),
            $app->make(Repository::class)->string('laralinky.user_agent'),
        ));
        $this->app->alias(Linky::class, 'laralinky');
        $this->app->singleton(MeterRepository::class, fn (Application $app) => new MeterRepository(
            $this->databaseConnection($app->make(Repository::class)),
            $app->make(Repository::class)->string('laralinky.storage.database.table_prefix'),
        ));
        $this->app->singleton(DailyConsumptionRepository::class, fn (Application $app) => new DailyConsumptionRepository(
            $this->databaseConnection($app->make(Repository::class)),
            $app->make(Repository::class)->string('laralinky.storage.database.table_prefix'),
        ));
        $this->app->singleton(HourlyConsumptionRepository::class, fn (Application $app) => new HourlyConsumptionRepository(
            $this->databaseConnection($app->make(Repository::class)),
            $app->make(Repository::class)->string('laralinky.storage.database.table_prefix'),
        ));
        $this->app->singleton(DatabaseStorage::class, fn (Application $app) => new DatabaseStorage(
            $app->make(DatabaseManager::class),
            $this->databaseConnection($app->make(Repository::class)),
            $app->make(Repository::class)->string('laralinky.storage.database.table_prefix'),
            $app->make(MeterRepository::class),
            $app->make(DailyConsumptionRepository::class),
            $app->make(HourlyConsumptionRepository::class),
        ));
        $this->app->singleton(SyncService::class);
        $this->app->singleton(DataManager::class);
    }

    private function databaseConnection(Repository $config): ?string
    {
        $connection = $config->get('laralinky.storage.database.connection');

        return is_string($connection) ? $connection : null;
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([Console\Commands\SyncDataCommand::class]);
            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'laralinky-migrations');
            $this->publishes([
                __DIR__.'/../config/laralinky.php' => config_path('laralinky.php'),
            ], 'laralinky-config');
        }

        $this->callAfterResolving(Schedule::class, static function (Schedule $schedule): void {
            $schedule->command('linky:sync')->hourly()->withoutOverlapping();
        });
    }
}
