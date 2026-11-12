<?php

declare(strict_types=1);

namespace LaraLinky;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use JsonException;
use LaraLinky\Exceptions\ApiException;
use RuntimeException;
use Throwable;

final class Linky
{
    /** @var non-empty-string */
    public const string DAILY_CONSUMPTION = 'consommation_quotidienne';

    /** @var non-empty-string */
    public const string CONSUMPTION_LOAD_CURVE = 'courbe_de_charge_consommation';

    /** @var non-empty-string */
    public const string CONSUMPTION_MAX_POWER = 'puissance_conso_max_quotidienne';

    /** @var non-empty-string */
    public const string DAILY_PRODUCTION = 'production_quotidienne';

    /** @var non-empty-string */
    public const string PRODUCTION_LOAD_CURVE = 'courbe_de_charge_production';

    /** @var non-empty-string */
    private const string API_HOST = 'https://conso.boris.sh';

    public function __construct(
        private readonly string $token,
        private readonly string $prm = '',
        private readonly string $baseUrl = self::API_HOST,
        private readonly float $timeout = 15,
        private readonly string $userAgent = 'LaraLinky',
    ) {}

    /** @return list<string> */
    public function prms(): array
    {
        try {
            $parts = explode('.', $this->token);

            if (! isset($parts[1])) {
                throw new RuntimeException;
            }
            $payload = json_decode($this->base64UrlDecode($parts[1]), true, flags: JSON_THROW_ON_ERROR);
        } catch (RuntimeException|JsonException $exception) {
            throw new InvalidArgumentException('The Linky token is invalid.', previous: $exception);
        }

        if (! is_array($payload)) {
            throw new InvalidArgumentException('The Linky token is invalid.');
        }

        $prms = $payload['sub'] ?? null;

        if (! is_array($prms) || $prms === []) {
            throw new InvalidArgumentException('The Linky token is invalid.');
        }

        $prms = array_values(array_filter($prms, 'is_string'));

        if ($prms === []) {
            throw new InvalidArgumentException('The Linky token is invalid.');
        }

        return $prms;
    }

    /** @return array<string, mixed> */
    public function getDailyConsumption(string $start, string $end): array
    {
        return $this->request(self::DAILY_CONSUMPTION, $start, $end);
    }

    /** @return array<string, mixed> */
    public function getLoadCurve(string $start, string $end): array
    {
        return $this->request(self::CONSUMPTION_LOAD_CURVE, $start, $end);
    }

    /** @return array<string, mixed> */
    public function getMaxPower(string $start, string $end, ?string $pas = null, ?string $grandeurPhysique = null): array
    {
        return $this->request(self::CONSUMPTION_MAX_POWER, $start, $end, array_filter([
            'mesuresPas' => $pas,
            'grandeurPhysique' => $grandeurPhysique,
        ], static fn (?string $value): bool => $value !== null && $value !== ''));
    }

    /** @return array<string, mixed> */
    public function getDailyProduction(string $start, string $end): array
    {
        return $this->request(self::DAILY_PRODUCTION, $start, $end);
    }

    /** @return array<string, mixed> */
    public function getProductionLoadCurve(string $start, string $end): array
    {
        return $this->request(self::PRODUCTION_LOAD_CURVE, $start, $end);
    }

    /** @param array<string, string> $optional
     * @return array<string, mixed>
     */
    private function request(string $type, string $start, string $end, array $optional = []): array
    {
        $prms = $this->prms();
        $prm = $this->prm !== '' ? $this->prm : $prms[0];

        if (! in_array($prm, $prms, true)) {
            throw new InvalidArgumentException("This token does not allow access to PRM {$prm}.");
        }

        try {
            $response = $this->pendingRequest()->get(rtrim($this->baseUrl, '/').'/api/'.$type, array_merge([
                'dateDebut' => $start,
                'dateFin' => $end,
                'pointId' => $prm,
            ], $optional));
        } catch (Throwable $exception) {
            throw new ApiException('Unable to contact Conso API: '.$exception->getMessage(), previous: $exception);
        }

        if ($response->failed()) {
            throw new ApiException(
                'Conso API responded with an error.',
                $response->status(),
                $response->json() ?? $response->body(),
                $response->transferStats?->getRequest(),
                $response,
            );
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new ApiException('Conso API returned an invalid JSON response.', $response->status(), $response->body(), response: $response);
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    private function pendingRequest(): PendingRequest
    {
        return Http::acceptJson()
            ->withToken($this->token)
            ->withUserAgent($this->userAgent)
            ->timeout($this->timeout);
    }

    private function base64UrlDecode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/').str_repeat('=', (4 - strlen($value) % 4) % 4), true);

        if ($decoded === false) {
            throw new RuntimeException('Invalid token payload.');
        }

        return $decoded;
    }
}
