<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CloudSyncLog extends Model
{
    use HasFactory;

    protected $table = 'cloud_sync_logs';

    protected $fillable = [
        'node_role',
        'direction',
        'status',
        'records_pushed',
        'records_pulled',
        'details',
        'error_message',
        'synced_at',
    ];

    protected $casts = [
        'details'   => 'array',
        'synced_at' => 'datetime',
    ];
}
