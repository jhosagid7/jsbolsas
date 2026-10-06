<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email','password' => 'required','device_name' => 'required']);

        $email = trim(strtolower($request->email));
        $ip = $request->ip();
        
        Log::info("Intento de login App desde IP: [" . $ip . "] Buscando usuario: " . $email);

        $credentials = [
            'email' => $email,
            'password' => $request->password,
        ];

        if (! Auth::attempt($credentials)) {
            $userExists = User::where('email', $email)->exists();
            Log::warning("Fallo de login App (IP: " . $ip . "): El usuario " . ($userExists ? 'SÍ existe en DB' : 'NO existe en DB') . " pero la clave fue rechazada.");
            
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        $user = Auth::user();
        Log::info("Login App EXITOSO (IP: " . $ip . ") para: " . $user->email);

        // Permission check for Soplados App
        if ($request->app_type === 'soplados') {
            try {
                $hasPerm = $user->hasPermissionTo('soplados.operator') || $user->hasPermissionTo('soplados.manager');
                if (!$hasPerm) {
                    Auth::logout();
                    return response()->json([
                        'message' => 'No tienes permiso para acceder a la aplicación de Soplados.',
                    ], 403);
                }
            } catch (\Throwable $e) {
                // Permission not configured
            }
        }

        // Generate the Sanctum token
        $token = $user->createToken($request->device_name)->plainTextToken;

        // Identify device for the response if model exists
        $deviceUuid = null;
        if (class_exists('App\Models\DeviceAuthorization')) {
            $device = \App\Models\DeviceAuthorization::where('ip_address', $ip)
                ->where('user_agent', $request->userAgent() ?? 'Unknown')
                ->where('status', 'approved')
                ->orderBy('last_accessed_at', 'desc')
                ->first();
            $deviceUuid = $device ? $device->uuid : null;
        }

        $isSopladosManager = false;
        try {
            $isSopladosManager = $user->hasPermissionTo('soplados.manager');
        } catch (\Throwable $e) {
            $isSopladosManager = false;
        }

        $isBolsasManager = false;
        try {
            $isBolsasManager = $user->hasPermissionTo('bolsas.manager') || in_array($user->profile, ['Admin', 'Super Admin']);
        } catch (\Throwable $e) {
            $isBolsasManager = in_array($user->profile, ['Admin', 'Super Admin']);
        }

        $role = strtolower($user->profile ?? 'operario');
        if (in_array($role, ['superadmin', 'super admin', 'admin', 'administrador'])) {
            $role = 'admin';
        }

        return response()->json([
            'token' => $token,
            'access_token' => $token, // Compatibility for older versions
            'device_uuid' => $deviceUuid,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile' => $user->profile,
                'role' => $role,
                'warehouse_id' => $user->warehouse_id,
                'daily_salary' => (float)($user->daily_salary ?? 15.0),
                'weekly_salary' => (float)($user->weekly_salary ?? 90.0),
                'work_days_per_week' => (int)($user->work_days_per_week ?? 6),
                'pay_partial_packages' => (bool)($user->pay_partial_packages ?? true),
                'order_deadline_at' => $user->order_deadline_at,
                'is_deadline_active' => $user->is_deadline_active,
                'is_soplados_manager' => $isSopladosManager,
                'is_bolsas_manager' => $isBolsasManager,
            ],
        ]);
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
