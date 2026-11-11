<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use LaraLinky\Exceptions\ApiException;
use LaraLinky\Linky;

function linkyToken(array $claims = ['sub' => ['12345678901234', '98765432109876']]): string
{
    $encode = static fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');

    return $encode('{"alg":"none"}').'.'.$encode(json_encode($claims, JSON_THROW_ON_ERROR)).'.signature';
}

it('retrieves all supported data points using Laravel HTTP client', function (string $method, string $endpoint, array $extra = []) {
    Http::fake(['conso.boris.sh/*' => Http::response(['ok' => true])]);
    $linky = new Linky(linkyToken(), baseUrl: 'https://conso.boris.sh');

    $result = $linky->{$method}('2026-05-01', '2026-05-04', ...($extra === [] ? [] : array_values($extra)));

    expect($result)->toBe(['ok' => true]);
    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), $endpoint)
        && $request['dateDebut'] === '2026-05-01'
        && $request['dateFin'] === '2026-05-04'
        && $request['pointId'] === '12345678901234'
        && $request->hasHeader('Authorization', 'Bearer '.linkyToken()));
})->with([
    'daily consumption' => ['getDailyConsumption', '/api/consommation_quotidienne'],
    'consumption load curve' => ['getLoadCurve', '/api/courbe_de_charge_consommation'],
    'max power' => ['getMaxPower', '/api/puissance_conso_max_quotidienne', ['P1M', 'PMA']],
    'daily production' => ['getDailyProduction', '/api/production_quotidienne'],
    'production load curve' => ['getProductionLoadCurve', '/api/courbe_de_charge_production'],
]);

it('sends optional max power parameters', function () {
    Http::fake(['*' => Http::response(['ok' => true])]);
    (new Linky(linkyToken()))->getMaxPower('2026-01-01', '2026-02-01', 'P1M', 'PMA');

    Http::assertSent(fn (Request $request): bool => $request['mesuresPas'] === 'P1M' && $request['grandeurPhysique'] === 'PMA');
});

it('selects a requested prm and rejects an inaccessible prm', function () {
    Http::fake(['*' => Http::response(['ok' => true])]);
    (new Linky(linkyToken(), '98765432109876'))->getDailyConsumption('2026-05-01', '2026-05-02');
    Http::assertSent(fn (Request $request): bool => $request['pointId'] === '98765432109876');

    expect(fn () => (new Linky(linkyToken(), '00000000000000'))->getDailyConsumption('a', 'b'))
        ->toThrow(InvalidArgumentException::class, 'does not allow access to PRM');
});

it('rejects invalid tokens and reports api errors with response details', function () {
    expect(fn () => (new Linky('invalid'))->prms())->toThrow(InvalidArgumentException::class, 'token is invalid');

    Http::fake(['*' => Http::response(['error' => 'unauthorized'], 401)]);

    try {
        (new Linky(linkyToken()))->getDailyConsumption('2026-05-01', '2026-05-02');
        $this->fail('Expected API exception.');
    } catch (ApiException $exception) {
        expect($exception->status)->toBe(401)
            ->and($exception->responseBody)->toBe(['error' => 'unauthorized']);
    }
});

it('rejects tokens with malformed payloads or no string prms', function (string $token) {
    expect(fn () => (new Linky($token))->prms())->toThrow(InvalidArgumentException::class, 'token is invalid');
})->with([
    'non object payload' => ['e30.MTIz.signature'],
    'missing prms' => [linkyToken(['sub' => []])],
    'non string prms' => [linkyToken(['sub' => [null, 123]])],
]);

it('registers a singleton from package configuration', function () {
    config()->set('laralinky.token', linkyToken());
    config()->set('laralinky.prm', '98765432109876');

    expect(app(Linky::class))->toBe(app('laralinky'))
        ->and(app(Linky::class)->prms())->toBe(['12345678901234', '98765432109876']);
});

it('explains when a token is missing from both configuration and command options', function () {
    $this->artisan('linky:fetch daily')
        ->expectsOutputToContain('Provide a token with --token or configure LINKY_TOKEN.')
        ->assertExitCode(1);
});

it('provides an artisan command with output formatting and token override', function () {
    Http::fake(['*' => Http::response(['grandeur' => [['unite' => 'Wh', 'points' => [['d' => '2026-05-01', 'v' => '42']]]]])]);

    $this->artisan('linky:fetch daily --start=2026-05-01 --end=2026-05-02 --token='.linkyToken().' --format=json')
        ->expectsOutputToContain('"v": "42"')
        ->assertSuccessful();

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/api/consommation_quotidienne'));
});

it('writes csv results to the requested output file', function () {
    Http::fake(['*' => Http::response(['grandeur' => [['unite' => 'Wh', 'points' => [['d' => '2026-05-01', 'v' => '42']]]]])]);
    $path = tempnam(sys_get_temp_dir(), 'laralinky-');
    expect($path)->not->toBeFalse();

    try {
        $this->artisan('linky:fetch daily --start=2026-05-01 --end=2026-05-02 --token='.linkyToken().' --format=csv --output='.$path)
            ->expectsOutput('Results saved to '.$path)
            ->assertSuccessful();

        expect(file_get_contents($path))->toBe("Date,Valeur (Wh)\n2026-05-01,42\n");
    } finally {
        unlink($path);
    }
});

it('publishes its configuration from the documented tag', function () {
    $target = $this->app->configPath('laralinky.php');

    $this->artisan('vendor:publish', ['--tag' => 'laralinky-config', '--force' => true])
        ->assertSuccessful();

    expect($target)->toBeFile()
        ->and(require $target)->toHaveKey('base_url', 'https://conso.boris.sh');
});

it('supports the five endpoint types through the artisan command', function (string $type, string $path) {
    Http::fake(['*' => Http::response(['ok' => true])]);

    $this->artisan("linky:fetch {$type} --token=".linkyToken().' --start=2026-05-01 --end=2026-05-02 --format=json')
        ->assertSuccessful();

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), $path));
})->with([
    'daily consumption' => ['daily', '/api/consommation_quotidienne'],
    'consumption curve' => ['loadcurve', '/api/courbe_de_charge_consommation'],
    'maximum power' => ['maxpower', '/api/puissance_conso_max_quotidienne'],
    'daily production' => ['dailyprod', '/api/production_quotidienne'],
    'production curve' => ['loadcurveprod', '/api/courbe_de_charge_production'],
]);

it('defaults artisan date options to yesterday and today', function () {
    $this->travelTo(now()->setDate(2026, 5, 5)->setTime(12, 0));
    Http::fake(['*' => Http::response(['ok' => true])]);

    $this->artisan('linky:fetch daily --token='.linkyToken())
        ->assertSuccessful();

    Http::assertSent(fn (Request $request): bool => $request['dateDebut'] === '2026-05-04'
        && $request['dateFin'] === '2026-05-05');
});
