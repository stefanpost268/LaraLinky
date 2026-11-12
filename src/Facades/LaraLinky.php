<?php

declare(strict_types=1);

namespace LaraLinky\Facades;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Facade;
use LaraLinky\DataManager;
use LaraLinky\Models\DailyConsumption;
use LaraLinky\Models\HourlyConsumption;
use LaraLinky\Models\Meter;

/**
 * @method static Builder<Meter> meters()
 * @method static Meter meter(string $prm)
 * @method static Builder<DailyConsumption> dailyConsumption(?string $prm = null)
 * @method static Builder<HourlyConsumption> hourlyConsumption(?string $prm = null)
 * @method static array{meters: int, daily: int, hourly: int} sync(string $start, string $end, ?string $prm = null, string $type = 'all')
 *
 * @see DataManager
 */
final class LaraLinky extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DataManager::class;
    }
}
