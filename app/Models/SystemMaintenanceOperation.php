<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemMaintenanceOperation extends Model
{
    protected $fillable = [
        'operation',
        'status',
        'file_name',
        'operation_key',
        'message',
        'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }
}
