<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawMaterialStock extends Model
{
    use HasFactory;

    protected $table = 'raw_material_stocks';

    protected $fillable = [
        'raw_material_id',
        'warehouse_type',
        'sacos_count',
        'total_kg',
    ];

    protected $casts = [
        'sacos_count' => 'decimal:2',
        'total_kg'    => 'decimal:4',
    ];

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }

    /**
     * Helper para obtener o inicializar stock de un material en un almacén
     */
    public static function getOrCreate(int $materialId, string $warehouseType = 'principal'): self
    {
        return self::firstOrCreate(
            ['raw_material_id' => $materialId, 'warehouse_type' => $warehouseType],
            ['sacos_count' => 0.00, 'total_kg' => 0.0000]
        );
    }
}
