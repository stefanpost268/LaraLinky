<?php

declare(strict_types=1);

namespace LaraLinky;

use Illuminate\Database\Eloquent\Builder;
use LaraLinky\Models\DailyConsumption;
use LaraLinky\Models\HourlyConsumption;
use LaraLinky\Models\Meter;
use LaraLinky\Repositories\DailyConsumptionRepository;
use LaraLinky\Repositories\HourlyConsumptionRepository;
use LaraLinky\Repositories\MeterRepository;
use LaraLinky\Sync\SyncService;

final class DataManager
{
    public function __construct(
        private readonly MeterRepository $meters,
        private readonly DailyConsumptionRepository $daily,
        private readonly HourlyConsumptionRepository $hourly,
        private readonly SyncService $syncService,
    ) {}

    /** @return Builder<Meter> */
    public function meters(): Builder
    {
        return $this->meters->query();
    }

    public function meter(string $prm): Meter
    {
        return $this->meters->find($prm) ?? new Meter(['prm' => $prm]);
    }

    /** @return Builder<DailyConsumption> */
    public function dailyConsumption(?string $prm = null): Builder
    {
        return $this->daily->query($prm);
    }

    /** @return Builder<HourlyConsumption> */
    public function hourlyConsumption(?string $prm = null): Builder
    {
        return $this->hourly->query($prm);
    }

    /** @return array{meters: int, daily: int, hourly: int} */
    public function sync(string $start, string $end, ?string $prm = null, string $type = 'all'): array
    {
        return $this->syncService->sync($start, $end, $prm, $type);
    }
}
