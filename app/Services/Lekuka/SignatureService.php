<?php

namespace App\Services\Lekuka;

use App\Models\LekukaDevice;
use RuntimeException;

class SignatureService
{
    public function __construct(
        protected CertificateService $certificates
    ) {
    }

    /**
     * Generate SHA-256 hash (Base64 encoded).
     */
    public function hash(string $data): string
    {
        return base64_encode(hash('sha256', $data, true));
    }

    /**
     * Sign canonical data with the device private key.
     */
    public function sign(
        LekukaDevice $device,
        string $canonicalData
    ): array {

        $privateKeyPath = $this->certificates
            ->getPrivateKeyPath($device);

        $privateKey = openssl_pkey_get_private(
            file_get_contents($privateKeyPath)
        );

        if (! $privateKey) {
            throw new RuntimeException(
                'Unable to load device private key.'
            );
        }

        $signature = '';

        $success = openssl_sign(
            $canonicalData,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );

        openssl_free_key($privateKey);

        if (! $success) {
            throw new RuntimeException(
                'Unable to generate device signature.'
            );
        }

        return [

            'hash' => $this->hash($canonicalData),

            'signature' => base64_encode($signature),

            'certificateThumbprint' => $device->thumbprint,

        ];
    }

    /**
     * Verify a signature.
     */
    public function verify(
        string $canonicalData,
        string $signature,
        string $certificate
    ): bool {

        $publicKey = openssl_pkey_get_public($certificate);

        return openssl_verify(
            $canonicalData,
            base64_decode($signature),
            $publicKey,
            OPENSSL_ALGO_SHA256
        ) === 1;
    }
}