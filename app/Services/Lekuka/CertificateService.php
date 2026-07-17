<?php

namespace App\Services\Lekuka;

use App\Models\LekukaDevice;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CertificateService
{
    protected string $disk = 'local';

    /*
    |--------------------------------------------------------------------------
    | Device Paths
    |--------------------------------------------------------------------------
    */

    protected function deviceFolder(LekukaDevice $device): string
    {
        return "lekuka/devices/{$device->device_id}";
    }

    protected function certificateFile(LekukaDevice $device): string
    {
        return $this->deviceFolder($device).'/device.crt';
    }

    protected function privateKeyFile(LekukaDevice $device): string
    {
        return $this->deviceFolder($device).'/private.key';
    }

    protected function metadataFile(LekukaDevice $device): string
    {
        return $this->deviceFolder($device).'/metadata.json';
    }

    /*
    |--------------------------------------------------------------------------
    | Device Certificate
    |--------------------------------------------------------------------------
    */

    public function saveCertificate(
        LekukaDevice $device,
        string $certificate
    ): string {

        Storage::disk($this->disk)->put(
            $this->certificateFile($device),
            $certificate
        );

        return Storage::disk($this->disk)
            ->path($this->certificateFile($device));
    }

    public function getCertificatePath(
        LekukaDevice $device
    ): string {

        $path = Storage::disk($this->disk)
            ->path($this->certificateFile($device));

        if (!file_exists($path)) {
            throw new RuntimeException(
                'Device certificate not found.'
            );
        }

        return $path;
    }

    /*
    |--------------------------------------------------------------------------
    | Private Key
    |--------------------------------------------------------------------------
    */

    public function savePrivateKey(
        LekukaDevice $device,
        string $privateKey
    ): string {

        Storage::disk($this->disk)->put(
            $this->privateKeyFile($device),
            $privateKey
        );

        return Storage::disk($this->disk)
            ->path($this->privateKeyFile($device));
    }

    public function getPrivateKeyPath(
        LekukaDevice $device
    ): string {

        $path = Storage::disk($this->disk)
            ->path($this->privateKeyFile($device));

        if (!file_exists($path)) {
            throw new RuntimeException(
                'Private key not found.'
            );
        }

        return $path;
    }

    /*
    |--------------------------------------------------------------------------
    | Server Certificate
    |--------------------------------------------------------------------------
    */

    public function saveServerCertificate(
        string $certificate
    ): string {

        Storage::disk($this->disk)->put(
            'lekuka/server/server.crt',
            $certificate
        );

        return Storage::disk($this->disk)
            ->path('lekuka/server/server.crt');
    }

    public function getServerCertificatePath(): string
    {
        $path = Storage::disk($this->disk)
            ->path('lekuka/server/server.crt');

        if (!file_exists($path)) {
            throw new RuntimeException(
                'Server certificate missing.'
            );
        }

        return $path;
    }

    /*
    |--------------------------------------------------------------------------
    | Metadata
    |--------------------------------------------------------------------------
    */

    public function saveMetadata(
        LekukaDevice $device,
        array $metadata
    ): void {

        Storage::disk($this->disk)->put(
            $this->metadataFile($device),
            json_encode(
                $metadata,
                JSON_PRETTY_PRINT
            )
        );
    }

    public function getMetadata(
        LekukaDevice $device
    ): array {

        if (
            !Storage::disk($this->disk)
                ->exists($this->metadataFile($device))
        ) {
            return [];
        }

        return json_decode(
            Storage::disk($this->disk)
                ->get($this->metadataFile($device)),
            true
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    public function isRegistered(
        LekukaDevice $device
    ): bool {

        return
            Storage::disk($this->disk)
                ->exists($this->certificateFile($device))
            &&
            Storage::disk($this->disk)
                ->exists($this->privateKeyFile($device));
    }

    /*
    |--------------------------------------------------------------------------
    | Remove Device
    |--------------------------------------------------------------------------
    */

    public function remove(
        LekukaDevice $device
    ): void {

        Storage::disk($this->disk)
            ->deleteDirectory(
                $this->deviceFolder($device)
            );
    }
}