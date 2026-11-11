<div align="center">
    <h1>LaraLinky</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/stefanpost/laralinky"><img src="https://img.shields.io/packagist/v/stefanpost/laralinky.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/stefanpost/laralinky"><img src="https://img.shields.io/packagist/php-v/stefanpost/laralinky.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/stefanpost/laralinky"><img src="https://badge.laravel.cloud/badge/stefanpost/laralinky?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/stefanpost/LaraLinky/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/stefanpost/LaraLinky/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/stefanpost/laralinky"><img src="https://img.shields.io/packagist/dt/stefanpost/laralinky.svg?style=flat-square" alt="Total Downloads"></a>
</p>



## Installation

You can install the package via Composer:

```bash
composer require stefanpost/laralinky
```

## Usage

For local package development, start the workbench in Docker with:

```bash
docker compose up --build
```

The workbench is available at `http://localhost:8000`. Run package checks with `docker compose run --rm app composer test`. Run its Artisan commands through Testbench, for example:

```bash
docker compose exec app php vendor/bin/testbench linky:fetch daily --prm=12345678901234 --format=json
```

For Docker workbench commands, put `LINKY_TOKEN` (and optionally `LINKY_PRM`) in the `.env` file beside `compose.yaml`; Compose passes those values into the container. Recreate the workbench container after changing `.env`:

```bash
docker compose up -d --force-recreate app
```

Publish the configuration (optional) and add your Conso API token and PRM to `.env`:

```bash
php artisan vendor:publish --tag=laralinky-config
```

```dotenv
LINKY_TOKEN=your.conso.api.jwt
LINKY_PRM=12345678901234
```

The package provides a Laravel service bound as `LaraLinky\Linky`. It supports the same five data endpoints as the Node.js `linky` package:

```php
use LaraLinky\Linky;

// Laravel resolves the configured service from the container.
$data = app(Linky::class)->getDailyConsumption('2026-05-01', '2026-05-04');
$curve = app(Linky::class)->getLoadCurve('2026-05-01', '2026-05-02');
$maxPower = app(Linky::class)->getMaxPower('2026-01-01', '2026-11-01', 'P1M', 'PMA');
$production = app(Linky::class)->getDailyProduction('2026-05-01', '2026-05-04');
$productionCurve = app(Linky::class)->getProductionLoadCurve('2026-05-01', '2026-05-02');
```

The methods return the decoded Conso API response as an associative array. `getMaxPower` accepts optional measurement interval and physical quantity arguments. When `LINKY_PRM` is omitted, the first PRM in the token is selected. API errors throw `LaraLinky\Exceptions\ApiException`, which exposes the HTTP status and response body. You can also use the `Linky` facade (`LaraLinky\Facades\Linky::getDailyConsumption(...)`).

For console workflows, the package registers `linky:fetch` with the same data types and formats as the Node CLI:

```bash
php artisan linky:fetch daily --start=2026-05-01 --end=2026-05-04 --format=json
php artisan linky:fetch maxpower --pas=P1M --grandeur-physique=PMA --output=storage/app/power.csv --format=csv
php artisan linky:fetch dailyprod --token=your.conso.api.jwt --prm=12345678901234
php artisan linky:fetch daily --format=json | jq '.grandeur[0].points[0].v'
```

The date options default to yesterday and today. Set `LINKY_TOKEN` and optionally `LINKY_PRM`, or pass per-run overrides. The package does not request Enedis consent itself; obtain the Conso API token through [conso.boris.sh](https://conso.boris.sh).

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to LaraLinky! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Stefan Post](https://github.com/stefanpost)
- [All Contributors](../../contributors)

## License

LaraLinky is open-sourced software licensed under the [MIT license](LICENSE.md).
