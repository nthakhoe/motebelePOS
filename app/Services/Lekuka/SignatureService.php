<?php

namespace App\Services\Lekuka;

use App\Models\LekukaDevice;
use Illuminate\Support\Facades\DB;
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
        $privateKeyPath = $this->certificates->getPrivateKeyPath($device);

        $privateKey = openssl_pkey_get_private(
            file_get_contents($privateKeyPath)
        );

        if (!$privateKey) {
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

        if (!$success) {
            throw new RuntimeException(
                'Unable to generate device signature.'
            );
        }

        return [
            'hash' => $this->hash($canonicalData),

            // Keep OpenSSL's native DER-encoded ECDSA signature.
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

        if (!$publicKey) {
            return false;
        }

        $decodedSignature = base64_decode($signature, true);

        if ($decodedSignature === false) {
            return false;
        }

        return openssl_verify(
            $canonicalData,
            $decodedSignature,
            $publicKey,
            OPENSSL_ALGO_SHA256
        ) === 1;
    }

    protected function rawToDerEcdsa(string $rawSignature): string
    {
        if (strlen($rawSignature) !== 64) {
            throw new RuntimeException(
                'Invalid raw ECDSA signature length.'
            );
        }

        $r = substr($rawSignature, 0, 32);
        $s = substr($rawSignature, 32, 32);

        // Remove unnecessary leading zeroes.
        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");

        // INTEGER must be positive.
        if ($r === '' || (ord($r[0]) & 0x80)) {
            $r = "\x00" . $r;
        }

        if ($s === '' || (ord($s[0]) & 0x80)) {
            $s = "\x00" . $s;
        }

        $rDer = "\x02" . $this->derLength(strlen($r)) . $r;
        $sDer = "\x02" . $this->derLength(strlen($s)) . $s;

        $sequence = $rDer . $sDer;

        return "\x30"
            . $this->derLength(strlen($sequence))
            . $sequence;
    }

    protected function derLength(int $length): string
    {
        if ($length < 128) {
            return chr($length);
        }

        $result = '';

        while ($length > 0) {
            $result = chr($length & 0xFF) . $result;
            $length >>= 8;
        }

        return chr(0x80 | strlen($result)) . $result;
    }
}