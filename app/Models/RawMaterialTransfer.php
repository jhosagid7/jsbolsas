<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawMaterialTransfer extends Model
{
    use HasFactory;

    protected $table = 'raw_material_transfers';

    protected $fillable = [
        'raw_material_id',
        'from_warehouse',
        'to_warehouse',
        'sacos_transferred',
        'weight_kg',
        'transferred_by',
        'received_by',
        'transferred_at',
        'notes',
    ];

    protected $casts = [
        'sacos_transferred' => 'decimal:2',
        'weight_kg'         => 'decimal:4',
        'transferred_at'    => 'datetime',
    ];

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }

    public function transferredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
