<?php

declare(strict_types=1);

namespace LaraLinky\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Meter extends Model
{
    protected $primaryKey = 'prm';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    public function getTable(): string
    {
        return config('laralinky.storage.database.table_prefix', 'laralinky_').'meters';
    }

    public function getConnectionName(): ?string
    {
        return config('laralinky.storage.database.connection') ?: parent::getConnectionName();
    }

    /** @return HasMany<DailyConsumption, $this> */
    public function dailyConsumption(): HasMany
    {
        return $this->hasMany(DailyConsumption::class, 'prm', 'prm');
    }

    /** @return HasMany<HourlyConsumption, $this> */
    public function hourlyConsumption(): HasMany
    {
        return $this->hasMany(HourlyConsumption::class, 'prm', 'prm');
    }
}
