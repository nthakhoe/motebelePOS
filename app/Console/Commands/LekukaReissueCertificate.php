<?php

namespace App\Console\Commands;

use App\Models\LekukaDevice;
use App\Services\Lekuka\CertificateService;
use App\Services\Lekuka\CsrService;
use App\Services\Lekuka\LekukaClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class LekukaReissueCertificate extends Command
{
    protected $signature = 'lekuka:reissue-certificate {deviceId=1180}';
    protected $description = 'Clean re-issue of Lekuka device certificate using the same method as registration';

    public function handle(
        LekukaClient $client,
        CsrService $csrService,
        CertificateService $certificateService
    ) {
        $lekukaDeviceId = (int) $this->argument('deviceId');

        $device = LekukaDevice::where('device_id', $lekukaDeviceId)->firstOrFail();

        $this->info("Re-issuing certificate for Lekuka device ID: {$lekukaDeviceId}");

        // -------------------------------------------------
        // 1. Generate new key pair + CSR (same as registration)
        // -------------------------------------------------
        $keys = $csrService->generateKeyPair();
        $csrPem = $csrService->generateCsr($device, $keys['resource']);

        $this->info('New private key and CSR generated.');
        $this->line('CN: RSL-' . $device->serial_number . '-' . str_pad($lekukaDeviceId, 10, '0', STR_PAD_LEFT));

        // -------------------------------------------------
        // 2. Call issueCertificate (two-step process)
        // -------------------------------------------------
        $response = $client->securePost(
            device: $device,
            endpoint: "/Device/v2/{$lekukaDeviceId}/IssueCertificate",
            payload: [
                'certificateRequest' => $csrPem,
                'twoStepsProcess'    => true,
            ],
            action: 'ISSUE_CERTIFICATE',
            correlationId: (string) Str::uuid(),
        );

        $response->throw();
        $data = $response->json();

        if (empty($data['certificate'])) {
            $this->error('Lekuka did not return a certificate.');
            return 1;
        }

        $certificate = $data['certificate'];
        $this->info('New certificate received from Lekuka.');

        // -------------------------------------------------
        // 3. Save certificate + private key
        // -------------------------------------------------
        $certificatePath = $certificateService->saveCertificate($device, $certificate);
        $privateKeyPath  = $certificateService->savePrivateKey($device, $keys['privateKey']);

        // -------------------------------------------------
        // 4. Calculate thumbprint
        // -------------------------------------------------
        $thumbprint = $csrService->thumbprint($certificate);
        $this->info("New thumbprint: {$thumbprint}");

        // -------------------------------------------------
        // 5. Confirm the certificate
        // -------------------------------------------------
        // Lekuka expects the thumbprint as Binary(20).
        // We send it as raw binary (hex2bin).
        // -------------------------------------------------
        // 5. Confirm the certificate
        // -------------------------------------------------
        $confirmResponse = $client->securePost(
            device: $device,
            endpoint: "/Device/v2/{$lekukaDeviceId}/ConfirmCertificate",
            payload: [
                // Binary(20) → base64 encoded
                'thumbprint' => base64_encode(hex2bin($thumbprint)),
            ],
            action: 'CONFIRM_CERTIFICATE',
            correlationId: (string) Str::uuid(),
        );

        $confirmResponse->throw();
        $this->info('Certificate confirmed successfully.');

        // -------------------------------------------------
        // 6. Update the device record
        // -------------------------------------------------
        $device->update([
            'certificate_path' => $certificatePath,
            'private_key_path' => $privateKeyPath,
            'thumbprint'       => $thumbprint,
        ]);

        $this->info('Database updated.');
        $this->info('Certificate re-issue completed successfully.');
        $this->warn('You can now try closing the fiscal day again.');

        return 0;
    }
}