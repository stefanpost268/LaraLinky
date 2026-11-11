<?php

declare(strict_types=1);

namespace LaraLinky;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

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
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([Console\Commands\FetchDataCommand::class]);
            $this->publishes([
                __DIR__.'/../config/laralinky.php' => config_path('laralinky.php'),
            ], 'laralinky-config');
        }
    }
}
