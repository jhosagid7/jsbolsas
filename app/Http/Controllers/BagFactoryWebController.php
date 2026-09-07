<?php

namespace App\Http\Controllers;

use App\Models\BagCostSetting;
use App\Models\BagMachine;
use App\Models\BagProduct;
use App\Models\BagProduction;
use App\Models\BagShift;
use App\Models\FormulaVersion;
use App\Models\ProductionFormula;
use App\Models\RawMaterial;
use App\Models\RawMaterialPriceHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BagFactoryWebController extends Controller
{
    // ==================== AUTENTICACIÓN ====================
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->route('dashboard');
        }

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    // ==================== DASHBOARD & RENDIMIENTO EN VIVO ====================
    public function dashboard(Request $request)
    {
        $period = $request->get('period', 'today');
        $settings = BagCostSetting::getSettings();

        // Determinar Rango de Fechas según el Período
        if ($period === 'week') {
            $startDate = Carbon::now()->startOfWeek();
            $endDate = Carbon::now()->endOfWeek();
            $daysCount = 7;
            $periodLabel = 'Esta Semana';
            $targetTitle = 'Meta Utilidad Semanal';
        } elseif ($period === 'month') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
            $daysCount = (int)Carbon::now()->daysInMonth;
            $periodLabel = 'Este Mes (' . Carbon::now()->locale('es')->isoFormat('MMMM YYYY') . ')';
            $targetTitle = 'Meta Utilidad Mensual';
        } elseif ($period === 'custom' && $request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $daysCount = max(1, (int)$startDate->diffInDays($endDate) + 1);
            $periodLabel = 'Rango: ' . $startDate->format('d/m/Y') . ' al ' . $endDate->format('d/m/Y');
            $targetTitle = "Meta del Período ($daysCount Días)";
        } else {
            $period = 'today';
            $startDate = Carbon::today()->startOfDay();
            $endDate = Carbon::today()->endOfDay();
            $daysCount = 1;
            $periodLabel = 'Hoy (' . Carbon::today()->format('d/m/Y') . ')';
            $targetTitle = 'Meta Utilidad Diaria';
        }

        $machineId = $request->get('machine_id');

        // Base Queries filtered by machine if requested
        $prodQuery = BagProduction::whereDate('recorded_at', '>=', $startDate)->whereDate('recorded_at', '<=', $endDate);
        if ($machineId) {
            $prodQuery->whereHas('shift', function ($q) use ($machineId) {
                $q->where('machine_id', $machineId);
            });
        }

        $shiftCountQuery = BagShift::whereDate('created_at', '>=', $startDate)->whereDate('created_at', '<=', $endDate);
        if ($machineId) {
            $shiftCountQuery->where('machine_id', $machineId);
        }

        // 1. Estadísticas Generales del Período
        $stats = [
            'today_kg'        => (float)(clone $prodQuery)->sum('weight'),
            'today_packages'  => (float)(clone $prodQuery)->sum('quantity'),
            'pending_review'  => BagProduction::where('status', 'pending_review')->count(),
            'approved_ready'  => BagProduction::where('status', 'approved')->whereNull('lifted_at')->count(),
            'active_shifts'   => (int)(clone $shiftCountQuery)->count(),
            'total_operators' => (\Illuminate\Support\Facades\Schema::hasColumn('users', 'role')
                ? User::where('role', 'like', '%operario%')->orWhere('role', 'like', '%operator%')->count()
                : (\Illuminate\Support\Facades\Schema::hasColumn('users', 'profile')
                    ? User::where('profile', 'like', '%operario%')->orWhere('profile', 'like', '%operator%')->count()
                    : User::count())) ?: User::count(),
        ];

        // 2. Turnos de la Jornada / Período
        $shiftQuery = BagShift::with(['user', 'machine', 'productions.product.formula.currentVersion'])
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        if ($period === 'today') {
            $shiftQuery->orWhere('status', 'open');
        }

        if ($machineId) {
            $shiftQuery->where('machine_id', $machineId);
        }

        $activeShiftsList = $shiftQuery->orderBy('start_time', 'desc')->get();

        foreach ($activeShiftsList as $s) {
            $s->recalculateFinancials();
        }

        // 3. Balance Financiero del Período
        $todayIncome = (float)$activeShiftsList->sum('total_income');
        $todayCost = (float)$activeShiftsList->sum('total_production_cost');
        $todayFixedCost = (float)$activeShiftsList->sum('fixed_operational_cost');
        $todayRawCost = max(0, $todayCost - $todayFixedCost);
        $todayNetProfit = $todayIncome - $todayCost;
        $todayMarginPercent = $todayIncome > 0 ? round(($todayNetProfit / $todayIncome) * 100.0, 2) : 0.0;
        
        $dailyTarget = (float)$settings->daily_profit_target * $daysCount;
        $targetProfitPercent = $dailyTarget > 0 ? round(($todayNetProfit / $dailyTarget) * 100.0, 1) : 0.0;

        $financials = [
            'today_income'          => $todayIncome,
            'today_cost'            => $todayCost,
            'today_fixed_cost'      => $todayFixedCost,
            'today_raw_cost'        => $todayRawCost,
            'today_net_profit'      => $todayNetProfit,
            'today_margin_percent'  => $todayMarginPercent,
            'daily_target'          => $dailyTarget,
            'target_profit_percent' => max(0, $targetProfitPercent),
            'target_title'          => $targetTitle,
            'period'                => $period,
            'period_label'          => $periodLabel,
            'days_count'            => $daysCount,
            'start_date'            => $startDate->format('Y-m-d'),
            'end_date'              => $endDate->format('Y-m-d'),
            'machine_id'            => $machineId,
        ];

        // Métricas específicas de Máquinas
        $allMachines = BagMachine::where('is_active', true)->orderBy('name')->get();
        $selectedMachine = $machineId ? BagMachine::find($machineId) : null;
        $machineStats = null;

        if ($selectedMachine) {
            $machineShifts = $activeShiftsList;
            $machineHours = 0.0;
            foreach ($machineShifts as $ms) {
                $start = $ms->start_time;
                $end = $ms->end_time ?: now();
                if ($start) {
                    $machineHours += round($start->diffInMinutes($end) / 60.0, 1);
                }
            }
            $targetPacksSum = (float)$machineShifts->sum('target_packages');
            $realPacksSum = (float)$machineShifts->sum('total_packages');
            $efficiency = $targetPacksSum > 0 ? round(($realPacksSum / $targetPacksSum) * 100, 1) : 0.0;

            $machineStats = [
                'machine'         => $selectedMachine,
                'total_kg'        => (float)$stats['today_kg'],
                'total_packages'  => (float)$stats['today_packages'],
                'estimated_hours' => $machineHours,
                'efficiency'      => $efficiency,
                'shifts_count'    => $machineShifts->count(),
            ];
        }

        $allProducts = BagProduct::where('is_active', true)->orderBy('name')->get();
        $allUsers = User::orderBy('name')->get();

        return view('dashboard', compact('stats', 'activeShiftsList', 'financials', 'allProducts', 'allUsers', 'settings', 'allMachines', 'selectedMachine', 'machineStats'));
    }

    // ==================== MATERIAS PRIMAS & HISTORIAL DE PRECIOS ====================
    public function rawMaterialsIndex()
    {
        $materials = RawMaterial::with(['priceHistories.creator'])->orderBy('name')->get();
        return view('raw_materials.index', compact('materials'));
    }

    public function rawMaterialsStore(Request $request)
    {
        if (!Auth::user()->isSuperAdmin()) {
            return back()->with('error', 'Solo el Super Administrador puede crear materias primas.');
        }

        $request->validate([
            'name'           => 'required|string|max:255',
            'code'           => 'required|string|max:50|unique:raw_materials,code',
            'base_price'     => 'required|numeric|min:0',
            'transport_cost' => 'required|numeric|min:0',
            'surcharge'      => 'required|numeric|min:0',
        ]);

        $finalPrice = RawMaterial::calculateFinalPrice(
            (float)$request->base_price,
            (float)$request->transport_cost,
            (float)$request->surcharge
        );

        $mat = RawMaterial::create([
            'name'           => $request->name,
            'code'           => strtoupper($request->code),
            'description'    => $request->description,
            'base_price'     => $request->base_price,
            'transport_cost' => $request->transport_cost,
            'surcharge'      => $request->surcharge,
            'final_price'    => $finalPrice,
            'is_active'      => true,
        ]);

        RawMaterialPriceHistory::create([
            'raw_material_id' => $mat->id,
            'base_price'      => $request->base_price,
            'transport_cost'  => $request->transport_cost,
            'surcharge'       => $request->surcharge,
            'final_price'     => $finalPrice,
            'valid_from'      => now(),
            'valid_to'        => null,
            'created_by'      => Auth::id(),
            'notes'           => 'Precio inicial de registro',
        ]);

        return back()->with('status', "Materia prima '{$mat->name}' registrada con precio final de \${$finalPrice}/Kg.");
    }

    public function rawMaterialsUpdatePrice(Request $request, $id)
    {
        if (!Auth::user()->isSuperAdmin()) {
            return back()->with('error', 'Solo el Super Administrador puede actualizar precios de materia prima.');
        }

        $mat = RawMaterial::findOrFail($id);

        $request->validate([
            'base_price'     => 'required|numeric|min:0',
            'transport_cost' => 'required|numeric|min:0',
            'surcharge'      => 'required|numeric|min:0',
            'notes'          => 'required|string|min:3',
        ]);

        $history = $mat->updatePrice(
            (float)$request->base_price,
            (float)$request->transport_cost,
            (float)$request->surcharge,
            Auth::id(),
            $request->notes
        );

        // Recalcular fórmulas activas que usen este material
        $formulas = ProductionFormula::with(['currentVersion.items'])->get();
        foreach ($formulas as $f) {
            $currVer = $f->currentVersion;
            if ($currVer) {
                $hasMat = $currVer->items->contains('raw_material_id', $mat->id);
                if ($hasMat) {
                    $newItems = [];
                    foreach ($currVer->items as $it) {
                        $newItems[] = [
                            'raw_material_id' => $it->raw_material_id,
                            'quantity_kg'     => (float)$it->quantity_kg,
                        ];
                    }
                    $f->createNewVersion($newItems, Auth::id(), "Actualización automática por cambio de precio en {$mat->name}");
                }
            }
        }

        // Recalcular catálogo de productos
        $products = BagProduct::all();
        foreach ($products as $p) {
            $p->recalculateAndSavePrices();
        }

        return back()->with('status', "Precio de '{$mat->name}' actualizado a \${$history->final_price}/Kg. Se generó nueva versión de las fórmulas asociadas e histórico inmutable.");
    }

    // ==================== FÓRMULAS DE PREPARACIÓN ====================
    public function formulasIndex()
    {
        $formulas = ProductionFormula::with(['currentVersion.items.rawMaterial', 'versions.items.rawMaterial', 'versions.creator'])->orderBy('name')->get();
        $rawMaterials = RawMaterial::where('is_active', true)->orderBy('name')->get();
        return view('formulas.index', compact('formulas', 'rawMaterials'));
    }

    public function formulasStore(Request $request)
    {
        if (!Auth::user()->isSuperAdmin()) {
            return back()->with('error', 'Solo el Super Administrador puede crear fórmulas.');
        }

        $request->validate([
            'name'                     => 'required|string|max:255',
            'code'                     => 'required|string|max:50|unique:production_formulas,code',
            'description'              => 'nullable|string',
            'items'                    => 'required|array|min:1',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.quantity_kg'     => 'required|numeric|min:0.01',
        ]);

        $formula = ProductionFormula::create([
            'name'        => $request->name,
            'code'        => strtoupper($request->code),
            'description' => $request->description,
            'is_active'   => true,
        ]);

        $version = $formula->createNewVersion($request->items, Auth::id(), 'Versión inicial v1 de la fórmula');

        return back()->with('status', "Fórmula '{$formula->name}' creada con éxito (Costo Ponderado: \${$version->cost_per_kg}/Kg).");
    }

    public function formulasNewVersion(Request $request, $id)
    {
        if (!Auth::user()->isSuperAdmin()) {
            return back()->with('error', 'Solo el Super Administrador puede ajustar recetas.');
        }

        $formula = ProductionFormula::findOrFail($id);

        $request->validate([
            'notes'                    => 'required|string|min:3',
            'items'                    => 'required|array|min:1',
            'items.*.raw_material_id' => 'required|exists:raw_materials,id',
            'items.*.quantity_kg'     => 'required|numeric|min:0.01',
        ]);

        $newVer = $formula->createNewVersion($request->items, Auth::id(), $request->notes);

        // Recalcular productos que usan esta fórmula
        $products = BagProduct::where('production_formula_id', $formula->id)->get();
        foreach ($products as $p) {
            $p->recalculateAndSavePrices();
        }

        return back()->with('status', "Fórmula '{$formula->name}' actualizada a versión v{$newVer->version_number} (Nuevo Costo: \${$newVer->cost_per_kg}/Kg).");
    }

    // ==================== MÓDULO DE COSTOS, MATERIA PRIMA & PRECIOS ====================
    public function costsIndex()
    {
        $settings = BagCostSetting::getSettings();
        $products = BagProduct::with(['formula.currentVersion'])->orderBy('name')->get();
        $formulas = ProductionFormula::with('currentVersion')->where('is_active', true)->orderBy('name')->get();
        return view('costs.index', compact('settings', 'products', 'formulas'));
    }

    public function costsUpdate(Request $request)
    {
        if (!Auth::user()->isSuperAdmin()) {
            return back()->with('error', 'Solo el Super Administrador puede modificar los parámetros de costos globales.');
        }

        $request->validate([
            'resin_price_per_kg'  => 'required|numeric|min:0.0001',
            'shift_fixed_cost'    => 'required|numeric|min:0',
            'daily_profit_target' => 'required|numeric|min:0',
        ]);

        $settings = BagCostSetting::getSettings();
        $settings->update([
            'resin_price_per_kg'  => $request->resin_price_per_kg,
            'shift_fixed_cost'    => $request->shift_fixed_cost,
            'daily_profit_target' => $request->daily_profit_target,
        ]);

        // Recalcular precios de todos los productos del catálogo
        $products = BagProduct::all();
        foreach ($products as $p) {
            $p->recalculateAndSavePrices($settings);
        }

        if ($request->expectsJson() || $request->ajax()) {
            $updatedProducts = BagProduct::with(['formula.currentVersion'])->get()->map(function($p) use ($settings) {
                $costo = $p->calculateRawMaterialCost((float)$settings->resin_price_per_kg);
                $fabrica = (float)($p->price > 0 ? $p->price : $p->simulateFactoryPriceFromDailyTarget());
                $tiers = $p->calculateTiersFromFactoryPrice($fabrica);
                $metaUnits = (int)($p->target_units_per_shift ?: 5);
                $utilDia = $p->calculateDailyProfitFromFactoryPrice($fabrica, $metaUnits, $costo);
                $priceKg = $p->getEffectivePricePerKg((float)$settings->resin_price_per_kg);

                return [
                    'id'               => $p->id,
                    'has_formula'      => (bool)($p->production_formula_id && $p->formula && $p->formula->currentVersion),
                    'formula_price_kg' => number_format($priceKg, 4),
                    'cost'             => number_format($costo, 2),
                    'factory_price'    => number_format($fabrica, 2),
                    'daily_profit'     => number_format($utilDia, 2),
                    'tier_1'           => number_format($tiers['tier_1'], 2),
                    'tier_2'           => number_format($tiers['tier_2'], 2),
                    'tier_3'           => number_format($tiers['tier_3'], 2),
                ];
            });

            return response()->json([
                'success'  => true,
                'message'  => 'Parámetros guardados y catálogo recalculado en tiempo real sin recargar la página.',
                'settings' => $settings,
                'products' => $updatedProducts,
            ]);
        }

        return back()->with('status', 'Parámetros de costos globales guardados y catálogo de precios recalculado exitosamente.');
    }

    public function productsTechnicalUpdate(Request $request, $id)
    {
        if (!Auth::user()->isSuperAdmin()) {
            return back()->with('error', 'Solo el Super Administrador puede editar fichas técnicas.');
        }

        $product = BagProduct::findOrFail($id);

        $request->validate([
            'name'                   => 'required|string|max:255',
            'category'               => 'nullable|string|max:255',
            'production_formula_id'  => 'nullable|exists:production_formulas,id',
            'sale_unit'              => 'required|string|max:30',
            'millar_per_bulto'       => 'required|numeric|min:0.0001',
            'target_units_per_shift' => 'required|integer|min:1',
            'target_daily_profit'    => 'nullable|numeric|min:0',
            'price'                  => 'nullable|numeric|min:0',
            'width_inch'             => 'nullable|numeric|min:0',
            'length_inch'            => 'nullable|numeric|min:0',
            'gauge_caliber'          => 'nullable|numeric|min:0',
            'unit_weight_kg'         => 'nullable|numeric|min:0',
            'sku'                    => 'nullable|string|max:50',
            'is_variable_quantity'   => 'nullable|boolean',
        ]);

        $isVariable = $request->boolean('is_variable_quantity', false);

        $product->fill([
            'name'                   => $request->name,
            'category'               => $request->category,
            'production_formula_id'  => $request->production_formula_id ?: null,
            'sale_unit'              => $isVariable ? 'KG' : strtoupper($request->sale_unit ?: 'BULTO'),
            'millar_per_bulto'       => $isVariable ? 1.0000 : (float)($request->millar_per_bulto ?: 1),
            'target_units_per_shift' => $request->target_units_per_shift,
            'target_daily_profit'    => $request->target_daily_profit ?? 105.00,
            'price'                  => $request->price ?? 0,
            'width_inch'             => $isVariable ? null : $request->width_inch,
            'length_inch'            => $isVariable ? null : $request->length_inch,
            'gauge_caliber'          => $isVariable ? null : $request->gauge_caliber,
            'real_total_weight_kg'   => $isVariable ? 1.0000 : $request->unit_weight_kg, // Override manual de PESO_R
            'unit_weight_kg'         => $isVariable ? 1.0000 : $request->unit_weight_kg,
            'sku'                    => strtoupper($request->sku ?? $product->sku),
            'is_variable_quantity'   => $isVariable,
        ]);

        $product->recalculateAndSavePrices();

        return back()->with('status', "Ficha técnica, costo de materia prima y precios simulados de '{$product->name}' guardados correctamente.");
    }

    // ==================== GESTIÓN DE USUARIOS Y ROLES ====================
    public function usersIndex()
    {
        $users = User::orderBy('name')->get();
        return view('users.index', compact('users'));
    }

    public function usersStore(Request $request)
    {
        $allowedRoles = ['admin', 'supervisor', 'operario', 'almacen'];
        if (Auth::user()?->isSuperAdmin()) {
            $allowedRoles[] = 'superadmin';
        }

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role'     => 'required|in:' . implode(',', $allowedRoles),
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        return back()->with('status', 'Usuario creado exitosamente.');
    }

    public function usersUpdate(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $allowedRoles = ['admin', 'supervisor', 'operario', 'almacen'];
        if (Auth::user()?->isSuperAdmin()) {
            $allowedRoles[] = 'superadmin';
        }

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $id,
            'role'     => 'required|in:' . implode(',', $allowedRoles),
            'password' => 'nullable|string|min:6',
        ]);

        if ($user->isSuperAdmin() && !Auth::user()?->isSuperAdmin()) {
            return back()->with('error', 'Solo el Super Administrador puede modificar esta cuenta.');
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        return back()->with('status', 'Usuario actualizado correctamente.');
    }

    public function usersDestroy($id)
    {
        if (Auth::id() == $id) {
            return back()->with('error', 'No puedes eliminar tu propio usuario actual.');
        }

        $user = User::findOrFail($id);
        if ($user->isSuperAdmin() && !Auth::user()?->isSuperAdmin()) {
            return back()->with('error', 'No tienes permisos para eliminar al Super Administrador.');
        }

        $user->delete();
        return back()->with('status', 'Usuario eliminado.');
    }

    // ==================== GESTIÓN DE PRODUCTOS / BOLSAS ====================
    public function productsIndex()
    {
        $products = BagProduct::orderBy('name')->get();
        return view('products.index', compact('products'));
    }

    public function productsStore(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'sku'                   => 'required|string|max:50|unique:bag_products,sku',
            'category'              => 'nullable|string|max:255',
            'production_formula_id' => 'nullable|exists:production_formulas,id',
            'sale_unit'             => 'nullable|string|max:30',
            'millar_per_bulto'      => 'nullable|numeric|min:0.0001',
            'width_inch'            => 'nullable|numeric|min:0',
            'length_inch'           => 'nullable|numeric|min:0',
            'gauge_caliber'         => 'nullable|numeric|min:0',
            'unit_weight_kg'        => 'nullable|numeric|min:0',
            'cost'                  => 'nullable|numeric|min:0',
            'price'                 => 'nullable|numeric|min:0',
            'target_units_per_shift'=> 'nullable|integer|min:1',
            'target_daily_profit'   => 'nullable|numeric|min:0',
            'is_variable_quantity'  => 'nullable|boolean',
        ]);

        $isVariable = $request->boolean('is_variable_quantity', false);

        $prod = BagProduct::create([
            'name'                   => $request->name,
            'category'               => $request->category,
            'production_formula_id'  => $request->production_formula_id ?: null,
            'sale_unit'              => $isVariable ? 'KG' : strtoupper($request->sale_unit ?? 'BULTO'),
            'sku'                    => strtoupper($request->sku),
            'millar_per_bulto'       => $isVariable ? 1.0000 : (float)($request->millar_per_bulto ?? 1),
            'width_inch'             => $isVariable ? null : $request->width_inch,
            'length_inch'            => $isVariable ? null : $request->length_inch,
            'gauge_caliber'          => $isVariable ? null : $request->gauge_caliber,
            'unit_weight_kg'         => $isVariable ? 1.0000 : $request->unit_weight_kg,
            'real_total_weight_kg'   => $isVariable ? 1.0000 : $request->unit_weight_kg,
            'cost'                   => $request->cost ?? 0,
            'price'                  => $request->price ?? 0,
            'target_units_per_shift' => $request->target_units_per_shift ?? 5,
            'target_daily_profit'    => $request->target_daily_profit ?? 105.00,
            'is_variable_quantity'   => $isVariable,
            'is_active'              => true,
        ]);

        $prod->recalculateAndSavePrices();

        return back()->with('status', 'Ficha técnica registrada exitosamente.');
    }

    public function productsUpdate(Request $request, $id)
    {
        $product = BagProduct::findOrFail($id);

        $request->validate([
            'name'                 => 'required|string|max:255',
            'sku'                  => 'required|string|max:50|unique:bag_products,sku,' . $id,
            'cost'                 => 'required|numeric|min:0',
            'price'                => 'required|numeric|min:0',
            'is_variable_quantity' => 'nullable|boolean',
        ]);

        $product->update([
            'name'                 => $request->name,
            'sku'                  => strtoupper($request->sku),
            'cost'                 => $request->cost,
            'price'                => $request->price,
            'is_variable_quantity' => $request->boolean('is_variable_quantity', false),
            'is_active'            => $request->boolean('is_active', true),
        ]);

        return back()->with('status', 'Producto actualizado.');
    }

    public function productsDestroy($id)
    {
        $product = BagProduct::findOrFail($id);
        $product->delete();
        return back()->with('status', 'Producto eliminado.');
    }

    // ==================== GESTIÓN DE MÁQUINAS ====================
    public function machinesIndex()
    {
        $machines = BagMachine::orderBy('name')->get();
        return view('machines.index', compact('machines'));
    }

    public function machinesStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:bag_machines,code',
            'type' => 'required|in:extrusora,selladora,cortadora,recuperadora,otra',
        ]);

        BagMachine::create([
            'name'      => $request->name,
            'code'      => strtoupper($request->code),
            'type'      => $request->type,
            'is_active' => true,
        ]);

        return back()->with('status', 'Máquina registrada correctamente.');
    }

    public function machinesDestroy($id)
    {
        $machine = BagMachine::findOrFail($id);
        $machine->delete();
        return back()->with('status', 'Máquina eliminada.');
    }

    // ==================== AUDITORÍA Y BÁSCULA ====================
    public function scaleAudit(Request $request)
    {
        $qrQuery = trim($request->get('qr', ''));
        $clinicalReport = null;

        if (!empty($qrQuery)) {
            $clinicalReport = BagProduction::with(['user', 'product.formula.currentVersion', 'shift.machine', 'reviewer'])
                ->where(function ($q) use ($qrQuery) {
                    $q->where('qr_code', $qrQuery)
                      ->orWhere('id', $qrQuery)
                      ->orWhere('sync_id', $qrQuery);
                })
                ->first();
        }

        $pendingProductions = BagProduction::with(['user', 'product.formula.currentVersion', 'shift.machine'])
            ->where('status', 'pending_review')
            ->orderBy('recorded_at', 'desc')
            ->get();

        $recentApproved = BagProduction::with(['user', 'product.formula.currentVersion', 'shift.machine', 'reviewer'])
            ->where('status', 'approved')
            ->orderBy('reviewed_at', 'desc')
            ->take(40)
            ->get();

        $allProducts = BagProduct::where('is_active', true)->orderBy('name')->get();
        $allUsers = User::orderBy('name')->get();
        $allMachines = BagMachine::where('is_active', true)->orderBy('name')->get();

        return view('scale.index', compact('pendingProductions', 'recentApproved', 'allProducts', 'allUsers', 'allMachines', 'qrQuery', 'clinicalReport'));
    }

    public function approve($id)
    {
        $prod = BagProduction::findOrFail($id);
        if (empty($prod->qr_code)) {
            $prod->qr_code = 'PKG-' . strtoupper(Str::random(10));
        }

        $prod->update([
            'status'      => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'Registro aprobado para Pre-Levantamiento.');
    }

    public function bulkApprove(Request $request)
    {
        $request->validate(['ids' => 'required|array|min:1']);
        $count = 0;
        foreach ($request->ids as $id) {
            $prod = BagProduction::find($id);
            if ($prod && $prod->status !== 'approved') {
                if (empty($prod->qr_code)) {
                    $prod->qr_code = 'PKG-' . strtoupper(Str::random(10));
                }
                $prod->status = 'approved';
                $prod->reviewed_by = auth()->id();
                $prod->reviewed_at = now();
                $prod->save();
                $count++;
            }
        }
        return back()->with('status', "Se aprobaron {$count} registros para Pre-Levantamiento.");
    }

    public function adjust(Request $request, $id)
    {
        $prod = BagProduction::findOrFail($id);

        $request->validate([
            'product_id'     => 'nullable|exists:bag_products,id',
            'user_id'        => 'nullable|exists:users,id',
            'quantity'       => 'nullable|numeric|min:0.01',
            'weight'         => 'nullable|numeric|min:0.01',
            'rolls'          => 'nullable|array',
            'rolls.*.weight' => 'nullable|numeric|min:0.01',
            'rolls.*.color'  => 'nullable|string',
            'rolls.*.batch'  => 'nullable|string',
        ]);

        if ($request->filled('product_id')) {
            $prod->product_id = $request->product_id;
        }

        if ($request->filled('user_id')) {
            $prod->user_id = $request->user_id;
        }

        if ($request->has('rolls') && is_array($request->rolls)) {
            $cleanRolls = [];
            $sumWeight = 0;
            foreach ($request->rolls as $r) {
                $w = (float)($r['weight'] ?? 0);
                if ($w > 0) {
                    $cleanRolls[] = [
                        'weight' => $w,
                        'color'  => trim($r['color'] ?? ''),
                        'batch'  => trim($r['batch'] ?? ''),
                    ];
                    $sumWeight += $w;
                }
            }

            if (!empty($cleanRolls)) {
                $prod->metadata = $cleanRolls;
                $prod->quantity = count($cleanRolls);
                if (is_null($prod->original_weight) && (float)$prod->weight !== (float)$sumWeight) {
                    $prod->original_weight = $prod->weight;
                }
                $prod->weight = $sumWeight;
            } else {
                $prod->metadata = null;
                if ($request->filled('quantity')) $prod->quantity = $request->quantity;
                if ($request->filled('weight')) {
                    if (is_null($prod->original_weight) && (float)$prod->weight !== (float)$request->weight) {
                        $prod->original_weight = $prod->weight;
                    }
                    $prod->weight = $request->weight;
                }
            }
        } else {
            if ($request->filled('quantity')) $prod->quantity = $request->quantity;
            if ($request->filled('weight')) {
                if (is_null($prod->original_weight) && (float)$prod->weight !== (float)$request->weight) {
                    $prod->original_weight = $prod->weight;
                }
                $prod->weight = $request->weight;
            }
        }

        $prod->reviewed_by = auth()->id();
        $prod->save();
        $prod->shift?->recalculateTotals();

        return back()->with('status', 'Registro y desglose de bobinas actualizado correctamente.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['rejection_reason' => 'required|string|min:3']);
        $prod = BagProduction::findOrFail($id);
        $prod->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'reviewed_by'      => auth()->id(),
            'reviewed_at'      => now(),
        ]);
        $prod->shift?->recalculateTotals();
        return back()->with('status', 'Registro rechazado por calidad.');
    }

    public function ticket($id)
    {
        $prod = BagProduction::with(['user', 'product', 'shift.machine', 'reviewer'])->findOrFail($id);
        return view('bag_factory.ticket', compact('prod'));
    }

    // ==================== REPORTES HISTÓRICOS ====================
    public function reportsIndex(Request $request)
    {
        $settings = BagCostSetting::getSettings();
        $query = BagProduction::with(['user', 'product.formula.currentVersion', 'shift.machine', 'reviewer', 'lifter'])
            ->orderBy('recorded_at', 'desc');

        if ($request->filled('start_date')) {
            $query->whereDate('recorded_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('recorded_at', '<=', $request->end_date);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('machine_id')) {
            $query->whereHas('shift', function ($q) use ($request) {
                $q->where('machine_id', $request->machine_id);
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $allProductions = $query->get();

        $groupedByDay = $allProductions->groupBy(function ($item) {
            return $item->recorded_at ? $item->recorded_at->format('Y-m-d') : 'Sin Fecha';
        });

        $users = User::all();
        $products = BagProduct::all();
        $allMachines = BagMachine::where('is_active', true)->orderBy('name')->get();

        $totalKg = (float)$allProductions->sum('weight');
        $totalPkgs = (float)$allProductions->sum('quantity');

        // Cálculo de KPIs Financieros del Reporte
        $totalIncome = 0.0;
        $totalRawCost = 0.0;
        $uniqueShiftIds = [];

        foreach ($allProductions as $prod) {
            if ($prod->bag_shift_id) {
                $uniqueShiftIds[$prod->bag_shift_id] = true;
            }
            $pModel = $prod->product;
            if (!$pModel) continue;

            $qty = (float)$prod->quantity;
            $weight = (float)$prod->weight;
            $unitPrice = (float)($pModel->price > 0 ? $pModel->price : $pModel->simulateFactoryPriceFromDailyTarget());

            if ($pModel->is_variable_quantity) {
                $totalIncome += ($weight * $unitPrice);
                $totalRawCost += ($pModel->calculateRawMaterialCost() * $weight);
            } else {
                $totalIncome += ($qty * $unitPrice);
                $totalRawCost += ($qty * $pModel->calculateRawMaterialCost());
            }
        }

        $shiftFixedRate = (float)$settings->shift_fixed_cost;
        $totalFixedCost = count($uniqueShiftIds) * $shiftFixedRate;
        $totalCost = $totalRawCost + $totalFixedCost;
        $netProfit = $totalIncome - $totalCost;
        $marginPercent = $totalIncome > 0 ? round(($netProfit / $totalIncome) * 100.0, 2) : 0.0;

        $financials = [
            'total_income'     => $totalIncome,
            'total_raw_cost'   => $totalRawCost,
            'total_fixed_cost' => $totalFixedCost,
            'total_cost'       => $totalCost,
            'net_profit'       => $netProfit,
            'margin_percent'   => $marginPercent,
            'total_shifts'     => count($uniqueShiftIds),
        ];

        return view('reports.index', compact('groupedByDay', 'users', 'products', 'allMachines', 'totalKg', 'totalPkgs', 'allProductions', 'financials', 'settings'));
    }

    public function reportsPdf(Request $request)
    {
        $settings = BagCostSetting::getSettings();
        $query = BagProduction::with(['user', 'product.formula.currentVersion', 'shift.machine', 'reviewer', 'lifter'])
            ->orderBy('recorded_at', 'desc');

        if ($request->filled('start_date')) {
            $query->whereDate('recorded_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('recorded_at', '<=', $request->end_date);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('machine_id')) {
            $query->whereHas('shift', function ($q) use ($request) {
                $q->where('machine_id', $request->machine_id);
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $allProductions = $query->get();

        $groupedByDay = $allProductions->groupBy(function ($item) {
            return $item->recorded_at ? $item->recorded_at->format('Y-m-d') : 'Sin Fecha';
        });

        $totalKg = (float)$allProductions->sum('weight');
        $totalPkgs = (float)$allProductions->sum('quantity');

        // Cálculo de KPIs Financieros para el PDF
        $totalIncome = 0.0;
        $totalRawCost = 0.0;
        $uniqueShiftIds = [];

        foreach ($allProductions as $prod) {
            if ($prod->bag_shift_id) {
                $uniqueShiftIds[$prod->bag_shift_id] = true;
            }
            $pModel = $prod->product;
            if (!$pModel) continue;

            $qty = (float)$prod->quantity;
            $weight = (float)$prod->weight;
            $unitPrice = (float)($pModel->price > 0 ? $pModel->price : $pModel->simulateFactoryPriceFromDailyTarget());

            if ($pModel->is_variable_quantity) {
                $totalIncome += ($weight * $unitPrice);
                $totalRawCost += ($pModel->calculateRawMaterialCost() * $weight);
            } else {
                $totalIncome += ($qty * $unitPrice);
                $totalRawCost += ($qty * $pModel->calculateRawMaterialCost());
            }
        }

        $shiftFixedRate = (float)$settings->shift_fixed_cost;
        $totalFixedCost = count($uniqueShiftIds) * $shiftFixedRate;
        $totalCost = $totalRawCost + $totalFixedCost;
        $netProfit = $totalIncome - $totalCost;
        $marginPercent = $totalIncome > 0 ? round(($netProfit / $totalIncome) * 100.0, 2) : 0.0;

        $financials = [
            'total_income'     => $totalIncome,
            'total_raw_cost'   => $totalRawCost,
            'total_fixed_cost' => $totalFixedCost,
            'total_cost'       => $totalCost,
            'net_profit'       => $netProfit,
            'margin_percent'   => $marginPercent,
            'total_shifts'     => count($uniqueShiftIds),
        ];

        return view('reports.pdf', compact('groupedByDay', 'totalKg', 'totalPkgs', 'allProductions', 'financials', 'settings'));
    }
}
