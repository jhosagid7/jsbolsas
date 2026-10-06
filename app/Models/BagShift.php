<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BagShift extends Model
{
    use HasFactory;

    protected $table = 'bag_shifts';

    protected $fillable = [
        'user_id',
        'machine_id',
        'shift_type',
        'start_time',
        'end_time',
        'status',
        'total_packages',
        'total_weight',
        'notes',
        'sync_id',
    ];

    protected $casts = [
        'start_time'     => 'datetime',
        'end_time'       => 'datetime',
        'total_packages' => 'decimal:2',
        'total_weight'   => 'decimal:4',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Resolve effective operator for the shift.
     * Prioritizes the operator associated with productions if shift was opened by admin or general user.
     */
    public function getEffectiveUserAttribute(): ?User
    {
        if ($this->relationLoaded('productions') && $this->productions->isNotEmpty()) {
            $firstUser = $this->productions->first()->user;
            if ($firstUser) {
                return $firstUser;
            }
        } elseif ($this->productions()->exists()) {
            $firstUser = $this->productions()->with('user')->first()?->user;
            if ($firstUser) {
                return $firstUser;
            }
        }

        return $this->user;
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(BagMachine::class, 'machine_id');
    }

    public function productions(): HasMany
    {
        return $this->hasMany(BagProduction::class, 'bag_shift_id');
    }

    /**
     * Duration in decimal hours (e.g. 8.5).
     */
    public function getDurationHoursAttribute(): float
    {
        $start = $this->start_time;
        if (!$start) return 0.0;
        $end = $this->end_time ?: now();
        return round(max(0, $start->diffInMinutes($end)) / 60.0, 2);
    }

    /**
     * Human-readable duration (e.g. 8h 30m or 4h 15m (En curso)).
     */
    public function getDurationHumanAttribute(): string
    {
        $start = $this->start_time;
        if (!$start) return '0h 00m';
        $end = $this->end_time ?: now();
        $totalMinutes = max(0, (int)$start->diffInMinutes($end));
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;
        $str = sprintf('%dh %02dm', $hours, $minutes);

        return $this->status === 'open' ? "{$str} (En curso)" : $str;
    }

    /**
     * Human-readable formatted start time.
     */
    public function getStartTimeHumanAttribute(): string
    {
        return $this->start_time ? $this->start_time->format('h:i A') : '--:--';
    }

    /**
     * Human-readable formatted end time.
     */
    public function getEndTimeHumanAttribute(): string
    {
        return $this->end_time ? $this->end_time->format('h:i A') : ($this->status === 'open' ? 'En Vivo' : '--:--');
    }

    /**
     * Proportional combined progress across multi-product sizes.
     * e.g. 50% Size A + 50% Size B = 100% Target Met.
     */
    public function getCombinedProgressPercentAttribute(): float
    {
        $prodGroups = $this->productions->groupBy('product_id');
        if ($prodGroups->isEmpty()) return 0.0;

        $accumulatedFraction = 0.0;
        foreach ($prodGroups as $prodId => $items) {
            $product = $items->first()->product;
            if (!$product) continue;
            $target = (float)($product->target_units_per_shift ?: 5);
            $qty = (float)$items->sum('quantity');
            if ($target > 0) {
                $accumulatedFraction += ($qty / $target);
            }
        }

        return round($accumulatedFraction * 100.0, 1);
    }

    public function getIsTargetMetAttribute(): bool
    {
        return $this->combined_progress_percent >= 100.0;
    }

    /**
     * Recalculate and update totals based on related productions.
     */
    public function recalculateTotals(): void
    {
        $this->total_packages = $this->productions()->sum('quantity') ?: 0;
        $this->total_weight = $this->productions()->sum('weight') ?: 0;
        $this->save();
    }

    /**
     * Recalculate financial breakdown (income, raw cost, fixed cost, net profit)
     */
    public function recalculateFinancials(): void
    {
        $settings = BagCostSetting::getSettings();
        $fixedCost = (float)$settings->shift_fixed_cost;

        $income = 0.0;
        $rawCost = 0.0;
        $targetUnits = 0.0;
        $totalQty = 0.0;
        $totalWeight = 0.0;
        $seenProductIds = [];

        foreach ($this->productions as $p) {
            $prod = $p->product;
            if (!$prod) continue;

            $qty = (float)$p->quantity;
            $weight = (float)$p->weight;
            $totalQty += $qty;
            $totalWeight += $weight;

            if (!in_array($prod->id, $seenProductIds)) {
                $targetUnits += (float)($prod->target_units_per_shift ?: 5);
                $seenProductIds[] = $prod->id;
            }

            $unitPrice = (float)($prod->price > 0 ? $prod->price : $prod->simulateFactoryPriceFromDailyTarget());
            if ($prod->is_variable_quantity) {
                $income += ($weight * $unitPrice);
                $rawCost += ($prod->calculateRawMaterialCost() * $weight);
            } else {
                $income += ($qty * $unitPrice);
                $rawCost += ($qty * $prod->calculateRawMaterialCost());
            }
        }

        $totalCost = $rawCost + $fixedCost;
        $netProfit = $income - $totalCost;
        $margin = $income > 0 ? round(($netProfit / $income) * 100, 2) : 0.0;

        $this->total_packages = $totalQty > 0 ? $totalQty : (float)$this->total_packages;
        $this->total_weight = $totalWeight > 0 ? $totalWeight : (float)$this->total_weight;
        $this->total_income = $income;
        $this->total_production_cost = $totalCost;
        $this->fixed_operational_cost = $fixedCost;
        $this->net_profit = $netProfit;
        $this->profit_margin_percent = $margin;
        $this->target_packages = $targetUnits > 0 ? $targetUnits : 5.0;
    }
}
