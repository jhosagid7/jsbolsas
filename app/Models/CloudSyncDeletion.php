<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CloudSyncDeletion extends Model
{
    use HasFactory;

    protected $table = 'cloud_sync_deletions';

    protected $fillable = [
        'entity_type',
        'sync_id',
    ];
}
