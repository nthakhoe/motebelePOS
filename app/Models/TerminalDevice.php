<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TerminalDevice extends Model
{
    protected $guarded = [];

    public function terminal()
    {
        return $this->belongsTo(Terminal::class);
    }
}
