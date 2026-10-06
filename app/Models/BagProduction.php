<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BagProduction extends Model
{
    use HasFactory;

    protected $table = 'bag_productions';

    protected $fillable = [
        'bag_shift_id',
        'user_id',
        'product_id',
        'machine_id',
        'quantity',
        'weight',
        'recorded_at',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'original_weight',
        'qr_code',
        'lifted_by',
        'lifted_at',
        'jspos_production_id',
        'sync_id',
        'metadata',
        'weight_quality_grade',
        'weight_deviation_percent',
        'completed_packages_count',
        'fractional_units',
        'is_package_completed',
        'labor_earned_amount',
        'labor_retained_amount',
        'completed_by_production_id',
        'is_printed',
        'printed_at',
    ];

    protected $casts = [
        'recorded_at'              => 'datetime',
        'reviewed_at'              => 'datetime',
        'lifted_at'                => 'datetime',
        'printed_at'               => 'datetime',
        'is_printed'               => 'boolean',
        'quantity'                 => 'decimal:2',
        'weight'                   => 'decimal:4',
        'original_weight'          => 'decimal:4',
        'weight_deviation_percent' => 'decimal:2',
        'completed_packages_count' => 'decimal:2',
        'fractional_units'         => 'decimal:2',
        'is_package_completed'     => 'boolean',
        'labor_earned_amount'      => 'decimal:2',
        'labor_retained_amount'    => 'decimal:2',
        'metadata'                 => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($production) {
            if (empty($production->qr_code)) {
                $production->qr_code = 'PKG-' . strtoupper(Str::random(10));
            }
            if (empty($production->weight_quality_grade)) {
                $production->weight_quality_grade = 'B';
            }
        });
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(BagShift::class, 'bag_shift_id');
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(BagMachine::class, 'machine_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function lifter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lifted_by');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(BagProduct::class, 'product_id');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(BagMachineIncident::class, 'production_id');
    }

    /**
     * Resolve machine with fallback to shift machine.
     */
    public function getEffectiveMachineAttribute()
    {
        return $this->machine ?? $this->shift?->machine;
    }

    public function labelPrints(): HasMany
    {
        return $this->hasMany(BagLabelPrint::class, 'production_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(BagProduction::class, 'completed_by_production_id');
    }

    /**
     * Discrete Batch Code: L{ymd}-{machineCode}-{grade} (e.g. L260910-EXT01-B).
     */
    public function getEffectiveBatchCodeAttribute(): string
    {
        $dateStr = ($this->recorded_at ?? now())->format('ymd');
        $machineCode = $this->effective_machine?->code ?? 'PL01';
        $grade = strtoupper($this->weight_quality_grade ?: 'B');

        return "L{$dateStr}-{$machineCode}-{$grade}";
    }

    /**
     * Weight Quality Badge Label.
     */
    public function getWeightGradeLabelAttribute(): string
    {
        $grade = strtoupper($this->weight_quality_grade ?: 'B');
        $dev = $this->weight_deviation_percent !== null ? (float)$this->weight_deviation_percent : 0.0;
        $sign = $dev > 0 ? '+' : '';

        return match ($grade) {
            'A'     => "Grado A: Sobrepeso ({$sign}{$dev}%)",
            'C'     => "Grado C: Subcalibre ({$sign}{$dev}%)",
            default => "Grado B: Peso Óptimo ({$sign}{$dev}%)",
        };
    }

    /**
     * Collaborative fraction completion: releases retained labor amount.
     */
    public function completeFractionWith(BagProduction $completingProduction): void
    {
        $this->update([
            'is_package_completed'       => true,
            'labor_retained_amount'      => 0.00,
            'completed_by_production_id' => $completingProduction->id,
        ]);
    }

    /**
     * Product name accessor fallback.
     */
    public function getProductNameAttribute(): string
    {
        return $this->product?->name ?? 'Bolsa';
    }

    /**
     * Recalculate labor amounts, fraction breakdown, and weight quality grading.
     */
    public function recalculateLaborAndQuality(): void
    {
        $product = $this->product;
        $user = $this->user;
        if (!$product || !$user) return;

        $qty = (float)$this->quantity;
        $weight = (float)$this->weight;

        $breakdown = $product->calculateBreakdown($qty);
        $completedCount = (float)$breakdown['completed_packages'];
        $fractionalUnits = (float)$breakdown['fractional_units'];
        $isCompleted = (bool)$breakdown['is_package_completed'];

        $grading = $product->calculateWeightQualityGrade($weight, $completedCount, $fractionalUnits);
        $grade = $grading['grade'];
        $devPercent = $grading['deviation_percent'];

        $tariffs = $user->calculateLaborTariff($product);
        $packageTariff = (float)$tariffs['package_tariff'];
        $fractionTariff = (float)$tariffs['fraction_tariff'];

        if ($user->pay_partial_packages) {
            $laborEarned = round(($completedCount * $packageTariff) + ($fractionalUnits * $fractionTariff), 2);
            $laborRetained = 0.00;
        } else {
            $earnedForCompleted = round($completedCount * $packageTariff, 2);
            $retainedForFraction = round($fractionalUnits * $fractionTariff, 2);
            $laborEarned = round($earnedForCompleted + $retainedForFraction, 2);
            $laborRetained = $retainedForFraction;
        }

        $meta = is_array($this->metadata) ? $this->metadata : (json_decode($this->metadata ?? '', true) ?: []);
        $meta['snapshot'] = [
            'product_name'            => $product->name,
            'sku'                     => $product->sku,
            'unit_weight_kg'          => (float)($product->unit_weight_kg ?? 0),
            'millar_per_bulto'        => (float)($product->millar_per_bulto ?? 1),
            'target_units_per_shift'  => (int)($product->target_units_per_shift ?? 5),
            'cost_per_kg_snapshot'    => (float)$product->getEffectivePricePerKg(),
            'factory_price_snapshot'  => (float)($product->price ?? 0),
            'applied_daily_salary'    => (float)$user->daily_salary,
            'applied_package_tariff'  => $packageTariff,
            'applied_fraction_tariff' => $fractionTariff,
            'recalculated_at'         => now()->toDateTimeString(),
        ];

        $this->update([
            'completed_packages_count' => $completedCount,
            'fractional_units'         => $fractionalUnits,
            'is_package_completed'     => $isCompleted,
            'weight_quality_grade'     => $grade,
            'weight_deviation_percent' => $devPercent,
            'labor_earned_amount'      => $laborEarned,
            'labor_retained_amount'    => $laborRetained,
            'metadata'                 => $meta,
        ]);
    }

    /**
     * Scope for items approved in factory and ready for JSPOS warehouse lifting.
     */
    public function scopeReadyForLifting($query)
    {
        return $query->where('status', 'approved')->whereNull('lifted_at');
    }

    /**
     * Check if production is locked for regular operators (printed / physically stickered).
     */
    public function getIsLockedForOperatorAttribute(): bool
    {
        return (bool)$this->is_printed;
    }
}
