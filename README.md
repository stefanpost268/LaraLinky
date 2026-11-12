<div align="center">
    <h1>LaraLinky</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/stefanpost/laralinky"><img src="https://img.shields.io/packagist/v/stefanpost/laralinky.svg?style=flat-square" alt="Packagist version"></a>
    <a href="https://packagist.org/packages/stefanpost/laralinky"><img src="https://img.shields.io/packagist/php-v/stefanpost/laralinky.svg?style=flat-square" alt="PHP version"></a>
    <a href="https://packagist.org/packages/stefanpost/laralinky"><img src="https://badge.laravel.cloud/badge/stefanpost/laralinky?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/stefanpost268/LaraLinky/actions"><img src="https://img.shields.io/github/actions/workflow/status/stefanpost268/LaraLinky/tests.yml?branch=main&label=Tests&style=flat-square" alt="Tests"></a>
    <a href="https://packagist.org/packages/stefanpost/laralinky"><img src="https://img.shields.io/packagist/dt/stefanpost/laralinky.svg?style=flat-square" alt="Total downloads"></a>
</p>

LaraLinky is a Laravel client for retrieving electricity consumption and production data from Enedis through the [Conso API](https://conso.boris.sh). It can also synchronize daily consumption and hourly load curve data to your application's database.

## Requirements

- PHP 8.3 or later
- Laravel 12 or 13

## Installation

Install the package with Composer:

```bash
composer require stefanpost/laralinky
```

Laravel discovers the service provider automatically. Publish the configuration if you want to customize the package settings:

```bash
php artisan vendor:publish --tag=laralinky-config
```

Set your Conso API token in `.env`:

```dotenv
LINKY_TOKEN=your.conso.api.jwt
```

The token identifies the PRMs you can access. API calls use the first PRM in the token by default; set `LINKY_PRM` to select another one for facade calls. The sync command processes all PRMs in the token unless you pass `--prm`.

```dotenv
LINKY_PRM=12345678901234
```

Optional client settings:

```dotenv
LINKY_BASE_URL=https://conso.boris.sh
LINKY_TIMEOUT=15
LINKY_USER_AGENT=my-laravel-app
```

Get a token from [conso.boris.sh](https://conso.boris.sh) after authorizing data sharing with Enedis. LaraLinky uses an existing token; it does not request consent or issue tokens.

## What you can do with LaraLinky

Use LaraLinky to:

- **Get Linky data when your app needs it.** Request consumption, production, load curve, or maximum power data from the Conso API. LaraLinky returns the API response to your code and does not save it automatically. [See the available API methods](#retrieve-data-from-the-api).
- **Keep consumption data in your database.** Sync daily consumption and hourly load curve data, then query it with LaraLinky's facade or Eloquent models. You can run a sync manually or let Laravel's scheduler run it automatically. Sync does not include production or maximum power data. [Set up scheduled sync](#set-up-scheduled-synchronization) or [sync manually](#manual-sync).

### Set up scheduled synchronization

To store consumption data automatically:

1. Configure `LINKY_TOKEN` in your application's `.env` file. Optionally set `LINKY_PRM` to choose the PRM used by direct facade requests. Scheduled sync processes every PRM in the token.
2. Publish and run LaraLinky's migrations:

   ```bash
   php artisan vendor:publish --tag=laralinky-migrations
   php artisan migrate
   ```

3. Enable Laravel's scheduler on your server. Laravel's scheduler checks for due tasks every minute, so add this cron entry:

   ```cron
   * * * * * cd /path/to/your-app && php artisan schedule:run >> /dev/null 2>&1
   ```

   Alternatively, keep `php artisan schedule:work` running as a supervised process. LaraLinky registers `linky:sync` to run hourly by default. Each run syncs the default date range: two days ago through today.

   For data that stays more up to date, consider scheduling `linky:sync` at least every 30 minutes in your application. For example, add this to your application's `routes/console.php`:

   ```php
   use Illuminate\Support\Facades\Schedule;

   Schedule::command('linky:sync')->everyThirtyMinutes()->withoutOverlapping();
   ```

You can also request a sync at any time. For example, run `php artisan linky:sync --type=daily` to sync daily records for the default date range. See [Manual sync](#manual-sync) for date and PRM options.

After the first sync, query the stored data with LaraLinky's facade or Eloquent models as shown in [Store and query consumption data](#store-and-query-consumption-data). If you only need an API response immediately, use the [direct API methods](#retrieve-data-from-the-api); those do not require the package migrations.

## Retrieve data from the API

Use `LaraLinky\\Facades\\Linky` to make requests. The methods return the decoded API response as an array; these calls do not save the response to the database:

```php
use LaraLinky\Facades\Linky;

$dailyConsumption = Linky::getDailyConsumption('2026-11-10', '2026-11-11');
$loadCurve = Linky::getLoadCurve('2026-11-10', '2026-11-11');
$maximumPower = Linky::getMaxPower('2026-11-01', '2026-12-01', 'P1M', 'PMA');
$dailyProduction = Linky::getDailyProduction('2026-11-10', '2026-11-11');
$productionCurve = Linky::getProductionLoadCurve('2026-11-10', '2026-11-11');
```

Available methods:

- `getDailyConsumption($start, $end)`
- `getLoadCurve($start, $end)`
- `getMaxPower($start, $end, $pas = null, $grandeurPhysique = null)`
- `getDailyProduction($start, $end)`
- `getProductionLoadCurve($start, $end)`

Dates use the `YYYY-MM-DD` format. The load curves contain half-hour measurements. The optional `getMaxPower` arguments set the measurement step and physical quantity, such as `P1M` and `PMA`. Requests that fail throw `LaraLinky\Exceptions\ApiException`, which exposes the HTTP status and response details when available.

## Store and query consumption data

Publish the package migrations and run them:

```bash
php artisan vendor:publish --tag=laralinky-migrations
php artisan migrate
```

The migrations create `laralinky_meters`, `laralinky_daily_consumption`, and `laralinky_hourly_consumption`. To use a different database connection or table prefix, configure `laralinky.storage.database.connection` or `laralinky.storage.database.table_prefix` in `config/laralinky.php`.

Query stored records with the `LaraLinky` facade:

```php
use LaraLinky\Facades\LaraLinky;

$meters = LaraLinky::meters()->get();
$meter = LaraLinky::meter('12345678901234');
$daily = $meter->dailyConsumption()->get();
$hourly = $meter->hourlyConsumption()->get();

// Query a data type directly, optionally filtering by PRM.
$daily = LaraLinky::dailyConsumption('12345678901234')->get();
$hourly = LaraLinky::hourlyConsumption()->where('value', '>', 0)->get();
```

The package also provides Eloquent models and repositories: `LaraLinky\Models\Meter`, `DailyConsumption`, and `HourlyConsumption`, plus matching classes under `LaraLinky\Repositories`. A meter has `dailyConsumption()` and `hourlyConsumption()` relationships.

## Manual sync

Sync writes daily consumption and hourly load curve data to the package tables. It does not sync production or maximum power data. Trigger a sync through the facade:

```php
use LaraLinky\\Facades\\LaraLinky;

$counts = LaraLinky::sync('2026-11-01', '2026-11-10');
$counts = LaraLinky::sync('2026-11-01', '2026-11-10', '12345678901234', 'daily');
// ['meters' => 1, 'daily' => 10, 'hourly' => 0]
```

Or use the Artisan command:

```bash
php artisan linky:sync
php artisan linky:sync --type=daily --prm=12345678901234
php artisan linky:sync --type=hourly --start=2026-11-01 --end=2026-11-10
```

The facade requires start and end dates. The command defaults to both data types, all PRMs in `LINKY_TOKEN`, and the period from two days ago through today. Choose `--type=daily` or `--type=hourly` to sync one data type; use `--prm`, `--start`, and `--end` to narrow the sync. Repeated syncs update existing records.

The command is scheduled to run hourly by default. For a more frequent sync, schedule the command at least every 30 minutes in your application; Laravel's scheduler still needs the once-per-minute cron entry or a continuously running `schedule:work` process.

## Local development

Build and start the Docker workbench:

```bash
docker compose up --build
```

The workbench is available at `http://localhost:8000`. Put its `.env` file beside `compose.yaml`; Docker Compose passes the `LINKY_*` settings to the app container. Recreate the container after changing the file:

```bash
docker compose up -d --force-recreate app
```

Run a sync in the workbench with:

```bash
docker compose exec app php vendor/bin/testbench linky:sync --type=hourly --prm=12345678901234
```

Run the package checks with:

```bash
docker compose run --rm app composer test
docker compose run --rm app composer lint:check
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release notes.

## Contributing

Contributions are welcome. See the [contributing guide](.github/CONTRIBUTING.md).

## Security

Report vulnerabilities according to the [security policy](.github/SECURITY.md).

## Credits

- [stefanpost268](https://github.com/stefanpost268)
- [All Contributors](../../contributors)

## License

LaraLinky is open-source software licensed under the [MIT License](LICENSE.md).
