<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LekukaDevice;
use App\Services\Lekuka\DeviceService;

class RegisterLekukaDevice extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'lekuka:register-device {device}';

    /**
     * The console command description.
     */
    protected $description = 'Register a Lekuka fiscal device with RSL';

    /**
     * Execute the console command.
     */
    public function handle(DeviceService $deviceService): int
    {
        try {
            $device = LekukaDevice::findOrFail($this->argument('device'));

            $this->info("Registering device {$device->device_id}...");

            $deviceService->register($device);

            $this->info('Device registered successfully.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}