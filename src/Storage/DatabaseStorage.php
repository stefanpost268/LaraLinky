<?php

declare(strict_types=1);

namespace LaraLinky\Storage;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use LaraLinky\Repositories\DailyConsumptionRepository;
use LaraLinky\Repositories\HourlyConsumptionRepository;
use LaraLinky\Repositories\MeterRepository;

final class DatabaseStorage
{
    private readonly MeterRepository $meters;

    private readonly DailyConsumptionRepository $dailyConsumption;

    private readonly HourlyConsumptionRepository $hourlyConsumption;

    public function __construct(
        DatabaseManager $database,
        ?string $connection = null,
        string $tablePrefix = 'laralinky_',
        ?MeterRepository $meters = null,
        ?DailyConsumptionRepository $dailyConsumption = null,
        ?HourlyConsumptionRepository $hourlyConsumption = null,
    ) {
        if ($tablePrefix === '' || ! preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $tablePrefix)) {
            throw new InvalidArgumentException('The LaraLinky database table prefix must start with a letter and contain only letters, numbers, and underscores.');
        }

        $database->connection($connection);
        $this->meters = $meters ?? new MeterRepository($connection, $tablePrefix);
        $this->dailyConsumption = $dailyConsumption ?? new DailyConsumptionRepository($connection, $tablePrefix);
        $this->hourlyConsumption = $hourlyConsumption ?? new HourlyConsumptionRepository($connection, $tablePrefix);
    }

    /** @param array<string, mixed> $response
     * @return array{daily: int, hourly: int}
     */
    public function store(string $prm, array $response, bool $daily): array
    {
        if ($prm === '') {
            throw new InvalidArgumentException('A PRM is required to store Linky data.');
        }

        $now = now();
        $this->meters->save($prm);

        $dailyRows = [];
        $hourlyRows = [];
        $grandeurs = Arr::get($response, 'grandeur', []);

        if (! is_array($grandeurs)) {
            $grandeurs = [];
        }

        foreach ($grandeurs as $grandeur) {
            if (! is_array($grandeur) || ! is_array($grandeur['points'] ?? null)) {
                continue;
            }
            $unit = is_string($grandeur['unite'] ?? null) ? $grandeur['unite'] : null;
            foreach ($grandeur['points'] as $point) {
                if (! is_array($point) || ! is_string($point['d'] ?? null) || ! array_key_exists('v', $point)) {
                    continue;
                }
                $row = [
                    'prm' => $prm,
                    'measured_at' => $point['d'],
                    'value' => is_numeric($point['v']) ? (float) $point['v'] : null,
                    'unit' => $unit,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($daily) {
                    $dailyRows[] = $row;
                } else {
                    $hourlyRows[] = $row;
                }
            }
        }

        $dailyCount = $this->dailyConsumption->upsert($dailyRows);
        $hourlyCount = $this->hourlyConsumption->upsert($hourlyRows);

        return ['daily' => $dailyCount, 'hourly' => $hourlyCount];
    }
}
