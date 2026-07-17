<?php

namespace App\Services\Lekuka;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class LekukaClient
{
    protected PendingRequest $client;

    public function __construct(
        protected CertificateService $certificates,
        protected LekukaAuditService $audit
    )
    {
    }

    protected function client(
        ?LekukaDevice $device = null
    ): PendingRequest {

        $client = Http::baseUrl(config('lekuka.base_url'))
            ->acceptJson()
            ->contentType('application/json')
            ->timeout(config('lekuka.timeout'));

        /*
        |--------------------------------------------------------------------------
        | Public endpoints
        |--------------------------------------------------------------------------
        */

        if ($device === null) {
            return $client;
        }

        /*
        |--------------------------------------------------------------------------
        | Protected endpoints
        |--------------------------------------------------------------------------
        */

        return $client->withOptions([

            'cert' => $this->certificates
                ->getCertificatePath($device),

            'ssl_key' => $this->certificates
                ->getPrivateKeyPath($device),

            'verify' => $this->certificates
                ->getServerCertificatePath(),
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
}