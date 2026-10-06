<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BagProduct extends Model
{
    use HasFactory;

    protected $table = 'bag_products';

    protected $fillable = [
        'name',
        'category',
        'production_formula_id',
        'sale_unit',
        'sku',
        'millar_per_bulto',
        'width_inch',
        'length_inch',
        'gauge_caliber',
        'unit_weight_kg',
        'real_total_weight_kg',
        'margin_percentage',
        'cost',
        'price',
        'price_tier_1',
        'price_tier_2',
        'price_tier_3',
        'target_units_per_shift',
        'target_daily_profit',
        'is_variable_quantity',
        'is_composite_rolls',
        'suggested_rolls_per_package',
        'is_active',
    ];

    protected $casts = [
        'production_formula_id'       => 'integer',
        'millar_per_bulto'            => 'decimal:4',
        'width_inch'                  => 'decimal:2',
        'length_inch'                 => 'decimal:2',
        'gauge_caliber'               => 'decimal:4',
        'unit_weight_kg'              => 'decimal:4',
        'real_total_weight_kg'        => 'decimal:4',
        'margin_percentage'           => 'decimal:2',
        'cost'                        => 'decimal:4',
        'price'                       => 'decimal:4',
        'price_tier_1'                => 'decimal:4',
        'price_tier_2'                => 'decimal:4',
        'price_tier_3'                => 'decimal:4',
        'target_units_per_shift'      => 'integer',
        'target_daily_profit'         => 'decimal:4',
        'is_variable_quantity'        => 'boolean',
        'is_composite_rolls'          => 'boolean',
        'suggested_rolls_per_package' => 'integer',
        'is_active'                   => 'boolean',
    ];

    protected $appends = [
        'theoretical_weight_kg',
        'real_total_weight_kg',
    ];

    public function formula(): BelongsTo
    {
        return $this->belongsTo(ProductionFormula::class, 'production_formula_id');
    }

    public function productions(): HasMany
    {
        return $this->hasMany(BagProduction::class, 'product_id');
    }

    /**
     * Accesor para Peso Teórico por Millar (PESO = Ancho x Largo x Calibre)
     */
    public function getTheoreticalWeightKgAttribute(): float
    {
        if ($this->is_variable_quantity) {
            return 1.0000;
        }
        return $this->calculatePhysicalWeight();
    }

    /**
     * Accesor para Peso Real por Unidad de Venta (PESO_R)
     */
    public function getRealTotalWeightKgAttribute(): float
    {
        return $this->calculateRealTotalWeight();
    }

    /**
     * Retornar Calibre con 4 decimales
     */
    public function getGaugeCaliberFormattedAttribute(): string
    {
        return number_format((float)($this->gauge_caliber ?? 0), 4, '.', '');
    }

    /**
     * Retornar Peso Teórico con 3 decimales para la Web
     */
    public function getTheoreticalWeightKgWebAttribute(): string
    {
        return number_format($this->getTheoreticalWeightKgAttribute(), 3, '.', '');
    }

    /**
     * Retornar Peso Real con 3 decimales para la Web
     */
    public function getRealTotalWeightKgWebAttribute(): string
    {
        return number_format($this->getRealTotalWeightKgAttribute(), 3, '.', '');
    }

    /**
     * 1. Peso Físico Unitario del Millar Teórico: PESO = ANCHO * LARGO * CALIBRE (o 1.0000 para Bobinas)
     */
    public function calculatePhysicalWeight(): float
    {
        if ($this->is_variable_quantity) {
            return 1.0000;
        }

        $w = (float)($this->width_inch ?? 0);
        $l = (float)($this->length_inch ?? 0);
        $c = (float)($this->gauge_caliber ?? 0);

        if ($w > 0 && $l > 0 && $c > 0) {
            return round($w * $l * $c, 4);
        }

        return (float)($this->unit_weight_kg ?? 0);
    }

    /**
     * 2. Peso Real por Unidad de Venta (PESO_R) con Fórmula de Escala Universal (V3):
     * PESO_R = (ANCHO * LARGO * CALIBRE) * millar_per_bulto
     * - Si es Bobina / Variable -> 1.0000
     * - Si tiene Override Manual en BD -> Respeta el valor manual
     * - Factor de Escala = millar_per_bulto (1.0 para 1 millar, 20.0 para bulto de 20 mil, 0.1 para paquete de 100 bolsas, etc.)
     */
    public function calculateRealTotalWeight(bool $forceCalculated = false): float
    {
        if ($this->is_variable_quantity) {
            return 1.0000;
        }

        // Si tiene un override manual guardado y no se fuerza cálculo
        if (!$forceCalculated && (float)($this->real_total_weight_kg ?? 0) > 0) {
            return (float)$this->real_total_weight_kg;
        }

        $pesoTeoricoMillar = $this->calculatePhysicalWeight();
        $factorEscala = (float)($this->millar_per_bulto > 0 ? $this->millar_per_bulto : 1.0);

        if ($pesoTeoricoMillar > 0) {
            return round($pesoTeoricoMillar * $factorEscala, 4);
        }

        return (float)($this->unit_weight_kg > 0 ? $this->unit_weight_kg : 1.0000);
    }

    /**
     * 3. Precio $/KG traído dinámicamente de la Fórmula de Preparación del Módulo 2
     */
    public function getEffectivePricePerKg(?float $fallbackResinPrice = null): float
    {
        if ($this->production_formula_id && $this->formula && $this->formula->currentVersion) {
            $fCost = (float)$this->formula->currentVersion->cost_per_kg;
            if ($fCost > 0) {
                return $fCost;
            }
        }

        if (is_null($fallbackResinPrice)) {
            $fallbackResinPrice = (float)BagCostSetting::getSettings()->resin_price_per_kg;
        }

        return $fallbackResinPrice;
    }

    /**
     * 4. Costo de Materia Prima por Unidad de Venta: COSTO = PESO_R * $/KG
     */
    public function calculateRawMaterialCost(?float $resinPrice = null): float
    {
        $priceKg = $this->getEffectivePricePerKg($resinPrice);
        $pesoR = $this->calculateRealTotalWeight();
        return round($pesoR * $priceKg, 4);
    }

    /**
     * 5. Márgenes de Referencia Informativos del Excel
     */
    public function calculateReferenceMargins(?float $costo = null): array
    {
        if (is_null($costo)) {
            $costo = $this->calculateRawMaterialCost();
        }

        return [
            'm_40' => round($costo * 1.40, 2),
            'm_45' => round($costo * 1.45, 2),
            'm_50' => round($costo * 1.50, 2),
            'm_60' => round($costo * 1.65, 2),
            'm_2'  => round($costo * 1.73, 2),
            'm_1'  => round($costo * 2.00, 2),
        ];
    }

    /**
     * 6. Calculador Inverso de Precio Fábrica: FABRICA = COSTO + (UtilidadDeseada / PRODUCCION)
     */
    public function simulateFactoryPriceFromDailyTarget(?float $targetDailyProfit = null, ?int $targetUnits = null, ?float $costo = null): float
    {
        if (is_null($costo)) {
            $costo = $this->calculateRawMaterialCost();
        }

        $profit = (float)($targetDailyProfit ?? ($this->target_daily_profit ?: BagCostSetting::getSettings()->daily_profit_target ?: 105.00));
        $units = (int)($targetUnits ?? ($this->target_units_per_shift ?: 5));

        if ($units <= 0) {
            $units = 1;
        }

        return round($costo + ($profit / $units), 2);
    }

    /**
     * 7. Utilidad Diaria Proyectada: UTIL/DIA = (FABRICA - COSTO) * PRODUCCION
     */
    public function calculateDailyProfitFromFactoryPrice(?float $factoryPrice = null, ?int $targetUnits = null, ?float $costo = null): float
    {
        if (is_null($costo)) {
            $costo = $this->calculateRawMaterialCost();
        }

        if (is_null($factoryPrice)) {
            $factoryPrice = (float)($this->price > 0 ? $this->price : $this->simulateFactoryPriceFromDailyTarget());
        }

        $units = (int)($targetUnits ?? ($this->target_units_per_shift ?: 5));

        return round(($factoryPrice - $costo) * $units, 2);
    }

    /**
     * 8. Escala de Precios de Venta:
     * - PRECIO I (Distribuidor):   FABRICA * 1.10 (+10%)
     * - PRECIO II (Mayorista):     FABRICA * 1.17 (+17%)
     * - PRECIO III (Minorista):    FABRICA * 1.21 (+21%)
     */
    public function calculateTiersFromFactoryPrice(?float $factoryPrice = null): array
    {
        if (is_null($factoryPrice)) {
            $factoryPrice = (float)($this->price > 0 ? $this->price : $this->simulateFactoryPriceFromDailyTarget());
        }

        return [
            'tier_1' => round($factoryPrice * 1.10, 2),
            'tier_2' => round($factoryPrice * 1.17, 2),
            'tier_3' => round($factoryPrice * 1.21, 2),
        ];
    }

    /**
     * 9. Recalcula y Guarda Precios y Costos en la Base de Datos
     */
    public function recalculateAndSavePrices(?BagCostSetting $settings = null): void
    {
        if (is_null($settings)) {
            $settings = BagCostSetting::getSettings();
        }

        if ($this->is_variable_quantity) {
            $this->sale_unit = 'KG';
            $this->millar_per_bulto = 1.0000;
            $this->unit_weight_kg = 1.0000;
            $this->real_total_weight_kg = 1.0000;
            $this->width_inch = null;
            $this->length_inch = null;
            $this->gauge_caliber = null;
        }

        $costo = $this->calculateRawMaterialCost((float)$settings->resin_price_per_kg);
        $this->cost = $costo;

        $targetUnits = (int)($this->target_units_per_shift ?: 5);
        $targetProfit = (float)($this->target_daily_profit ?: $settings->daily_profit_target ?: 105.00);

        if ((float)($this->price ?? 0) <= 0) {
            $this->price = $this->simulateFactoryPriceFromDailyTarget($targetProfit, $targetUnits, $costo);
        }

        $tiers = $this->calculateTiersFromFactoryPrice((float)$this->price);
        $this->price_tier_1 = $tiers['tier_1'];
        $this->price_tier_2 = $tiers['tier_2'];
        $this->price_tier_3 = $tiers['tier_3'];

        $this->save();
    }

    /**
     * Determina si el producto es un Bulto Compuesto de Bobinitas / Sub-bobinas de peso variable
     */
    public function isCompositeBobinaBulto(): bool
    {
        return (bool)($this->is_variable_quantity && $this->is_composite_rolls);
    }

    /**
     * Breakdown units into complete packages and loose fractional units.
     */
    public function calculateBreakdown(float $units): array
    {
        $capacity = max(0.0001, (float)($this->millar_per_bulto ?: 1));
        $completed = floor($units / $capacity);
        $fraction = round($units - ($completed * $capacity), 4);

        return [
            'completed_packages'   => (float)$completed,
            'fractional_units'     => (float)$fraction,
            'is_package_completed' => ($fraction == 0.0),
        ];
    }

    /**
     * Calculate weight quality grading (A, B, C) based on comparison with standard theoretical weight.
     * Tolerance: +/- 3% is Grade 'B' (Optimal). > +3% is 'A' (Overweight). < -3% is 'C' (Underweight).
     */
    public function calculateWeightQualityGrade(float $actualWeight, float $quantity = 1.0, float $fractionalUnits = 0.0): array
    {
        if ($this->is_variable_quantity) {
            return [
                'grade'              => 'B',
                'deviation_percent'  => 0.0,
                'theoretical_weight' => round($actualWeight, 4),
                'actual_weight'      => round($actualWeight, 4),
            ];
        }

        $unitWeight = (float)($this->unit_weight_kg > 0 ? $this->unit_weight_kg : $this->calculatePhysicalWeight());
        $millarPerBulto = (float)($this->millar_per_bulto ?: 1);
        $packageWeight = (float)($this->real_total_weight_kg > 0 ? $this->real_total_weight_kg : ($unitWeight * $millarPerBulto));

        $theoretical = ($quantity * $packageWeight) + ($fractionalUnits * $unitWeight);
        if ($theoretical <= 0.0001) {
            $theoretical = $actualWeight > 0 ? $actualWeight : 1.0;
        }

        $deviation = (($actualWeight - $theoretical) / $theoretical) * 100.0;
        $deviation = round($deviation, 2);

        if (abs($deviation) <= 3.0) {
            $grade = 'B';
        } elseif ($deviation > 3.0) {
            $grade = 'A';
        } else {
            $grade = 'C';
        }

        return [
            'grade'              => $grade,
            'deviation_percent'  => $deviation,
            'theoretical_weight' => round($theoretical, 4),
            'actual_weight'      => round($actualWeight, 4),
        ];
    }
}
