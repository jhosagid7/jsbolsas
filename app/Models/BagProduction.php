<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BagProduction extends Model
{
    use HasFactory;

    protected $table = 'bag_productions';

    protected $fillable = [
        'bag_shift_id',
        'user_id',
        'product_id',
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
    ];

    protected $casts = [
        'recorded_at'     => 'datetime',
        'reviewed_at'     => 'datetime',
        'lifted_at'       => 'datetime',
        'quantity'        => 'decimal:2',
        'weight'          => 'decimal:4',
        'original_weight' => 'decimal:4',
        'metadata'        => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($production) {
            if (empty($production->qr_code)) {
                $production->qr_code = 'PKG-' . strtoupper(Str::random(10));
            }
        });
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(BagShift::class, 'bag_shift_id');
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

    /**
     * Machine through the shift relationship.
     */
    public function getMachineAttribute()
    {
        return $this->shift?->machine;
    }

    /**
     * Scope for items approved in factory and ready for JSPOS warehouse lifting.
     */
    public function scopeReadyForLifting($query)
    {
        return $query->where('status', 'approved')->whereNull('lifted_at');
    }
}
