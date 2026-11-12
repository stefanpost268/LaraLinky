<?php

declare(strict_types=1);

namespace LaraLinky\Sync;

use InvalidArgumentException;
use LaraLinky\Linky;
use LaraLinky\Storage\DatabaseStorage;

final class SyncService
{
    public function __construct(private readonly DatabaseStorage $storage) {}

    /** @return array{meters: int, daily: int, hourly: int} */
    public function sync(string $start, string $end, ?string $prm = null, string $type = 'all'): array
    {
        if (! in_array($type, ['all', 'daily', 'hourly'], true)) {
            throw new InvalidArgumentException('Sync type must be all, daily, or hourly.');
        }

        $token = config('laralinky.token');

        if (! is_string($token) || $token === '') {
            throw new InvalidArgumentException('Configure LINKY_TOKEN before syncing data.');
        }

        $configuredClient = app(Linky::class);
        $prms = $prm === null ? $configuredClient->prms() : [$prm];
        $totals = ['meters' => 0, 'daily' => 0, 'hourly' => 0];

        foreach ($prms as $meterPrm) {
            $client = new Linky(
                $token,
                $meterPrm,
                $this->stringConfig('laralinky.base_url', 'https://conso.boris.sh'),
                $this->floatConfig('laralinky.timeout', 15),
                $this->stringConfig('laralinky.user_agent', 'LaraLinky'),
            );

            if ($type === 'all' || $type === 'daily') {
                $counts = $this->storage->store($meterPrm, $client->getDailyConsumption($start, $end), true);
                $totals['daily'] += $counts['daily'];
            }

            if ($type === 'all' || $type === 'hourly') {
                $counts = $this->storage->store($meterPrm, $client->getLoadCurve($start, $end), false);
                $totals['hourly'] += $counts['hourly'];
            }

            $totals['meters']++;
        }

        return $totals;
    }

    private function stringConfig(string $key, string $default): string
    {
        $value = config($key, $default);

        return is_string($value) ? $value : $default;
    }

    private function floatConfig(string $key, float $default): float
    {
        $value = config($key, $default);

        return is_numeric($value) ? (float) $value : $default;
    }
}
