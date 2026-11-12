<?php

declare(strict_types=1);

namespace LaraLinky\Repositories;

use Illuminate\Database\Eloquent\Builder;
use LaraLinky\Models\HourlyConsumption;

final class HourlyConsumptionRepository
{
    public function __construct(private readonly ?string $connection = null, private readonly string $tablePrefix = 'laralinky_') {}

    /** @param list<array<string, mixed>> $rows */
    public function upsert(array $rows): int
    {
        foreach (array_chunk($rows, 500) as $chunk) {
            $this->modelQuery()->upsert($chunk, ['prm', 'measured_at'], ['value', 'unit', 'updated_at']);
        }

        return count($rows);
    }

    /** @return Builder<HourlyConsumption> */
    public function query(?string $prm = null): Builder
    {
        $query = $this->modelQuery()->orderBy('measured_at');

        return $prm === null ? $query : $query->where('prm', $prm);
    }

    /** @return Builder<HourlyConsumption> */
    private function modelQuery(): Builder
    {
        $model = new HourlyConsumption;
        $model->setTable($this->tablePrefix.'hourly_consumption');

        if ($this->connection !== null) {
            $model->setConnection($this->connection);
        }

        return $model->newQuery();
    }
}
