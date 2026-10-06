<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BagMachine extends Model
{
    use HasFactory;

    protected $table = 'bag_machines';

    protected $fillable = [
        'name',
        'code',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function shifts()
    {
        return $this->hasMany(BagShift::class, 'machine_id');
    }

    public function directProductions()
    {
        return $this->hasMany(BagProduction::class, 'machine_id');
    }

    public function productions()
    {
        return $this->hasManyThrough(BagProduction::class, BagShift::class, 'machine_id', 'bag_shift_id');
    }

    public function incidents()
    {
        return $this->hasMany(BagMachineIncident::class, 'machine_id');
    }

    /**
     * Query all productions associated directly or through shift.
     */
    public function allProductionsQuery()
    {
        return BagProduction::query()->where(function ($query) {
            $query->where('bag_productions.machine_id', $this->id)
                ->orWhere(function ($q) {
                    $q->whereNull('bag_productions.machine_id')
                      ->whereHas('shift', function ($sq) {
                          $sq->where('machine_id', $this->id);
                      });
                });
        });
    }
}
