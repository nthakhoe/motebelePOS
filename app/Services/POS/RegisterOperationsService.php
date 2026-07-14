<?php

namespace App\Services\POS;

use App\Models\RegisterSession;
use App\Models\Terminal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterOperationsService
{
    public function openRegister(...){}

    public function closeRegister(...){}

    public function suspendRegister(...){}

    public function resumeRegister(...){}

    public function activeSession(...){}

    public function hasOpenSession(...){}

    public function calculateExpectedCash(...){}

    public function calculateVariance(...){}
}