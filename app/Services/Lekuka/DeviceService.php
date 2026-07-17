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
        protected LekukaAuditService $audit,
    )
    {
    }

    public function register(LekukaDevice $device): void
    {
        DB::transaction(function () use ($device) {

            /*
             |--------------------------------------------------------------------------
             | Step 1: Verify Taxpayer
             |--------------------------------------------------------------------------
             */

            $verification = $this->verifyTaxpayer(
                $device->device_id,
                $device->activation_key,
                $device->serial_number
            );

            if (!($verification['success'] ?? false)) {
                throw new RuntimeException(
                    $verification['message']
                    ?? 'Taxpayer verification failed.'
                );
            }

            /*
             |--------------------------------------------------------------------------
             | Step 2: Register Device
             |--------------------------------------------------------------------------
             */

            $registration = $this->registerDevice(
                $device->device_id,
                [
                    'activationKey'        => $device->activation_key,
                    'deviceSerialNo'       => $device->serial_number,
                    'deviceModelName'      => $device->device_model,
                    'deviceModelVersionNo' => $device->device_model_version,
                ]
            );

            if (!($registration['success'] ?? false)) {
                throw new RuntimeException(
                    $registration['message']
                    ?? 'Device registration failed.'
                );
            }

            /*
             |--------------------------------------------------------------------------
             | Step 3: Download Server Certificate
             |--------------------------------------------------------------------------
             */

            $serverCertificate = $this->getServerCertificate();

            if (isset($serverCertificate['certificate'])) {

                Storage::disk('local')->put(
                    "lekuka/server/server.crt",
                    $serverCertificate['certificate']
                );
            }

            /*
             |--------------------------------------------------------------------------
             | Step 4: Store Device Certificate
             |--------------------------------------------------------------------------
             |
             | RegisterDevice normally returns the device certificate
             | and thumbprint.
             |
             */

            if (isset($registration['certificate'])) {

                Storage::disk('local')->put(
                    "lekuka/device/{$device->device_id}.crt",
                    $registration['certificate']
                );

                $certificatePath = $this->certificates->saveCertificate(
                    $device,
                    $registration['certificate']
                );
            }

            if (isset($registration['privateKey'])) {

                Storage::disk('local')->put(
                    "lekuka/device/{$device->device_id}.key",
                    $registration['privateKey']
                );

                $device->private_key_path =
                    storage_path(
                        "app/lekuka/device/{$device->device_id}.key"
                    );
            }

            /*
             |--------------------------------------------------------------------------
             | Step 5: Get Configuration
             |--------------------------------------------------------------------------
             */

            $configuration = $this->getConfig(
                $device->device_id
            );

            /*
             |--------------------------------------------------------------------------
             | Step 6: Update Device
             |--------------------------------------------------------------------------
             */
            // 1. Register the device with Lekuka
            $registration = $this->client->registerDevice($device);

            // 2. Download the server certificate (if this is a separate endpoint)
            $serverCertificate = $this->client->getServerCertificate();

              // 3. Save all certificates and keys
            $certificatePath = $this->certificates->saveCertificate(
                $device,
                $registration['certificate']
            );

            $privateKeyPath = $this->certificates->savePrivateKey(
                $device,
                $registration['privateKey']
            );

            $this->certificates->saveServerCertificate(
                $serverCertificate['certificate']
            );

            $this->certificates->saveMetadata($device, [
                'thumbprint'    => $registration['thumbprint'],
                'registered_at' => now()->toDateTimeString(),
            ]);

            // 4. Update the device record
            $device->update([
                'device_uid'          => $registration['deviceUid'],
                'certificate_path'    => $certificatePath,
                'private_key_path'    => $privateKeyPath,
                'certificate_thumbprint' => $registration['thumbprint'],
                'status'              => 'REGISTERED',
                'registered_at'       => now(),
            ]);

            $this->auditService->log(
                'DEVICE_REGISTERED',
                $device,
                [
                    'device_uid' => $registration['deviceUid'],
                ]
            );
        });
    }

    // Existing methods...

    public function verifyTaxpayer(...)
    {
        // Existing implementation
    }

    public function registerDevice(...)
    {
        // Existing implementation
    }

    public function getServerCertificate()
    {
        // Existing implementation
    }

    public function getConfig(...)
    {
        // Existing implementation
    }

    public function register(LekukaDevice $device): void
    {
        $correlationId = (string) Str::uuid();

        DB::transaction(function () use ($device, $correlationId) {

            // All audit logs use the same correlation ID

        });
    }
}