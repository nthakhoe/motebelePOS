<?php

namespace App\Services\Lekuka;

use App\Models\LekukaDevice;
use RuntimeException;

class CsrService
{
    /**
     * Generate an ECC P-256 private key.
     */
    public function generateKeyPair(): array
    {
        $configFile = 'C:\\xampp\\php\\extras\\ssl\\openssl.cnf';

        if (! file_exists($configFile)) {
            throw new RuntimeException("OpenSSL config not found: {$configFile}");
        }

        putenv("OPENSSL_CONF={$configFile}");

        $config = [
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name'       => 'prime256v1',
            'config'           => $configFile,
        ];

        $key = openssl_pkey_new($config);

        if ($key === false) {
            while ($error = openssl_error_string()) {
                logger()->error($error);
                $this->output?->writeln($error);
            }

            throw new RuntimeException('Unable to generate private key.');
        }

        if (! openssl_pkey_export($key, $privateKey, null, [
            'config' => $configFile,
        ])) {
            throw new RuntimeException('Unable to export private key.');
        }

        return [
            'resource'   => $key,
            'privateKey' => $privateKey,
        ];
    }

    /**
     * Generate CSR.
     */
    public function generateCsr(
        LekukaDevice $device,
        $privateKey
    ): string {

        $commonName = sprintf(
            'RSL-%s-%010d',
            $device->serial_number,
            $device->device_id
        );

        $dn = [
            'countryName'       => 'LS',
            'organizationName'  => 'Revenue Services Lesotho',
            'stateOrProvinceName' => 'Lesotho',
            'commonName'        => $commonName,
        ];

        $configFile = 'C:\\xampp\\php\\extras\\ssl\\openssl.cnf';

        $options = [
            'digest_alg' => 'sha256',
            'config'     => $configFile,
        ];

        $csr = openssl_csr_new(
            $dn,
            $privateKey,
            $options
        );

        if (! $csr) {
            throw new RuntimeException(
                'Unable to generate CSR.'
            );
        }

        openssl_csr_export(
            $csr,
            $csrPem
        );

        return $csrPem;
    }

    /**
     * Calculate SHA1 certificate thumbprint.
     */
    public function thumbprint(string $certificate): string
    {
        $fingerprint = openssl_x509_fingerprint($certificate, 'sha1');

        if ($fingerprint === false) {
            while ($error = openssl_error_string()) {
                logger()->error($error);
            }

            throw new \RuntimeException('Unable to calculate certificate thumbprint.');
        }

        return strtolower(str_replace(':', '', $fingerprint));
    }

    /**
     * Read certificate information.
     */
    public function parseCertificate(
        string $certificate
    ): array {

        return openssl_x509_parse($certificate);
    }
}