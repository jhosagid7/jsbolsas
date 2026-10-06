<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'taxpayer_id',
        'address',
        'password',
        'profile',
        'role',
        'theme',
        'weekly_salary',
        'work_days_per_week',
        'pay_partial_packages',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'weekly_salary' => 'decimal:2',
        'work_days_per_week' => 'integer',
        'pay_partial_packages' => 'boolean',
    ];

    public function getDailySalaryAttribute(): float
    {
        $days = $this->work_days_per_week ?: 6;
        $salary = $this->weekly_salary ?: 90.00;
        return round($salary / max(1, $days), 2);
    }

    public function getThemeAttribute($value)
    {
        if (is_null($value)) return [];
        
        $decoded = json_decode($value, true);
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }
        
        return is_array($decoded) ? $decoded : [];
    }

    public function setThemeAttribute($value)
    {
        $this->attributes['theme'] = is_array($value) ? json_encode($value) : $value;
    }

    public function isSuperAdmin(): bool
    {
        return in_array(strtolower($this->role ?? $this->profile ?? ''), ['superadmin', 'super admin', 'super_admin'])
            || (method_exists($this, 'hasRole') && $this->hasRole(['Super Admin', 'superadmin']));
    }

    public function isAdmin(): bool
    {
        return in_array(strtolower($this->role ?? $this->profile ?? ''), ['admin', 'administrador'])
            || (method_exists($this, 'hasRole') && $this->hasRole(['Admin', 'admin']))
            || $this->isSuperAdmin();
    }

    public function isSupervisor(): bool
    {
        return strtolower($this->role ?? $this->profile ?? '') === 'supervisor'
            || (method_exists($this, 'hasRole') && $this->hasRole(['Supervisor', 'supervisor']));
    }

    public function isOperator(): bool
    {
        return in_array(strtolower($this->role ?? $this->profile ?? ''), ['operario', 'operator'])
            || (method_exists($this, 'hasRole') && $this->hasRole(['Operario', 'operario', 'Operator']));
    }

    public function isOperario(): bool
    {
        return $this->isOperator();
    }

    public function isWarehouse(): bool
    {
        return in_array(strtolower($this->role ?? $this->profile ?? ''), ['almacen', 'warehouse'])
            || (method_exists($this, 'hasRole') && $this->hasRole(['Almacen', 'almacen', 'Warehouse']));
    }

    public function isAlmacen(): bool
    {
        return $this->isWarehouse();
    }

    public function getRoleAttribute()
    {
        return $this->attributes['role'] ?? $this->attributes['profile'] ?? null;
    }

    public function setRoleAttribute($value)
    {
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'role')) {
            $this->attributes['role'] = $value;
        }
        $this->attributes['profile'] = $value;
    }

    /**
     * Calculate labor tariffs per bulto and per millar for a given bag product.
     */
    public function calculateLaborTariff($product): array
    {
        $dailySalary = $this->daily_salary;
        $targetUnits = max(1, (int)($product->target_units_per_shift ?? 1));
        $packageTariff = round($dailySalary / $targetUnits, 4);
        $millarPerBulto = max(1, (float)($product->millar_per_bulto ?? 1));
        $fractionTariff = round($packageTariff / $millarPerBulto, 4);

        return [
            'daily_salary'    => $dailySalary,
            'package_tariff'  => $packageTariff,
            'fraction_tariff' => $fractionTariff,
        ];
    }

    /**
     * Get operator shift earnings breakdown.
     */
    public function getShiftEarnings($shiftId): array
    {
        $productions = \App\Models\BagProduction::where('user_id', $this->id)
            ->where('bag_shift_id', $shiftId)
            ->get();

        $earned = (float)$productions->sum('labor_earned_amount');
        $retained = (float)$productions->sum('labor_retained_amount');
        $available = (float)($earned - $retained);
        $completedPackages = (float)$productions->sum('completed_packages_count');
        $fractionalUnits = (float)$productions->sum('fractional_units');

        return [
            'earned'             => round($earned, 2),
            'available'          => round($available, 2),
            'retained'           => round($retained, 2),
            'completed_packages' => $completedPackages,
            'fractional_units'   => $fractionalUnits,
            'count'              => $productions->count(),
        ];
    }

    /**
     * Get operator weekly earnings breakdown.
     */
    public function getWeeklyEarnings($startDate = null, $endDate = null): array
    {
        $start = $startDate ? \Carbon\Carbon::parse($startDate)->startOfDay() : now()->startOfWeek(\Carbon\Carbon::MONDAY)->startOfDay();
        $end = $endDate ? \Carbon\Carbon::parse($endDate)->endOfDay() : now()->endOfWeek(\Carbon\Carbon::SUNDAY)->endOfDay();

        $productions = \App\Models\BagProduction::where('user_id', $this->id)
            ->whereBetween('recorded_at', [$start, $end])
            ->get();

        $earned = (float)$productions->sum('labor_earned_amount');
        $retained = (float)$productions->sum('labor_retained_amount');
        $available = (float)($earned - $retained);
        $completedPackages = (float)$productions->sum('completed_packages_count');
        $fractionalUnits = (float)$productions->sum('fractional_units');

        return [
            'start_date'         => $start->toDateString(),
            'end_date'           => $end->toDateString(),
            'weekly_salary'      => (float)($this->weekly_salary ?: 90.00),
            'work_days'          => (int)($this->work_days_per_week ?: 6),
            'daily_salary'       => $this->daily_salary,
            'earned'             => round($earned, 2),
            'available'          => round($available, 2),
            'retained'           => round($retained, 2),
            'completed_packages' => $completedPackages,
            'fractional_units'   => $fractionalUnits,
        ];
    }
}

