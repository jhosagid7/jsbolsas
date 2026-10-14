<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionFormula extends Model
{
    use HasFactory;

    protected $table = 'production_formulas';

    protected $fillable = [
        'name',
        'code',
        'description',
        'current_version_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(FormulaVersion::class, 'production_formula_id')->orderBy('version_number', 'desc');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(FormulaVersion::class, 'current_version_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(BagProduct::class, 'production_formula_id');
    }

    /**
     * Crea una nueva versión inmutable de la fórmula con sus ingredientes y costo ponderado $/KG
     */
    public function createNewVersion(array $items, ?int $userId = null, ?string $notes = null): FormulaVersion
    {
        $lastVersion = $this->versions()->latest('version_number')->first();
        $nextVersionNum = $lastVersion ? ($lastVersion->version_number + 1) : 1;

        $totalKg = 0.0;
        $totalCost = 0.0;
        $cleanItems = [];

        foreach ($items as $item) {
            $matId = (int)($item['raw_material_id'] ?? 0);
            $qty = (float)($item['quantity_kg'] ?? 0);

            if ($matId > 0 && $qty > 0) {
                $mat = RawMaterial::find($matId);
                if ($mat) {
                    $price = (float)$mat->final_price;
                    $subtotal = round($qty * $price, 4);

                    $totalKg += $qty;
                    $totalCost += $subtotal;

                    $cleanItems[] = [
                        'raw_material_id' => $mat->id,
                        'quantity_kg'     => $qty,
                        'price_applied'   => $price,
                        'subtotal_cost'   => $subtotal,
                    ];
                }
            }
        }

        $costPerKg = $totalKg > 0 ? round($totalCost / $totalKg, 4) : 0.0;
        $now = now();

        // 1. Desactivar versión anterior
        FormulaVersion::where('production_formula_id', $this->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'valid_to'  => $now,
            ]);

        // 2. Crear nueva versión
        $newVersion = FormulaVersion::create([
            'production_formula_id' => $this->id,
            'version_number'        => $nextVersionNum,
            'total_kg'              => $totalKg,
            'total_cost'            => $totalCost,
            'cost_per_kg'           => $costPerKg,
            'is_active'             => true,
            'valid_from'            => $now,
            'valid_to'              => null,
            'notes'                 => $notes,
            'created_by'            => $userId,
        ]);

        // 3. Crear detalles de ingredientes
        foreach ($cleanItems as $ci) {
            FormulaVersionItem::create([
                'formula_version_id' => $newVersion->id,
                'raw_material_id'    => $ci['raw_material_id'],
                'quantity_kg'        => $ci['quantity_kg'],
                'price_applied'      => $ci['price_applied'],
                'subtotal_cost'      => $ci['subtotal_cost'],
            ]);
        }

        // 4. Vincular a la fórmula
        $this->update(['current_version_id' => $newVersion->id]);

        return $newVersion;
    }

    /**
     * Obtiene la versión de la fórmula vigente a una fecha histórica específica
     */
    public function getVersionAtDate($dateTime): ?FormulaVersion
    {
        return FormulaVersion::with(['items.rawMaterial'])
            ->where('production_formula_id', $this->id)
            ->where('valid_from', '<=', $dateTime)
            ->where(function ($q) use ($dateTime) {
                $q->whereNull('valid_to')->orWhere('valid_to', '>', $dateTime);
            })
            ->latest('valid_from')
            ->first();
    }

    /**
     * Calcula el desglose proporcional de materias primas para un peso producido total (Kg)
     * basado en la versión activa de la fórmula.
     */
    public function calculateIngredientBreakdown(float $totalWeightKg): array
    {
        if ($totalWeightKg <= 0) {
            return [];
        }

        $version = $this->currentVersion ?: $this->versions()->where('is_active', true)->first();
        if (!$version || (float)$version->total_kg <= 0) {
            return [];
        }

        $breakdown = [];
        $items = $version->items()->with('rawMaterial')->get();

        foreach ($items as $item) {
            $prop = (float)$item->quantity_kg / (float)$version->total_kg;
            $kgConsumed = round($totalWeightKg * $prop, 4);

            $breakdown[] = [
                'raw_material_id'   => $item->raw_material_id,
                'raw_material_name' => $item->rawMaterial?->name ?? 'Insumo #' . $item->raw_material_id,
                'raw_material_code' => $item->rawMaterial?->code ?? '',
                'percentage'        => round($prop * 100, 2),
                'quantity_kg'       => $kgConsumed,
                'sacos_count'       => round($kgConsumed / 25.0, 4),
            ];
        }

        return $breakdown;
    }
}
