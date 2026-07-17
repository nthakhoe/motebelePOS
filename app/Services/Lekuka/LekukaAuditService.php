<?php

namespace App\Services\Lekuka;

use App\Models\LekukaAuditLog;
use Illuminate\Support\Str;

class LekukaAuditService
{
    public function log(array $data): LekukaAuditLog
    {
        $data['correlation_id'] ??= (string) Str::uuid();

        return LekukaAuditLog::create($data);
    }
}