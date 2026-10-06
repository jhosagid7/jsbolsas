<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class BagLabelPrint extends Model
{
    use HasFactory;

    protected $table = 'bag_label_prints';

    protected $fillable = [
        'production_id',
        'user_id',
        'qr_code',
        'label_type',
        'print_count',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'production_id' => 'integer',
        'user_id'       => 'integer',
        'print_count'   => 'integer',
    ];

    public function production(): BelongsTo
    {
        return $this->belongsTo(BagProduction::class, 'production_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Registrar de forma transparente e inalterable la impresión o reimpresión de una etiqueta física.
     */
    public static function logPrint(
        string $qrCode,
        ?int $productionId = null,
        ?int $userId = null,
        string $labelType = 'millar',
        ?string $ip = null,
        ?string $userAgent = null
    ): self {
        $userId = $userId ?: (Auth::id() ?? 1);
        $ip = $ip ?: (request()->ip() ?? '127.0.0.1');
        $userAgent = $userAgent ?: (request()->userAgent() ?? 'CLI/System');

        $query = static::where('qr_code', $qrCode);
        if ($productionId) {
            $query->where('production_id', $productionId);
        }

        $existing = $query->first();

        if ($existing) {
            $existing->increment('print_count');
            $existing->update([
                'user_id'    => $userId,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);
            return $existing;
        }

        return static::create([
            'production_id' => $productionId,
            'user_id'       => $userId,
            'qr_code'       => $qrCode,
            'label_type'    => $labelType,
            'print_count'   => 1,
            'ip_address'    => $ip,
            'user_agent'    => $userAgent,
        ]);
    }
}
