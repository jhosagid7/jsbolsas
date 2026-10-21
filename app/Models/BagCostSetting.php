<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BagCostSetting extends Model
{
    use HasFactory;

    protected $table = 'bag_cost_settings';

    protected $fillable = [
        'resin_price_per_kg',
        'shift_fixed_cost',
        'daily_profit_target',
        'margin_40_multiplier',
        'margin_45_multiplier',
        'margin_50_multiplier',
        'margin_60_multiplier',
        'tier1_multiplier',
        'tier2_multiplier',
        'tier3_multiplier',
    ];

    protected $casts = [
        'resin_price_per_kg'   => 'decimal:4',
        'shift_fixed_cost'     => 'decimal:4',
        'daily_profit_target'  => 'decimal:4',
        'margin_40_multiplier' => 'decimal:2',
        'margin_45_multiplier' => 'decimal:2',
        'margin_50_multiplier' => 'decimal:2',
        'margin_60_multiplier' => 'decimal:2',
        'tier1_multiplier'     => 'decimal:2',
        'tier2_multiplier'     => 'decimal:2',
        'tier3_multiplier'     => 'decimal:2',
    ];

    public static function getSettings(): self
    {
        $existing = self::first();
        if ($existing) {
            return $existing;
        }

        return self::create([
            'resin_price_per_kg'   => 1.4000,
            'shift_fixed_cost'     => 25.0000,
            'daily_profit_target'  => 100.0000,
            'margin_40_multiplier' => 1.40,
            'margin_45_multiplier' => 1.45,
            'margin_50_multiplier' => 1.50,
            'margin_60_multiplier' => 1.65,
            'tier1_multiplier'     => 1.10,
            'tier2_multiplier'     => 1.17,
            'tier3_multiplier'     => 1.21,
        ]);
    }
}
