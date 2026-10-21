<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawMaterialAudit extends Model
{
    use HasFactory;

    protected $table = 'raw_material_audits';

    protected $fillable = [
        'sync_id',
        'audit_date',
        'period_start',
        'period_end',
        'initial_plant_kg',
        'transferred_to_plant_kg',
        'final_counted_plant_kg',
        'consumed_kg',
        'good_production_kg',
        'waste_purga_kg',
        'variance_kg',
        'variance_percentage',
        'status',
        'audited_by',
        'notes',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($audit) {
            if (empty($audit->sync_id)) {
                $audit->sync_id = 'AUDIT-' . (string)\Illuminate\Support\Str::uuid();
            }
        });
    }

    protected $casts = [
        'audit_date'              => 'date',
        'period_start'            => 'date',
        'period_end'              => 'date',
        'initial_plant_kg'        => 'decimal:4',
        'transferred_to_plant_kg' => 'decimal:4',
        'final_counted_plant_kg'  => 'decimal:4',
        'consumed_kg'             => 'decimal:4',
        'good_production_kg'      => 'decimal:4',
        'waste_purga_kg'          => 'decimal:4',
        'variance_kg'             => 'decimal:4',
        'variance_percentage'     => 'decimal:2',
    ];

    public function auditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'audited_by');
    }

    public function auditedByUser(): BelongsTo
    {
        return $this->auditor();
    }
}
