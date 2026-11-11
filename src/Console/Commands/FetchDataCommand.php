<?php

declare(strict_types=1);

namespace LaraLinky\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use LaraLinky\Linky;
use RuntimeException;
use Throwable;

final class FetchDataCommand extends Command
{
    protected $signature = 'linky:fetch
        {type : daily|loadcurve|maxpower|dailyprod|loadcurveprod}
        {--start= : Start date (YYYY-MM-DD), defaults to yesterday}
        {--end= : End date (YYYY-MM-DD), defaults to today}
        {--prm= : PRM to use}
        {--token= : Conso API token override}
        {--pas= : Max power measurement step, e.g. P1D or P1M}
        {--grandeur-physique= : Max power physical quantity, e.g. PMA or TOUT}
        {--format=pretty : pretty|json|csv}
        {--output= : Write output to this file}';

    protected $description = 'Fetch Linky consumption or production data from Conso API';

    public function handle(Linky $configuredLinky): int
    {
        $type = $this->argumentString('type');
        $format = $this->optionString('format') ?? 'pretty';

        if (! in_array($format, ['pretty', 'json', 'csv'], true)) {
            $this->error('Format must be one of: pretty, json, csv.');

            return self::FAILURE;
        }

        try {
            $linky = $this->client($configuredLinky);
            $start = $this->optionString('start') ?: now()->subDay()->toDateString();
            $end = $this->optionString('end') ?: now()->toDateString();
            $data = match ($type) {
                'daily' => $linky->getDailyConsumption($start, $end),
                'loadcurve' => $linky->getLoadCurve($start, $end),
                'maxpower' => $linky->getMaxPower($start, $end, $this->optionString('pas'), $this->optionString('grandeur-physique')),
                'dailyprod' => $linky->getDailyProduction($start, $end),
                'loadcurveprod' => $linky->getProductionLoadCurve($start, $end),
                default => throw new InvalidArgumentException('Unknown type. Use daily, loadcurve, maxpower, dailyprod or loadcurveprod.'),
            };
            $rendered = $this->render($data, $format);
            $output = $this->optionString('output');

            if (is_string($output) && $output !== '') {
                $directory = dirname($output);

                if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
                    throw new RuntimeException("Unable to create output directory: {$directory}");
                }

                if (file_put_contents($output, $rendered) === false) {
                    throw new RuntimeException("Unable to write output file: {$output}");
                }

                if (! $this->option('quiet')) {
                    $this->info("Results saved to {$output}");
                }

                return self::SUCCESS;
            }

            $this->line($rendered);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function client(Linky $configured): Linky
    {
        $token = $this->optionString('token');
        $prm = $this->optionString('prm');

        if ($token === null || $token === '') {
            $configuredToken = app('config')->get('laralinky.token');

            if (! is_string($configuredToken) || $configuredToken === '') {
                throw new InvalidArgumentException('Provide a token with --token or configure LINKY_TOKEN.');
            }

            if ($prm !== null && $prm !== '') {
                return $this->configuredClient($configuredToken, $prm);
            }

            return $configured;
        }

        return $this->configuredClient($token, $prm ?? '');
    }

    private function configuredClient(string $token, string $prm): Linky
    {
        return new Linky(
            $token,
            $prm,
            app('config')->string('laralinky.base_url'),
            app('config')->float('laralinky.timeout'),
            app('config')->string('laralinky.user_agent'),
        );
    }

    private function argumentString(string $name): string
    {
        $value = $this->argument($name);

        if (! is_string($value)) {
            throw new InvalidArgumentException("The {$name} argument must be a string.");
        }

        return $value;
    }

    private function optionString(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) ? $value : null;
    }

    /** @param array<string, mixed> $data */
    private function render(array $data, string $format): string
    {
        if ($format === 'json') {
            return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
        }

        $points = [];
        foreach (($data['grandeur'] ?? []) as $grandeur) {
            if (! is_array($grandeur)) {
                continue;
            }
            foreach (($grandeur['points'] ?? []) as $point) {
                if (is_array($point)) {
                    $points[] = [
                        'date' => $point['d'] ?? '',
                        'value' => $point['v'] ?? null,
                        'unit' => $grandeur['unite'] ?? '',
                    ];
                }
            }
        }

        if ($format === 'csv') {
            $lines = ['Date,Valeur ('.($points[0]['unit'] ?? '').')'];
            foreach ($points as $point) {
                $lines[] = (string) $point['date'].','.($point['value'] === null ? 'null' : (string) $point['value']);
            }

            return implode("\n", $lines)."\n";
        }

        if ($points === []) {
            return 'No data points returned.'.PHP_EOL;
        }

        $maxValue = max(array_map(static function (array $point): float {
            $value = $point['value'];

            return is_numeric($value) ? (float) $value : 0.0;
        }, $points));
        $lines = [sprintf('%-24s %-18s %s', 'Date', 'Valeur ('.$points[0]['unit'].')', 'Graphique')];
        foreach ($points as $point) {
            $value = $point['value'];
            $numeric = is_numeric($value) ? (float) $value : 0.0;
            $bar = $maxValue > 0 && $numeric > 0 ? str_repeat('■', (int) ceil(30 * $numeric / $maxValue)) : '';
            $lines[] = sprintf('%-24s %-18s %s', (string) $point['date'], (string) ($value ?? 'null'), $bar);
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }
}
