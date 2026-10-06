<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BagMachineIncident extends Model
{
    use HasFactory;

    protected $table = 'bag_machine_incidents';

    protected $fillable = [
        'machine_id',
        'production_id',
        'product_id',
        'user_id',
        'incident_type',
        'severity',
        'title',
        'description',
        'batch_code',
        'root_cause_analysis',
        'status',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(BagMachine::class, 'machine_id');
    }

    public function production(): BelongsTo
    {
        return $this->belongsTo(BagProduction::class, 'production_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(BagProduct::class, 'product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
