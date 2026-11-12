<?php

declare(strict_types=1);

namespace LaraLinky\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HourlyConsumption extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['measured_at' => 'datetime', 'value' => 'decimal:4'];
    }

    public function getTable(): string
    {
        $prefix = config('laralinky.storage.database.table_prefix', 'laralinky_');

        return (is_string($prefix) ? $prefix : 'laralinky_').'hourly_consumption';
    }

    public function getConnectionName(): ?string
    {
        $connection = config('laralinky.storage.database.connection');

        return is_string($connection) && $connection !== '' ? $connection : parent::getConnectionName();
    }

    /** @return BelongsTo<Meter, $this> */
    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class, 'prm', 'prm');
    }
}
