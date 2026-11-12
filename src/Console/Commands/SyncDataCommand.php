<?php

declare(strict_types=1);

namespace LaraLinky\Console\Commands;

use Illuminate\Console\Command;
use LaraLinky\Sync\SyncService;
use Throwable;

final class SyncDataCommand extends Command
{
    protected $signature = 'linky:sync
        {--start= : Start date (YYYY-MM-DD), defaults to two days ago}
        {--end= : End date (YYYY-MM-DD), defaults to today}
        {--prm= : PRM to sync, defaults to all meters in the token}
        {--type=all : all|daily|hourly}';

    protected $description = 'Synchronize Linky consumption data into the package database';

    public function handle(SyncService $syncService): int
    {
        $type = $this->option('type');

        if (! is_string($type)) {
            $this->error('Type must be all, daily, or hourly.');

            return self::FAILURE;
        }

        $start = $this->option('start');
        $end = $this->option('end');
        $prm = $this->option('prm');

        try {
            $counts = $syncService->sync(
                is_string($start) && $start !== '' ? $start : now()->subDays(2)->toDateString(),
                is_string($end) && $end !== '' ? $end : now()->toDateString(),
                is_string($prm) && $prm !== '' ? $prm : null,
                $type,
            );

            $this->info(sprintf(
                'Synced %d meter(s): %d daily and %d hourly records.',
                $counts['meters'],
                $counts['daily'],
                $counts['hourly'],
            ));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
