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
    ];

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
}

