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

## Retrieve API data

Use `LaraLinky\\Facades\\Linky` to make requests. The methods return the decoded API response as an array:

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

Synchronize daily consumption and hourly consumption load curve data with `linky:sync`:

```bash
php artisan linky:sync
php artisan linky:sync --type=daily --prm=12345678901234
php artisan linky:sync --type=hourly --start=2026-11-01 --end=2026-11-10
```

The command defaults to both data types, all PRMs in `LINKY_TOKEN`, and the period from two days ago through today. Choose `--type=daily` or `--type=hourly` to sync one data type; use `--prm`, `--start`, and `--end` to narrow the sync. Repeated syncs update existing records.

The package registers the command to run hourly. Enable Laravel's scheduler using the normal `schedule:run` cron entry or a continuously running `schedule:work` process.

Query stored records with the `LaraLinky` facade:

```php
use LaraLinky\Facades\LaraLinky;

$meters = LaraLinky::meters()->get();
$meter = LaraLinky::meter('12345678901234');
$daily = $meter->dailyConsumption()->get();
$hourly = $meter->hourlyConsumption()->get();
```

The package also provides Eloquent models and repositories: `LaraLinky\Models\Meter`, `DailyConsumption`, and `HourlyConsumption`, plus matching classes under `LaraLinky\Repositories`. A meter has `dailyConsumption()` and `hourlyConsumption()` relationships.

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
