<?php

declare(strict_types=1);

namespace LaraLinky\Repositories;

use Illuminate\Database\Eloquent\Builder;
use LaraLinky\Models\Meter;

final class MeterRepository
{
    public function __construct(private readonly ?string $connection = null, private readonly string $tablePrefix = 'laralinky_') {}

    public function find(string $prm): ?Meter
    {
        return $this->query()->find($prm);
    }

    /** @return Builder<Meter> */
    public function query(): Builder
    {
        $model = new Meter;
        $model->setTable($this->tablePrefix.'meters');

        if ($this->connection !== null) {
            $model->setConnection($this->connection);
        }

        return $model->newQuery();
    }

    public function save(string $prm): Meter
    {
        return $this->query()->updateOrCreate(['prm' => $prm]);
    }
}
