<?php

namespace App\Services\Lekuka;

use App\Models\LekukaDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Illuminate\Support\Str;

class DeviceService
{
    public function __construct(

        protected LekukaClient $client,

        protected CertificateService $certificates,

        protected ConfigurationService $configuration,

        protected CsrService $csr,

        protected LekukaAuditService $audit

    ) {}

    public function register(LekukaDevice $device): LekukaDevice
    {
        return DB::transaction(function () use ($device) {

            // Generate key pair
            $keys = $this->csr->generateKeyPair();

            // Generate CSR
            $csr = $this->csr->generateCsr(
                $device,
                $keys['resource']
            );


            // Register with Lekuka
            $response = $this->client->registerDevice(
                $device,
                $csr
            );

            $certificate = $response['certificate'];

            $certificatePath = $this->certificates->saveCertificate(
                $device,
                $certificate
            );

            $privateKeyPath = $this->certificates->savePrivateKey(
                $device,
                $keys['privateKey']
            );

            $thumbprint = $this->csr->thumbprint(
                $certificate
            );

            // Download server certificate
            $serverCertificate = $this->client->getServerCertificate($device);

            $this->certificates
                ->saveServerCertificate(
                    $serverCertificate
                );

            // Download configuration
            $this->configuration
                ->refresh($device);

            // Update device
            $device->update([

                'certificate_path' => $certificatePath,

                'private_key_path' => $privateKeyPath,

                'thumbprint' => $thumbprint,

                'registered' => true,

                'registered_at' => now(),

            ]);

            return $device;
        });
    }

}