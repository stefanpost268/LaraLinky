<?php

declare(strict_types=1);

namespace LaraLinky\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array<string, mixed> getDailyConsumption(string $start, string $end)
 * @method static array<string, mixed> getLoadCurve(string $start, string $end)
 * @method static array<string, mixed> getMaxPower(string $start, string $end, ?string $pas = null, ?string $grandeurPhysique = null)
 * @method static array<string, mixed> getDailyProduction(string $start, string $end)
 * @method static array<string, mixed> getProductionLoadCurve(string $start, string $end)
 * @method static list<string> prms()
 *
 * @see \LaraLinky\Linky
 */
final class Linky extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \LaraLinky\Linky::class;
    }
}
