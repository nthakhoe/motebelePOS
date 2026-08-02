<?php

namespace App\Services\Lekuka;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use App\Models\LekukaDevice;
use Illuminate\Http\Client\RequestException;


class LekukaClient
{
    protected PendingRequest $client;

    public function __construct(
        protected CertificateService $certificates,
        protected LekukaAuditService $audit
    )
    {
    }

    protected function client(?LekukaDevice $device = null): PendingRequest
    {
        $client = Http::baseUrl(config('lekuka.base_url'))
            ->acceptJson()
            ->contentType('application/json')
            ->timeout(config('lekuka.timeout'));

        if ($device) {
            $client = $client->withHeaders([
                'DeviceModelName'      => $device->device_model,
                'DeviceModelVersion' => $device->device_model_version,
            ]);
        }

        if ($device === null) {
            return $client;
        }

        return $client->withOptions([
            'cert'    => $this->certificates->getCertificatePath($device),
            'ssl_key' => $this->certificates->getPrivateKeyPath($device),
            'verify'  => false,
        ]);
    }

    protected function handle(Response $response): Response
    {
        if ($response->failed()) {

            logger()->error('Lekuka API Error', [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);

            $response->throw();
        }

        return $response;
    }

    protected function request(

        string $method,

        string $endpoint,

        array $payload = [],

        ?LekukaDevice $device = null,

        ?string $action = null,

        ?string $correlationId = null

    ): Response {

        $start = microtime(true);

        $client = $this->client($device);

        $response = $this->client($device)
            ->send(
                $method,
                $endpoint,
                [
                    'json' => $payload
                ]
            );

        $this->audit->log([

            'company_id' => $device?->company_id,

            'branch_id' => $device?->branch_id,

            'device_id' => $device?->id,

            'user_id' => auth()->id(),

            'correlation_id' => $correlationId,

            'action' => $action,

            'endpoint' => $endpoint,

            'http_method' => strtoupper($method),

            'http_status' => $response->status(),

            'status' => $response->successful()
                            ? 'SUCCESS'
                            : 'FAILED',

            'request' => $payload,

            'response' => $response->json(),

            'duration_ms' => (microtime(true)-$start)*1000,

            'ip_address' => request()?->ip(),

        ]);

        return $response;
    }

    public function post(

        string $endpoint,

        array $payload = [],

        ?string $action = null,

        ?string $correlationId = null

    ): Response {

        return $this->request(

            'POST',

            $endpoint,

            $payload,

            null,

            $action,

            $correlationId

        );
    }

    public function securePost(

        LekukaDevice $device,

        string $endpoint,

        array $payload = [],

        string $action,

        string $correlationId

    ): Response {

        return $this->request(

            'POST',

            $endpoint,

            $payload,

            $device,

            $action,

            $correlationId

        );
    }

    protected function correlationId(
        ?string $id
    ): string
    {
        return $id ?: (string) Str::uuid();
    }

    protected function publicClient()
    {
        return Http::baseUrl(
            config('services.lekuka.base_url')
        )
        ->timeout(60)
        ->acceptJson()
        ->withHeaders([

            'DeviceModelName' =>
                config('services.lekuka.device_model'),

            'DeviceModelVersion' =>
                config('services.lekuka.device_model_version'),

        ]);
    }

    public function registerDevice(
        LekukaDevice $device,
        string $csr
    ): array {

        $payload = [
            'activationKey'      => $device->activation_key,
            'certificateRequest' => $csr,
            'deviceModelName'    => trim($device->device_model),
            'deviceModelVersion' => trim($device->device_model_version),
        ];

        $model = preg_replace('/[\r\n]+/', '', trim($device->device_model));
        $version = preg_replace('/[\r\n]+/', '', trim($device->device_model_version));
        $response = Http::baseUrl(config('services.lekuka.base_url'))
            ->acceptJson()
            ->withHeaders([
                'DeviceModelName' => $model,
                'DeviceModelVersion' => $version,
            ])
            ->post(
                "/Public/v1/{$device->device_id}/RegisterDevice",
                $payload
            );

        logger()->info('Lekuka Register Response', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        if (! $response->successful()) {
            throw new \Exception($response->body());
        }

        return $response->json();
    }

    public function getServerCertificate(LekukaDevice $device): string
    {
        $response = $this->client($device)
            ->get('/Public/v1/GetServerCertificate');

        $this->handle($response);

        return $response->json('certificate.0');
    }

    public function secureGet(
        LekukaDevice $device,
        string $endpoint,
        string $action,
        ?string $correlationId = null
    ): Response {

        return $this->request(
            'GET',
            $endpoint,
            [],
            $device,
            $action,
            $correlationId
        );
    }

}