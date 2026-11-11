<div align="center">
    <h1>LaraLinky</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/stefanpost/laralinky"><img src="https://img.shields.io/packagist/v/stefanpost/laralinky.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/stefanpost/laralinky"><img src="https://img.shields.io/packagist/php-v/stefanpost/laralinky.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/stefanpost/laralinky"><img src="https://badge.laravel.cloud/badge/stefanpost/laralinky?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/stefanpost268/LaraLinky/actions"><img src="https://img.shields.io/github/actions/workflow/status/stefanpost268/LaraLinky/tests.yml?branch=main&label=Tests&style=flat-square" alt="Tests"></a>
    <a href="https://packagist.org/packages/stefanpost/laralinky"><img src="https://img.shields.io/packagist/dt/stefanpost/laralinky.svg?style=flat-square" alt="Total downloads"></a>
</p>

LaraLinky is a Laravel package for retrieving electricity consumption and production data from Enedis through [Conso API](https://conso.boris.sh).

## Installation

Install the package with Composer:

```bash
composer require stefanpost/laralinky
```

Laravel discovers the service provider automatically.

## Configuration

You can publish the package configuration file:

```bash
php artisan vendor:publish --tag=laralinky-config
```

Add your Conso API token and, optionally, a PRM to your Laravel application's `.env` file:

```dotenv
LINKY_TOKEN=your.conso.api.jwt
LINKY_PRM=12345678901234
```

The token authorizes access to the meters shared with your account. The PRM selects a meter; if omitted, LaraLinky uses the first PRM in the token. You can override the token and PRM for an individual Artisan request.

Optional settings:

```dotenv
LINKY_BASE_URL=https://conso.boris.sh
LINKY_TIMEOUT=15
LINKY_USER_AGENT=my-laravel-app
```

Obtain your token from [conso.boris.sh](https://conso.boris.sh) after authorizing data sharing with Enedis. LaraLinky does not request consent or issue tokens.

## Retrieve data in PHP

Resolve the client from Laravel's service container:

```php
use LaraLinky\Linky;

$linky = app(Linky::class);

$dailyConsumption = $linky->getDailyConsumption('2026-11-10', '2026-11-11');
$consumptionCurve = $linky->getLoadCurve('2026-11-10', '2026-11-11');
$maximumPower = $linky->getMaxPower('2026-11-01', '2026-12-01', 'P1M', 'PMA');
$dailyProduction = $linky->getDailyProduction('2026-11-10', '2026-11-11');
$productionCurve = $linky->getProductionLoadCurve('2026-11-10', '2026-11-11');
```

Available methods:

- `getDailyConsumption($start, $end)`
- `getLoadCurve($start, $end)`
- `getMaxPower($start, $end, $pas = null, $grandeurPhysique = null)`
- `getDailyProduction($start, $end)`
- `getProductionLoadCurve($start, $end)`

The methods return the decoded Conso API response as an associative array. The load curves contain half-hour measurements. `getMaxPower` accepts optional measurement-step and physical-quantity values, such as `P1M` and `PMA`.

You can also call the client through the facade:

```php
use LaraLinky\Facades\Linky;

$data = Linky::getDailyConsumption('2026-11-10', '2026-11-11');
```

Failed API requests throw `LaraLinky\Exceptions\ApiException`. The exception provides the HTTP status, response body, request, and response when available.

## Artisan command

The package provides `linky:fetch` for retrieving and formatting data:

```bash
php artisan linky:fetch daily --start=2026-11-10 --end=2026-11-11 --format=json
php artisan linky:fetch loadcurve --prm=12345678901234 --format=pretty
php artisan linky:fetch maxpower --pas=P1M --grandeur-physique=PMA --format=csv --output=storage/app/power.csv
php artisan linky:fetch dailyprod --token=your.conso.api.jwt --prm=12345678901234
```

Supported types are `daily`, `loadcurve`, `maxpower`, `dailyprod`, and `loadcurveprod`. The start date defaults to yesterday and the end date to today. Output formats are `pretty`, `json`, and `csv`; use `--output` to save results to a file. You can select a meter with `--prm` and provide a per-request token with `--token`.

## Local development

Start the package workbench in Docker:

```bash
docker compose up --build
```

The workbench is available at `http://localhost:8000`. Its `.env` file should be in the same directory as `compose.yaml`; Docker Compose passes the `LINKY_*` settings into the container. Recreate the workbench after changing that file:

```bash
docker compose up -d --force-recreate app
```

Run the workbench command through Testbench:

```bash
docker compose exec app php vendor/bin/testbench linky:fetch loadcurve --start=2026-11-10 --end=2026-11-11 --prm=12345678901234 --format=json
```

Pass `--token=your.conso.api.jwt` to provide a token directly for that request. Run the package checks with:

```bash
docker compose run --rm app composer test
docker compose run --rm app composer lint:check
```

## Changelog

See [CHANGELOG](CHANGELOG.md) for release notes.

## Contributing

Contributions are welcome. Please read the [contributing guide](.github/CONTRIBUTING.md).

## Security

Please see the [security policy](.github/SECURITY.md) to report a vulnerability.

## Credits

- [stefanpost268](https://github.com/stefanpost268)
- [All Contributors](../../contributors)

## License

LaraLinky is open-sourced software licensed under the [MIT license](LICENSE.md).
