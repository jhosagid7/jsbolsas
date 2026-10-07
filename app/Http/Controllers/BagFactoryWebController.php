<?php

namespace App\Http\Controllers;

use App\Models\BagCostSetting;
use App\Models\BagLabelPrint;
use App\Models\BagMachine;
use App\Models\BagMachineIncident;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BagFactoryWebController extends Controller
{
    // ==================== AUTENTICACIÓN ====================
    public function showLogin()
    {
        if (Auth::check()) {
            if (Auth::user()?->isOperator()) {
                return redirect()->route('operator.station');
            }
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
            if (Auth::user()?->isOperator()) {
                return redirect()->route('operator.station');
            }
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

    protected function authorizeAdminOrSupervisor(): void
    {
        if (Auth::check() && Auth::user()->isOperator()) {
            abort(403, 'Acceso denegado: este módulo está reservado para supervisión y administración.');
        }
    }

    // ==================== DASHBOARD & RENDIMIENTO EN VIVO ====================
    public function dashboard(Request $request)
    {
        if (Auth::user()?->isOperator()) {
            return redirect()->route('operator.station');
        }

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
        $shiftQuery = BagShift::with(['user', 'machine', 'productions.user', 'productions.product.formula.currentVersion'])
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

        // 3. Balance Financiero & Meta Global de Producción de la Fábrica
        $todayIncome = (float)$activeShiftsList->sum('total_income');
        $todayCost = (float)$activeShiftsList->sum('total_production_cost');
        $todayFixedCost = (float)$activeShiftsList->sum('fixed_operational_cost');
        $todayRawCost = max(0, $todayCost - $todayFixedCost);
        $todayNetProfit = $todayIncome - $todayCost;
        $todayMarginPercent = $todayIncome > 0 ? round(($todayNetProfit / $todayIncome) * 100.0, 2) : 0.0;
        
        $dailyTarget = (float)$settings->daily_profit_target * $daysCount;
        $targetProfitPercent = $dailyTarget > 0 ? round(($todayNetProfit / $dailyTarget) * 100.0, 1) : 0.0;

        // Metas de Producción Global de la Fábrica (Suma de cuotas de todos los turnos abiertos/activos en el período)
        $factoryTargetPackages = (float)$activeShiftsList->sum('target_packages');
        $factoryRealPackages = (float)$activeShiftsList->sum('total_packages');
        $factoryRealWeight = (float)$activeShiftsList->sum('total_weight');
        
        $factoryTargetProgressPercent = $factoryTargetPackages > 0 
            ? round(($factoryRealPackages / $factoryTargetPackages) * 100.0, 1) 
            : 0.0;
            
        $activeOperatorsCount = $activeShiftsList->count();
        $metOperatorsCount = $activeShiftsList->filter(function($s) {
            $t = (float)($s->target_packages > 0 ? $s->target_packages : 5.0);
            return (float)$s->total_packages >= $t;
        })->count();
        $isFactoryTargetMet = $factoryTargetPackages > 0 && ($factoryRealPackages >= $factoryTargetPackages);

        // Métricas de Jornada Global y Tiempos de Planta
        $hasOpenShifts = $activeShiftsList->contains('status', 'open');
        $earliestStart = $activeShiftsList->min('start_time');
        $latestEnd = $activeShiftsList->max('end_time');

        $plantStatus = $activeShiftsList->isEmpty() 
            ? 'idle' 
            : ($hasOpenShifts ? 'open' : 'closed');

        $plantStatusLabel = match($plantStatus) {
            'open'   => '🟢 Planta en Operación',
            'closed' => '⚪ Jornada Concluida',
            default  => '⏸️ Sin Turnos Activos',
        };

        $plantOperationMinutes = 0;
        if ($earliestStart) {
            $effectiveEnd = $hasOpenShifts ? now() : ($latestEnd ?: now());
            $plantOperationMinutes = max(0, (int)$earliestStart->diffInMinutes($effectiveEnd));
        }
        $plantOpHours = intdiv($plantOperationMinutes, 60);
        $plantOpMins = $plantOperationMinutes % 60;
        $plantOperationHuman = sprintf('%dh %02dm', $plantOpHours, $plantOpMins) . ($hasOpenShifts ? ' (En curso)' : '');

        $totalManMinutes = 0;
        foreach ($activeShiftsList as $s) {
            $sStart = $s->start_time;
            if ($sStart) {
                $sEnd = $s->end_time ?: now();
                $totalManMinutes += max(0, (int)$sStart->diffInMinutes($sEnd));
            }
        }
        $manHours = intdiv($totalManMinutes, 60);
        $manMins = $totalManMinutes % 60;
        $plantManHoursHuman = sprintf('%dh %02dm', $manHours, $manMins);

        $plantScheduleWindow = $earliestStart 
            ? ($earliestStart->format('h:i A') . ' - ' . ($hasOpenShifts ? 'En Vivo' : ($latestEnd ? $latestEnd->format('h:i A') : 'Cerrado')))
            : 'Sin actividad';

        $financials = [
            'today_income'                    => $todayIncome,
            'today_cost'                      => $todayCost,
            'today_fixed_cost'                => $todayFixedCost,
            'today_raw_cost'                  => $todayRawCost,
            'today_net_profit'                => $todayNetProfit,
            'today_margin_percent'            => $todayMarginPercent,
            'daily_target'                    => $dailyTarget,
            'target_profit_percent'           => max(0, $targetProfitPercent),
            'target_title'                    => $targetTitle,
            'period'                          => $period,
            'period_label'                    => $periodLabel,
            'days_count'                      => $daysCount,
            'start_date'                      => $startDate->format('Y-m-d'),
            'end_date'                        => $endDate->format('Y-m-d'),
            'machine_id'                      => $machineId,
            // Métricas de Producción Global de Planta
            'factory_target_packages'         => $factoryTargetPackages,
            'factory_real_packages'           => $factoryRealPackages,
            'factory_real_weight'             => $factoryRealWeight,
            'factory_target_progress_percent' => $factoryTargetProgressPercent,
            'is_factory_target_met'           => $isFactoryTargetMet,
            'active_operators_count'          => $activeOperatorsCount,
            'met_operators_count'             => $metOperatorsCount,
            // Métricas de Horarios y Horas-Hombre
            'plant_status'                    => $plantStatus,
            'plant_status_label'              => $plantStatusLabel,
            'plant_schedule_window'           => $plantScheduleWindow,
            'plant_operation_human'           => $plantOperationHuman,
            'plant_operation_hours'           => round($plantOperationMinutes / 60.0, 2),
            'plant_man_hours_human'           => $plantManHoursHuman,
            'plant_man_hours'                 => round($totalManMinutes / 60.0, 2),
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

    public function dashboardLiveData(Request $request)
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

        $prodQuery = BagProduction::whereDate('recorded_at', '>=', $startDate)->whereDate('recorded_at', '<=', $endDate);
        if ($machineId) {
            $prodQuery->whereHas('shift', function ($q) use ($machineId) {
                $q->where('machine_id', $machineId);
            });
        }

        $latestProductionId = (int)(BagProduction::max('id') ?? 0);
        $pendingReviewCount = BagProduction::where('status', 'pending_review')->count();

        // 2. Turnos de la Jornada / Período
        $shiftQuery = BagShift::with(['user', 'machine', 'productions.user', 'productions.product.formula.currentVersion'])
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

        // 3. Balance Financiero & Meta Global de Producción de la Fábrica
        $todayIncome = (float)$activeShiftsList->sum('total_income');
        $todayCost = (float)$activeShiftsList->sum('total_production_cost');
        $todayFixedCost = (float)$activeShiftsList->sum('fixed_operational_cost');
        $todayRawCost = max(0, $todayCost - $todayFixedCost);
        $todayNetProfit = $todayIncome - $todayCost;
        $todayMarginPercent = $todayIncome > 0 ? round(($todayNetProfit / $todayIncome) * 100.0, 2) : 0.0;
        
        $dailyTarget = (float)$settings->daily_profit_target * $daysCount;
        $targetProfitPercent = $dailyTarget > 0 ? round(($todayNetProfit / $dailyTarget) * 100.0, 1) : 0.0;

        $factoryTargetPackages = (float)$activeShiftsList->sum('target_packages');
        $factoryRealPackages = (float)$activeShiftsList->sum('total_packages');
        $factoryRealWeight = (float)$activeShiftsList->sum('total_weight');
        
        $factoryTargetProgressPercent = $factoryTargetPackages > 0 
            ? round(($factoryRealPackages / $factoryTargetPackages) * 100.0, 1) 
            : 0.0;
            
        $activeOperatorsCount = $activeShiftsList->count();
        $metOperatorsCount = $activeShiftsList->filter(function($s) {
            $t = (float)($s->target_packages > 0 ? $s->target_packages : 5.0);
            return (float)$s->total_packages >= $t;
        })->count();
        $isFactoryTargetMet = $factoryTargetPackages > 0 && ($factoryRealPackages >= $factoryTargetPackages);

        // Métricas de Jornada Global y Tiempos de Planta
        $hasOpenShifts = $activeShiftsList->contains('status', 'open');
        $earliestStart = $activeShiftsList->min('start_time');
        $latestEnd = $activeShiftsList->max('end_time');

        $plantStatus = $activeShiftsList->isEmpty() 
            ? 'idle' 
            : ($hasOpenShifts ? 'open' : 'closed');

        $plantStatusLabel = match($plantStatus) {
            'open'   => '🟢 Planta en Operación',
            'closed' => '⚪ Jornada Concluida',
            default  => '⏸️ Sin Turnos Activos',
        };

        $plantOperationMinutes = 0;
        if ($earliestStart) {
            $effectiveEnd = $hasOpenShifts ? now() : ($latestEnd ?: now());
            $plantOperationMinutes = max(0, (int)$earliestStart->diffInMinutes($effectiveEnd));
        }
        $plantOpHours = intdiv($plantOperationMinutes, 60);
        $plantOpMins = $plantOperationMinutes % 60;
        $plantOperationHuman = sprintf('%dh %02dm', $plantOpHours, $plantOpMins) . ($hasOpenShifts ? ' (En curso)' : '');

        $totalManMinutes = 0;
        foreach ($activeShiftsList as $s) {
            $sStart = $s->start_time;
            if ($sStart) {
                $sEnd = $s->end_time ?: now();
                $totalManMinutes += max(0, (int)$sStart->diffInMinutes($sEnd));
            }
        }
        $manHours = intdiv($totalManMinutes, 60);
        $manMins = $totalManMinutes % 60;
        $plantManHoursHuman = sprintf('%dh %02dm', $manHours, $manMins);

        $plantScheduleWindow = $earliestStart 
            ? ($earliestStart->format('h:i A') . ' - ' . ($hasOpenShifts ? 'En Vivo' : ($latestEnd ? $latestEnd->format('h:i A') : 'Cerrado')))
            : 'Sin actividad';

        $shiftsFormatted = $activeShiftsList->map(function ($shift) {
            $target = (float)($shift->target_packages > 0 ? $shift->target_packages : 5.0);
            $actual = (float)$shift->total_packages;
            $pct = $target > 0 ? round(($actual / $target) * 100.0, 1) : 100.0;
            $isMet = $actual >= $target;
            $net = (float)$shift->net_profit;

            return [
                'id'                    => $shift->id,
                'user_name'             => $shift->effective_user->name ?? $shift->user->name ?? 'Operario',
                'machine_name'          => $shift->machine?->name,
                'machine_code'          => $shift->machine?->code,
                'shift_type'            => $shift->shift_type,
                'status'                => $shift->status,
                'start_time_human'      => $shift->start_time_human,
                'end_time_human'        => $shift->end_time_human,
                'duration_human'        => $shift->duration_human,
                'target_packages'       => $target,
                'total_packages'        => $actual,
                'total_weight'          => (float)$shift->total_weight,
                'completion_percent'    => $pct,
                'is_met'                => $isMet,
                'remaining_units'       => max(0, (int)($target - $actual)),
                'net_profit'            => $net,
                'profit_margin_percent' => $shift->profit_margin_percent ?? 0,
            ];
        });

        return response()->json([
            'success'              => true,
            'timestamp'            => now()->toIso8601String(),
            'latest_production_id' => $latestProductionId,
            'pending_review'       => $pendingReviewCount,
            'financials'           => [
                'today_income'                    => $todayIncome,
                'today_income_formatted'          => number_format($todayIncome, 2),
                'today_cost'                      => $todayCost,
                'today_cost_formatted'            => number_format($todayCost, 2),
                'today_fixed_cost'                => $todayFixedCost,
                'today_fixed_cost_formatted'      => number_format($todayFixedCost, 2),
                'today_raw_cost'                  => $todayRawCost,
                'today_raw_cost_formatted'        => number_format($todayRawCost, 2),
                'today_net_profit'                => $todayNetProfit,
                'today_net_profit_formatted'      => number_format($todayNetProfit, 2),
                'today_margin_percent'            => $todayMarginPercent,
                'daily_target'                    => $dailyTarget,
                'daily_target_formatted'          => number_format($dailyTarget, 0),
                'target_profit_percent'           => $targetProfitPercent,
                'factory_target_packages'         => $factoryTargetPackages,
                'factory_real_packages'           => $factoryRealPackages,
                'factory_real_weight'             => $factoryRealWeight,
                'factory_real_weight_formatted'   => number_format($factoryRealWeight, 2),
                'factory_target_progress_percent' => $factoryTargetProgressPercent,
                'is_factory_target_met'           => $isFactoryTargetMet,
                'active_operators_count'          => $activeOperatorsCount,
                'met_operators_count'             => $metOperatorsCount,
                // Horas y Estado de Planta
                'plant_status'                    => $plantStatus,
                'plant_status_label'              => $plantStatusLabel,
                'plant_schedule_window'           => $plantScheduleWindow,
                'plant_operation_human'           => $plantOperationHuman,
                'plant_operation_hours'           => round($plantOperationMinutes / 60.0, 2),
                'plant_man_hours_human'           => $plantManHoursHuman,
                'plant_man_hours'                 => round($totalManMinutes / 60.0, 2),
            ],
            'shifts'               => $shiftsFormatted,
        ]);
    }

    // ==================== MATERIAS PRIMAS & HISTORIAL DE PRECIOS ====================
    public function rawMaterialsIndex()
    {
        $this->authorizeAdminOrSupervisor();
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
        $this->authorizeAdminOrSupervisor();
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
        $this->authorizeAdminOrSupervisor();
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
        $isCompositeRolls = $request->boolean('is_composite_rolls', false);
        $suggestedRolls = max(1, (int)($request->input('suggested_rolls_per_package', 9)));

        $product->fill([
            'name'                        => $request->name,
            'category'                    => $request->category,
            'production_formula_id'       => $request->production_formula_id ?: null,
            'sale_unit'                   => $isVariable ? 'KG' : strtoupper($request->sale_unit ?: 'BULTO'),
            'millar_per_bulto'            => $isVariable ? 1.0000 : (float)($request->millar_per_bulto ?: 1),
            'target_units_per_shift'      => $request->target_units_per_shift,
            'target_daily_profit'         => $request->target_daily_profit ?? 105.00,
            'price'                       => $request->price ?? 0,
            'width_inch'                  => $isVariable ? null : $request->width_inch,
            'length_inch'                 => $isVariable ? null : $request->length_inch,
            'gauge_caliber'               => $isVariable ? null : $request->gauge_caliber,
            'real_total_weight_kg'        => $isVariable ? 1.0000 : $request->unit_weight_kg, // Override manual de PESO_R
            'unit_weight_kg'              => $isVariable ? 1.0000 : $request->unit_weight_kg,
            'sku'                         => strtoupper($request->sku ?? $product->sku),
            'is_variable_quantity'        => $isVariable,
            'is_composite_rolls'          => $isVariable && $isCompositeRolls,
            'suggested_rolls_per_package' => $suggestedRolls,
        ]);

        $product->recalculateAndSavePrices();

        // Recalcular ÚNICAMENTE producciones pendientes de revisión en borrador (protegiendo lotes aprobados y despachados)
        $product->productions()
            ->where('status', 'pending_review')
            ->whereNull('lifted_at')
            ->with(['user', 'product'])
            ->get()
            ->each(function ($prod) {
                $prod->recalculateLaborAndQuality();
            });

        return back()->with('status', "Ficha técnica, costo de materia prima y precios simulados de '{$product->name}' guardados correctamente.");
    }

    // ==================== GESTIÓN DE USUARIOS Y ROLES ====================
    public function usersIndex()
    {
        $this->authorizeAdminOrSupervisor();
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
            'name'                 => 'required|string|max:255',
            'email'                => 'required|email|unique:users,email',
            'password'             => 'required|string|min:6',
            'role'                 => 'required|in:' . implode(',', $allowedRoles),
            'weekly_salary'        => 'nullable|numeric|min:0',
            'work_days_per_week'   => 'nullable|integer|in:5,6,7',
            'pay_partial_packages' => 'nullable|boolean',
        ]);

        User::create([
            'name'                 => $request->name,
            'email'                => $request->email,
            'password'             => Hash::make($request->password),
            'role'                 => $request->role,
            'weekly_salary'        => $request->weekly_salary ?? 90.00,
            'work_days_per_week'   => $request->work_days_per_week ?? 6,
            'pay_partial_packages' => $request->boolean('pay_partial_packages', false),
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
            'name'                 => 'required|string|max:255',
            'email'                => 'required|email|unique:users,email,' . $id,
            'role'                 => 'required|in:' . implode(',', $allowedRoles),
            'password'             => 'nullable|string|min:6',
            'weekly_salary'        => 'nullable|numeric|min:0',
            'work_days_per_week'   => 'nullable|integer|in:5,6,7',
            'pay_partial_packages' => 'nullable|boolean',
        ]);

        if ($user->isSuperAdmin() && !Auth::user()?->isSuperAdmin()) {
            return back()->with('error', 'Solo el Super Administrador puede modificar esta cuenta.');
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;
        if ($request->filled('weekly_salary')) {
            $user->weekly_salary = $request->weekly_salary;
        }
        if ($request->filled('work_days_per_week')) {
            $user->work_days_per_week = $request->work_days_per_week;
        }
        if ($request->has('pay_partial_packages')) {
            $user->pay_partial_packages = $request->boolean('pay_partial_packages');
        }
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
        $machines = BagMachine::withCount(['shifts', 'incidents'])
            ->orderBy('name')
            ->get();
        return view('machines.index', compact('machines'));
    }

    public function machinesShow($id)
    {
        $machine = BagMachine::with(['shifts.user', 'incidents.user', 'incidents.product', 'incidents.resolver'])->findOrFail($id);

        $allApproved = $machine->allProductionsQuery()
            ->where('status', 'approved');

        $totalKg = (float)$allApproved->sum('weight');
        $totalUnits = (float)$allApproved->sum('quantity');
        $totalBatches = (int)$allApproved->count();
        $totalShifts = $machine->shifts()->count();

        $totalIncidents = $machine->incidents()->count();
        $openIncidents = $machine->incidents()->where('status', '!=', 'resuelta')->count();

        // Desglose por producto fabricado en esta máquina
        $productsBreakdown = $machine->allProductionsQuery()
            ->where('status', 'approved')
            ->selectRaw('product_id, SUM(quantity) as total_qty, SUM(weight) as total_weight, COUNT(*) as batches_count')
            ->groupBy('product_id')
            ->with('product')
            ->get();

        // Historial cronológico de producciones
        $productionsHistory = $machine->allProductionsQuery()
            ->with(['user', 'product', 'reviewer', 'shift'])
            ->orderBy('recorded_at', 'desc')
            ->paginate(20);

        // Novedades de calidad
        $incidents = $machine->incidents()
            ->with(['user', 'product', 'resolver', 'production'])
            ->latest()
            ->get();

        $allProducts = BagProduct::where('is_active', true)->orderBy('name')->get();

        return view('machines.show', compact(
            'machine',
            'totalKg',
            'totalUnits',
            'totalBatches',
            'totalShifts',
            'totalIncidents',
            'openIncidents',
            'productsBreakdown',
            'productionsHistory',
            'incidents',
            'allProducts'
        ));
    }

    public function machinesReportIncident(Request $request, $id)
    {
        $machine = BagMachine::findOrFail($id);

        $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'required|string',
            'incident_type' => 'required|in:mecanica,formula,operacion,otra',
            'severity'      => 'required|in:baja,media,alta,critica',
            'product_id'    => 'nullable|exists:bag_products,id',
            'batch_code'    => 'nullable|string|max:100',
            'production_id' => 'nullable|exists:bag_productions,id',
        ]);

        $rootCause = match ($request->incident_type) {
            'mecanica'  => 'maquina',
            'formula'   => 'formula',
            'operacion' => 'operador',
            default     => 'desconocido',
        };

        BagMachineIncident::create([
            'machine_id'          => $machine->id,
            'production_id'       => $request->production_id,
            'product_id'          => $request->product_id,
            'user_id'             => Auth::id() ?? 1,
            'incident_type'       => $request->incident_type,
            'severity'            => $request->severity,
            'title'               => $request->title,
            'description'         => $request->description,
            'batch_code'          => $request->batch_code,
            'root_cause_analysis' => $rootCause,
            'status'              => 'abierta',
        ]);

        return back()->with('status', 'Novedad de calidad reportada exitosamente.');
    }

    public function machinesResolveIncident(Request $request, $id, $incidentId)
    {
        $incident = BagMachineIncident::where('machine_id', $id)->findOrFail($incidentId);

        $request->validate([
            'resolution_notes' => 'required|string',
        ]);

        $incident->update([
            'status'           => 'resuelta',
            'resolution_notes' => $request->resolution_notes,
            'resolved_by'      => Auth::id(),
            'resolved_at'      => now(),
        ]);

        return back()->with('status', 'Novedad marcada como resuelta.');
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
        $prod = BagProduction::with(['product', 'shift.user'])->findOrFail($id);

        // Si la producción contiene más de 1 unidad (bultos o múltiples bobinas homogéneas), se divide en unidades individuales
        if ($prod->quantity > 1 && !$prod->product?->is_composite_rolls) {
            $totalQty = (int)$prod->quantity;
            $totalWeight = (float)$prod->weight;
            $rolls = [];

            if (!empty($prod->metadata)) {
                if (is_array($prod->metadata) && isset($prod->metadata['rolls']) && is_array($prod->metadata['rolls'])) {
                    $rolls = $prod->metadata['rolls'];
                } elseif (is_array($prod->metadata) && isset($prod->metadata[0]['weight'])) {
                    $rolls = $prod->metadata;
                }
            }

            DB::beginTransaction();
            try {
                for ($i = 0; $i < $totalQty; $i++) {
                    $newProd = $prod->replicate();
                    $newProd->quantity = 1.0;
                    $rollWeight = isset($rolls[$i]['weight']) && (float)$rolls[$i]['weight'] > 0
                        ? (float)$rolls[$i]['weight']
                        : round($totalWeight / $totalQty, 2);
                    $newProd->weight = $rollWeight;
                    $newProd->qr_code = 'PKG-' . strtoupper(Str::random(10));
                    $newProd->status = 'approved';
                    $newProd->reviewed_at = now();
                    $newProd->reviewed_by = auth()->id();
                    $newProd->sync_id = 'PROD-SPLIT-' . Str::uuid();
                    $newProd->metadata = isset($rolls[$i]) ? ['roll' => $rolls[$i]] : null;
                    $newProd->save();
                }
                $prod->delete();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return back()->with('error', 'Error al dividir registros: ' . $e->getMessage());
            }

            return back()->with('status', "Lote dividido y aprobado en {$totalQty} unidades individuales con éxito.");
        }

        // Lógica estándar para cantidad = 1 o Bulto Compuesto de Bobinas
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

        DB::transaction(function () use ($request, &$count) {
            foreach ($request->ids as $id) {
                $prod = BagProduction::with(['product', 'shift.user'])->find($id);
                if ($prod && $prod->status !== 'approved') {
                    if ($prod->quantity > 1 && !$prod->product?->is_composite_rolls) {
                        $totalQty = (int)$prod->quantity;
                        $totalWeight = (float)$prod->weight;
                        $rolls = [];

                        if (!empty($prod->metadata)) {
                            if (is_array($prod->metadata) && isset($prod->metadata['rolls']) && is_array($prod->metadata['rolls'])) {
                                $rolls = $prod->metadata['rolls'];
                            } elseif (is_array($prod->metadata) && isset($prod->metadata[0]['weight'])) {
                                $rolls = $prod->metadata;
                            }
                        }

                        for ($i = 0; $i < $totalQty; $i++) {
                            $newProd = $prod->replicate();
                            $newProd->quantity = 1.0;
                            $rollWeight = isset($rolls[$i]['weight']) && (float)$rolls[$i]['weight'] > 0
                                ? (float)$rolls[$i]['weight']
                                : round($totalWeight / $totalQty, 2);
                            $newProd->weight = $rollWeight;
                            $newProd->qr_code = 'PKG-' . strtoupper(Str::random(10));
                            $newProd->status = 'approved';
                            $newProd->reviewed_at = now();
                            $newProd->reviewed_by = auth()->id();
                            $newProd->sync_id = 'PROD-SPLIT-' . Str::uuid();
                            $newProd->metadata = isset($rolls[$i]) ? ['roll' => $rolls[$i]] : null;
                            $newProd->save();
                            $count++;
                        }
                        $prod->delete();
                    } else {
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
            }
        });

        return back()->with('status', "Se aprobaron {$count} unidades para Pre-Levantamiento.");
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

    public function ticket($id, Request $request)
    {
        $prod = BagProduction::with(['user', 'product', 'shift.user', 'reviewer'])->findOrFail($id);
        $isVariable = (bool)($prod->product?->is_variable_quantity);
        $isComposite = $isVariable && (bool)($prod->product?->is_composite_rolls);
        $defaultScope = $isComposite ? 'kit' : 'millar';

        if (!$prod->is_printed) {
            $prod->update([
                'is_printed' => true,
                'printed_at' => now(),
            ]);
        }

        $scope = $request->get('scope', $defaultScope);
        $labels = $this->formatLabelsList($prod, $scope);
        $pdfUrl = route('ticket.pdf', ['id' => $id, 'scope' => $scope]);
        return view('bag_factory.ticket', compact('labels', 'pdfUrl', 'scope', 'prod', 'isComposite'));
    }

    public function ticketPdf($id, Request $request)
    {
        $prod = BagProduction::with(['user', 'product', 'shift.user', 'reviewer'])->findOrFail($id);
        $isVariable = (bool)($prod->product?->is_variable_quantity);
        $isComposite = $isVariable && (bool)($prod->product?->is_composite_rolls);
        $defaultScope = $isComposite ? 'kit' : 'millar';

        if (!$prod->is_printed) {
            $prod->update([
                'is_printed' => true,
                'printed_at' => now(),
            ]);
        }

        $scope = $request->get('scope', $defaultScope);
        $labels = $this->formatLabelsList($prod, $scope);
        $this->logLabelsPrint($labels);
        $size = $request->get('size', '80mm');

        return $this->renderPdfResponse($labels, $size, "etiquetas_{$id}.pdf");
    }

    public function printShiftLabels($shift_id, Request $request)
    {
        $productions = BagProduction::where('bag_shift_id', $shift_id)
            ->where('status', 'approved')
            ->with(['user', 'product', 'shift.user', 'reviewer'])
            ->orderBy('recorded_at', 'asc')
            ->get();

        $scope = $request->get('scope', 'millar');
        $labels = [];
        foreach ($productions as $p) {
            foreach ($this->formatLabelsList($p, $scope) as $lbl) {
                $labels[] = $lbl;
            }
        }
        $pdfUrl = route('ticket.shift.pdf', ['shift_id' => $shift_id, 'scope' => $scope]);
        return view('bag_factory.ticket', compact('labels', 'pdfUrl', 'scope'));
    }

    public function printShiftLabelsPdf($shift_id, Request $request)
    {
        $productions = BagProduction::where('bag_shift_id', $shift_id)
            ->where('status', 'approved')
            ->with(['user', 'product', 'shift.user', 'reviewer'])
            ->orderBy('recorded_at', 'asc')
            ->get();

        $scope = $request->get('scope', 'millar');
        $labels = [];
        foreach ($productions as $p) {
            foreach ($this->formatLabelsList($p, $scope) as $lbl) {
                $labels[] = $lbl;
            }
        }
        $this->logLabelsPrint($labels);
        $size = $request->get('size', '80mm');

        return $this->renderPdfResponse($labels, $size, "etiquetas_turno_{$shift_id}.pdf");
    }

    public function printBatchLabels(Request $request)
    {
        $ids = $request->get('ids', []);
        if (is_string($ids)) {
            $ids = array_filter(explode(',', $ids));
        }

        $productions = BagProduction::whereIn('id', $ids)
            ->where('status', 'approved')
            ->with(['user', 'product', 'shift.user', 'reviewer'])
            ->orderBy('recorded_at', 'asc')
            ->get();

        $scope = $request->get('scope', 'millar');
        $labels = [];
        foreach ($productions as $p) {
            foreach ($this->formatLabelsList($p, $scope) as $lbl) {
                $labels[] = $lbl;
            }
        }
        $pdfUrl = route('ticket.batch.pdf', ['ids' => implode(',', (array)$ids), 'scope' => $scope]);
        return view('bag_factory.ticket', compact('labels', 'pdfUrl', 'scope'));
    }

    public function printBatchLabelsPdf(Request $request)
    {
        $ids = $request->get('ids', []);
        if (is_string($ids)) {
            $ids = array_filter(explode(',', $ids));
        }

        $productions = BagProduction::whereIn('id', $ids)
            ->where('status', 'approved')
            ->with(['user', 'product', 'shift.user', 'reviewer'])
            ->orderBy('recorded_at', 'asc')
            ->get();

        $scope = $request->get('scope', 'millar');
        $labels = [];
        foreach ($productions as $p) {
            foreach ($this->formatLabelsList($p, $scope) as $lbl) {
                $labels[] = $lbl;
            }
        }
        $this->logLabelsPrint($labels);
        $size = $request->get('size', '80mm');

        return $this->renderPdfResponse($labels, $size, "etiquetas_lote.pdf");
    }

    public function formatLabelsList(BagProduction $prod, string $scope = 'millar'): array
    {
        $isVariable = (bool)($prod->product?->is_variable_quantity);
        $saleUnit = strtoupper($prod->product?->sale_unit ?? 'BULTO');
        $qty = max(1, (int)$prod->quantity);
        $totalWeight = (float)$prod->weight;
        $millarPerBulto = (float)($prod->product?->millar_per_bulto > 0 ? $prod->product->millar_per_bulto : 1.0);

        $rolls = [];
        if (!empty($prod->metadata)) {
            if (is_array($prod->metadata) && isset($prod->metadata['rolls']) && is_array($prod->metadata['rolls'])) {
                $rolls = $prod->metadata['rolls'];
            } elseif (is_array($prod->metadata) && isset($prod->metadata['roll']) && is_array($prod->metadata['roll'])) {
                $rolls = [$prod->metadata['roll']];
            } elseif (is_array($prod->metadata) && isset($prod->metadata[0]['weight'])) {
                $rolls = $prod->metadata;
            }
        }

        $labels = [];

        $operatorName = $prod->shift?->user?->name ?? $prod->user?->name ?? 'Operador Planta';
        $prodDate = $prod->recorded_at ? $prod->recorded_at->format('d/m/Y') : ($prod->created_at ? $prod->created_at->format('d/m/Y') : date('d/m/Y'));
        $approverName = $prod->reviewer?->name ?? (Auth::user()?->name ?? 'Supervisor');
        $effectiveMachine = $prod->effective_machine;
        $machineCode = $effectiveMachine?->code ?? ('M' . ($effectiveMachine?->id ?? '1'));
        $dateCode = $prod->recorded_at ? $prod->recorded_at->format('ymd') : ($prod->created_at ? $prod->created_at->format('ymd') : date('ymd'));
        
        // Clasificación de Calidad por Tolerancia (Grado A, B, C)
        $grade = strtoupper($prod->weight_quality_grade ?: 'B');
        $gradeDesc = match($grade) {
            'A' => 'Sobrepeso',
            'C' => 'Subcalibre',
            default => 'Óptimo',
        };
        $gradeLabel = match($grade) {
            'A' => 'GRADO A (Sobrepeso)',
            'C' => 'GRADO C (Subcalibre)',
            default => 'GRADO B (Óptimo)',
        };
        $batchCode = $prod->effective_batch_code ?: ("L{$dateCode}-{$machineCode}-{$grade}");

        $sku = $prod->product?->sku ?? 'S/SKU';
        $prodName = mb_strtoupper($prod->product?->name ?? 'PRODUCTO', 'UTF-8');
        $baseQr = $prod->qr_code ?: ('PKG-' . strtoupper(Str::random(10)));

        if ($isVariable) {
            $isComposite = ($qty > 1 || (bool)$prod->product?->is_composite_rolls || count($rolls) > 1);

            if ($scope === 'bulto' && $isComposite) {
                // Etiqueta Master de Bulto de Bobinas
                $labels[] = [
                    'id'                => $prod->id,
                    'index'             => 1,
                    'total_in_batch'    => 1,
                    'product_name'      => $prodName,
                    'presentation_info' => "1 BULTO ({$qty} BOBINAS)",
                    'operator_name'     => $operatorName,
                    'production_date'   => $prodDate,
                    'approver_name'     => $approverName,
                    'is_variable'       => true,
                    'weight_kg'         => $totalWeight,
                    'batch_code'        => $batchCode,
                    'quality_grade'     => $grade,
                    'grade_desc'        => $gradeDesc,
                    'grade_label'       => $gradeLabel,
                    'qr_code'           => $baseQr,
                    'sku'               => $sku,
                    'label_type'        => 'bulto',
                ];
            } elseif ($scope === 'kit' && $isComposite) {
                // Kit Completo: 1 Etiqueta Master Bulto + N Etiquetas Hijas
                $totalLabels = $qty + 1;
                $currentIndex = 1;

                // 1. Master Bulto
                $labels[] = [
                    'id'                => $prod->id,
                    'index'             => $currentIndex,
                    'total_in_batch'    => $totalLabels,
                    'product_name'      => $prodName,
                    'presentation_info' => "1 BULTO ({$qty} BOBINAS)",
                    'operator_name'     => $operatorName,
                    'production_date'   => $prodDate,
                    'approver_name'     => $approverName,
                    'is_variable'       => true,
                    'weight_kg'         => $totalWeight,
                    'batch_code'        => $batchCode,
                    'quality_grade'     => $grade,
                    'grade_desc'        => $gradeDesc,
                    'grade_label'       => $gradeLabel,
                    'qr_code'           => $baseQr,
                    'sku'               => $sku,
                    'label_type'        => 'bulto',
                ];

                // 2. N Bobinas Hijas
                for ($i = 0; $i < $qty; $i++) {
                    $currentIndex++;
                    $rollWeight = isset($rolls[$i]['weight']) && (float)$rolls[$i]['weight'] > 0
                        ? (float)$rolls[$i]['weight']
                        : round($totalWeight / $qty, 2);

                    $labels[] = [
                        'id'                => $prod->id,
                        'index'             => $currentIndex,
                        'total_in_batch'    => $totalLabels,
                        'product_name'      => "{$prodName} - BOBINA " . ($i + 1) . "/{$qty}",
                        'presentation_info' => "BOBINA - PESO VARIABLE",
                        'operator_name'     => $operatorName,
                        'production_date'   => $prodDate,
                        'approver_name'     => $approverName,
                        'is_variable'       => true,
                        'weight_kg'         => $rollWeight,
                        'batch_code'        => $batchCode,
                        'quality_grade'     => $grade,
                        'grade_desc'        => $gradeDesc,
                        'grade_label'       => $gradeLabel,
                        'qr_code'           => "{$baseQr}-R" . ($i + 1),
                        'sku'               => $sku,
                        'label_type'        => 'bobina',
                    ];
                }
            } else {
                // Modo Individual (o bobina única suelta)
                for ($i = 0; $i < $qty; $i++) {
                    $rollWeight = isset($rolls[$i]['weight']) && (float)$rolls[$i]['weight'] > 0
                        ? (float)$rolls[$i]['weight']
                        : ($qty > 1 ? round($totalWeight / $qty, 2) : $totalWeight);

                    $presentation = ($qty > 1 ? "BOBINA #" . ($i + 1) . " - PESO VARIABLE" : "BOBINA - PESO VARIABLE");
                    $qrCode = ($i === 0 && !empty($prod->qr_code) && $qty === 1)
                        ? $prod->qr_code
                        : ($qty > 1 ? "{$baseQr}-R" . ($i + 1) : $baseQr);

                    $labels[] = [
                        'id'                => $prod->id,
                        'index'             => $i + 1,
                        'total_in_batch'    => $qty,
                        'product_name'      => $qty > 1 ? "{$prodName} - BOBINA " . ($i + 1) . "/{$qty}" : $prodName,
                        'presentation_info' => $presentation,
                        'operator_name'     => $operatorName,
                        'production_date'   => $prodDate,
                        'approver_name'     => $approverName,
                        'is_variable'       => true,
                        'weight_kg'         => $rollWeight,
                        'batch_code'        => $batchCode,
                        'quality_grade'     => $grade,
                        'grade_desc'        => $gradeDesc,
                        'grade_label'       => $gradeLabel,
                        'qr_code'           => $qrCode,
                        'sku'               => $sku,
                        'label_type'        => 'bobina',
                    ];
                }
            }
        } else {
            // Productos estándar (Bolsas homogéneas)
            // 1. Caso: Modo Bulto (Etiqueta Master Exterior)
            if ($scope === 'bulto') {
                for ($b = 0; $b < $qty; $b++) {
                    $bultoQr = $qty > 1 ? "{$baseQr}-B" . ($b + 1) : $baseQr;
                    $pres = $millarPerBulto > 1 ? "1 BULTO (" . (int)$millarPerBulto . " MILLARES)" : "1 {$saleUnit}";
                    $labels[] = [
                        'id'                => $prod->id,
                        'index'             => $b + 1,
                        'total_in_batch'    => $qty,
                        'product_name'      => $prodName,
                        'presentation_info' => $pres,
                        'operator_name'     => $operatorName,
                        'production_date'   => $prodDate,
                        'approver_name'     => $approverName,
                        'is_variable'       => false,
                        'weight_kg'         => 0.0,
                        'batch_code'        => $batchCode,
                        'quality_grade'     => $grade,
                        'grade_desc'        => $gradeDesc,
                        'grade_label'       => $gradeLabel,
                        'qr_code'           => $bultoQr,
                        'sku'               => $sku,
                        'label_type'        => 'bulto',
                    ];
                }
            } elseif ($scope === 'kit') {
                // 2. Caso: Kit Completo (Bulto Exterior + Millares Interiores)
                $currentIndex = 0;
                $totalLabels = $qty * ($millarPerBulto > 1 ? ((int)$millarPerBulto + 1) : 1);

                for ($b = 0; $b < $qty; $b++) {
                    $bultoQr = $qty > 1 ? "{$baseQr}-B" . ($b + 1) : $baseQr;
                    if ($millarPerBulto > 1) {
                        // Etiqueta exterior de bulto
                        $currentIndex++;
                        $labels[] = [
                            'id'                => $prod->id,
                            'index'             => $currentIndex,
                            'total_in_batch'    => $totalLabels,
                            'product_name'      => $prodName,
                            'presentation_info' => "1 BULTO (" . (int)$millarPerBulto . " MILLARES)",
                            'operator_name'     => $operatorName,
                            'production_date'   => $prodDate,
                            'approver_name'     => $approverName,
                            'is_variable'       => false,
                            'weight_kg'         => 0.0,
                            'batch_code'        => $batchCode,
                            'quality_grade'     => $grade,
                            'grade_desc'        => $gradeDesc,
                            'grade_label'       => $gradeLabel,
                            'qr_code'           => $bultoQr,
                            'sku'               => $sku,
                            'label_type'        => 'bulto',
                        ];

                        // Etiquetas interiores de millar
                        for ($m = 1; $m <= (int)$millarPerBulto; $m++) {
                            $currentIndex++;
                            $labels[] = [
                                'id'                => $prod->id,
                                'index'             => $currentIndex,
                                'total_in_batch'    => $totalLabels,
                                'product_name'      => $prodName,
                                'presentation_info' => "1 MILLAR",
                                'operator_name'     => $operatorName,
                                'production_date'   => $prodDate,
                                'approver_name'     => $approverName,
                                'is_variable'       => false,
                                'weight_kg'         => 0.0,
                                'batch_code'        => $batchCode,
                                'quality_grade'     => $grade,
                                'grade_desc'        => $gradeDesc,
                                'grade_label'       => $gradeLabel,
                                'qr_code'           => "{$bultoQr}-M{$m}",
                                'sku'               => $sku,
                                'label_type'        => 'millar',
                            ];
                        }
                    } else {
                        // Bulto es 1 millar
                        $currentIndex++;
                        $labels[] = [
                            'id'                => $prod->id,
                            'index'             => $currentIndex,
                            'total_in_batch'    => $totalLabels,
                            'product_name'      => $prodName,
                            'presentation_info' => "1 MILLAR",
                            'operator_name'     => $operatorName,
                            'production_date'   => $prodDate,
                            'approver_name'     => $approverName,
                            'is_variable'       => false,
                            'weight_kg'         => 0.0,
                            'batch_code'        => $batchCode,
                            'quality_grade'     => $grade,
                            'grade_desc'        => $gradeDesc,
                            'grade_label'       => $gradeLabel,
                            'qr_code'           => $bultoQr,
                            'sku'               => $sku,
                            'label_type'        => 'millar',
                        ];
                    }
                }
            } else {
                // 3. Caso: Modo Millar (Interiores por Defecto)
                $millarCount = ($millarPerBulto > 1) ? (int)$millarPerBulto : 1;
                $totalMillares = $qty * $millarCount;
                $currentIndex = 0;

                for ($b = 0; $b < $qty; $b++) {
                    $bultoQr = $qty > 1 ? "{$baseQr}-B" . ($b + 1) : $baseQr;
                    for ($m = 1; $m <= $millarCount; $m++) {
                        $currentIndex++;
                        $millarQr = $millarCount > 1 ? "{$bultoQr}-M{$m}" : $bultoQr;
                        $labels[] = [
                            'id'                => $prod->id,
                            'index'             => $currentIndex,
                            'total_in_batch'    => $totalMillares,
                            'product_name'      => $prodName,
                            'presentation_info' => "1 MILLAR",
                            'operator_name'     => $operatorName,
                            'production_date'   => $prodDate,
                            'approver_name'     => $approverName,
                            'is_variable'       => false,
                            'weight_kg'         => 0.0,
                            'batch_code'        => $batchCode,
                            'quality_grade'     => $grade,
                            'grade_desc'        => $gradeDesc,
                            'grade_label'       => $gradeLabel,
                            'qr_code'           => $millarQr,
                            'sku'               => $sku,
                            'label_type'        => 'millar',
                        ];
                    }
                }
            }
        }

        return $labels;
    }

    protected function logLabelsPrint(array $labels): void
    {
        foreach ($labels as $lbl) {
            if (!empty($lbl['qr_code'])) {
                BagLabelPrint::logPrint(
                    $lbl['qr_code'],
                    $lbl['id'] ?? null,
                    Auth::id(),
                    $lbl['label_type'] ?? 'millar',
                    request()->ip(),
                    request()->userAgent()
                );
            }
        }
    }

    public function labelAudits(Request $request)
    {
        $query = BagProduction::where('status', 'approved')
            ->with(['product', 'user', 'shift.user', 'shift.machine', 'reviewer', 'labelPrints.user'])
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
        if ($request->filled('shift_id')) {
            $query->where('bag_shift_id', $request->shift_id);
        }

        $allApproved = (clone $query)->get();

        $totalApprovedPkgs = 0;
        $totalExpectedLabels = 0;
        $totalActualPrints = 0;
        $totalAnomalies = 0;

        foreach ($allApproved as $prod) {
            $qty = (int)$prod->quantity;
            $millarPerBulto = (float)($prod->product?->millar_per_bulto > 0 ? $prod->product->millar_per_bulto : 1.0);
            $isVar = (bool)$prod->product?->is_variable_quantity;

            $expectedMillar = $isVar ? $qty : ($qty * (int)$millarPerBulto);
            $expectedBulto = $isVar ? $qty : $qty;
            $maxExpectedKit = $isVar ? $qty : ($expectedMillar + ($millarPerBulto > 1 ? $expectedBulto : 0));

            $actualPrints = (int)$prod->labelPrints->sum('print_count');

            $prod->expected_millar = $expectedMillar;
            $prod->expected_bulto = $expectedBulto;
            $prod->max_expected_kit = $maxExpectedKit;
            $prod->actual_prints = $actualPrints;
            $prod->has_anomaly = ($actualPrints > $maxExpectedKit) || $prod->labelPrints->contains(fn($lp) => $lp->print_count > 1);
            $prod->excess_count = max(0, $actualPrints - $maxExpectedKit);

            $totalApprovedPkgs += $qty;
            $totalExpectedLabels += $maxExpectedKit;
            $totalActualPrints += $actualPrints;
            if ($prod->has_anomaly) {
                $totalAnomalies++;
            }
        }

        $productions = $query->paginate(25);
        foreach ($productions as $prod) {
            $qty = (int)$prod->quantity;
            $millarPerBulto = (float)($prod->product?->millar_per_bulto > 0 ? $prod->product->millar_per_bulto : 1.0);
            $isVar = (bool)$prod->product?->is_variable_quantity;

            $expectedMillar = $isVar ? $qty : ($qty * (int)$millarPerBulto);
            $expectedBulto = $isVar ? $qty : $qty;
            $maxExpectedKit = $isVar ? $qty : ($expectedMillar + ($millarPerBulto > 1 ? $expectedBulto : 0));

            $actualPrints = (int)$prod->labelPrints->sum('print_count');

            $prod->expected_millar = $expectedMillar;
            $prod->expected_bulto = $expectedBulto;
            $prod->max_expected_kit = $maxExpectedKit;
            $prod->actual_prints = $actualPrints;
            $prod->has_anomaly = ($actualPrints > $maxExpectedKit) || $prod->labelPrints->contains(fn($lp) => $lp->print_count > 1);
            $prod->excess_count = max(0, $actualPrints - $maxExpectedKit);
        }

        $users = User::orderBy('name')->get();
        $products = BagProduct::where('is_active', true)->orderBy('name')->get();

        $kpis = [
            'total_approved_pkgs'   => $totalApprovedPkgs,
            'total_expected_labels' => $totalExpectedLabels,
            'total_actual_prints'   => $totalActualPrints,
            'total_anomalies'       => $totalAnomalies,
        ];

        return view('bag_factory.label_audits', compact('productions', 'users', 'products', 'kpis'));
    }

    protected function renderPdfResponse(array $labels, string $size, string $filename)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        if ($size === 'sheet') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.labels_sheet_qr', compact('labels'));
            $pdf->setPaper('letter', 'portrait');
        } elseif ($size === '58mm') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('bag_factory.ticket_pdf', [
                'labels' => $labels,
                'size'   => '58mm',
            ]);
            // Ancho 58mm (aprox 164pt), alto variable 50mm por ticket (aprox 141pt)
            $pdf->setPaper([0, 0, 164.4, 141.7 * max(1, count($labels))], 'portrait');
        } else {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('bag_factory.ticket_pdf', [
                'labels' => $labels,
                'size'   => '80mm',
            ]);
            // Ancho 80mm (aprox 226.7pt), alto 60mm por ticket (aprox 170pt)
            $pdf->setPaper([0, 0, 226.7, 170.0 * max(1, count($labels))], 'portrait');
        }

        return $pdf->stream($filename);
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

    // ==================== NÓMINA Y RENDIMIENTO POR METAS ====================
    public function payrollIndex(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfWeek(Carbon::MONDAY)->startOfDay();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $hasRoleCol = \Illuminate\Support\Facades\Schema::hasColumn('users', 'role');
        $roleField = $hasRoleCol ? 'role' : 'profile';

        $operators = User::where(function ($q) use ($roleField) {
            $q->where($roleField, 'like', '%operario%')
              ->orWhere($roleField, 'like', '%operator%');
        })->get();

        if ($operators->isEmpty()) {
            $operators = User::all();
        }

        $payrollData = $operators->map(function ($op) use ($startDate, $endDate) {
            $weekly = $op->getWeeklyEarnings($startDate, $endDate);
            return [
                'operator' => $op,
                'weekly'   => $weekly,
            ];
        });

        $incompleteFractions = BagProduction::where('is_package_completed', false)
            ->where('labor_retained_amount', '>', 0)
            ->whereBetween('recorded_at', [$startDate, $endDate])
            ->with(['user', 'product', 'machine', 'shift.machine'])
            ->orderBy('recorded_at', 'desc')
            ->get();

        return view('bag_factory.payroll', compact('startDate', 'endDate', 'payrollData', 'incompleteFractions'));
    }

    // ==================== ESTACIÓN WEB DE OPERARIOS (CARGA RÁPIDA & PESAJE POR LOTES) ====================
    public function operatorStation(Request $request)
    {
        $user = Auth::user();

        // Si es admin/supervisor y desea ver la estación de otro operario:
        $targetUserId = ($user->isOperator() || !$request->filled('operator_id'))
            ? $user->id
            : $request->operator_id;
        $targetUser = User::find($targetUserId) ?: $user;

        // Buscar turno activo del operario objetivo
        $activeShift = BagShift::where('user_id', $targetUser->id)
            ->where('status', 'open')
            ->with(['machine', 'productions.product'])
            ->latest()
            ->first();

        // Catálogo de máquinas activas
        $machines = BagMachine::where('is_active', true)->orderBy('name')->get();

        // Catálogo de productos activos
        $products = BagProduct::where('is_active', true)
            ->orderBy('name')
            ->get([
                'id', 'name', 'sku', 'sale_unit', 'unit_weight_kg', 'real_total_weight_kg',
                'millar_per_bulto', 'target_units_per_shift', 'is_variable_quantity'
            ]);

        // Producciones del turno actual
        $shiftProductions = collect();
        $totalEarnedUsd = 0.00;
        $combinedProgressPercent = 0.00;
        $totalUnits = 0.00;
        $totalWeightKg = 0.00;

        if ($activeShift) {
            $shiftProductions = BagProduction::where('bag_shift_id', $activeShift->id)
                ->with(['product', 'machine'])
                ->latest('recorded_at')
                ->get();

            // 1. Ganancia acumulada en USD
            $totalEarnedUsd = round((float)$shiftProductions->sum('labor_earned_amount'), 2);

            // 2. Kilos y unidades totales
            $totalUnits = round((float)$shiftProductions->sum('quantity'), 2);
            $totalWeightKg = round((float)$shiftProductions->sum('weight'), 2);

            // 3. Cumplimiento de meta combinada multimedida
            $prodGroups = $shiftProductions->groupBy('product_id');
            $accumulatedFraction = 0.0;

            foreach ($prodGroups as $prodId => $items) {
                $product = $items->first()->product;
                if (!$product) continue;

                $target = (float)($product->target_units_per_shift ?: 5);
                $qtyProduced = (float)$items->sum('quantity');

                if ($target > 0) {
                    $accumulatedFraction += ($qtyProduced / $target);
                }
            }

            $combinedProgressPercent = round($accumulatedFraction * 100.0, 1);
        }

        // Operarios para selector de supervisión (si es admin o supervisor)
        $operatorsList = collect();
        if (!$user->isOperator()) {
            $hasRoleCol = \Illuminate\Support\Facades\Schema::hasColumn('users', 'role');
            $roleField = $hasRoleCol ? 'role' : 'profile';
            $operatorsList = User::where(function ($q) use ($roleField) {
                $q->where($roleField, 'like', '%operario%')
                  ->orWhere($roleField, 'like', '%operator%');
            })->orderBy('name')->get();
        }

        // Catálogo serializado para el buscador inteligente reactivo
        $targetDailySalary = (float)($targetUser->daily_salary ?: 15.0);
        $productsCatalog = $products->map(function ($p) use ($targetDailySalary) {
            $target = (int)($p->target_units_per_shift ?: 5);
            $tariff = $target > 0 ? ($targetDailySalary / $target) : 0.0;
            return [
                'id'          => $p->id,
                'name'        => $p->name,
                'sku'         => $p->sku ?? '',
                'sale_unit'   => $p->sale_unit ?? '',
                'theoretical' => (float)($p->unit_weight_kg > 0 ? $p->unit_weight_kg : 1.0),
                'target'      => $target,
                'millar'      => (float)($p->millar_per_bulto ?: 1),
                'is_variable' => (bool)$p->is_variable_quantity,
                'tariff'      => round($tariff, 2),
            ];
        })->values();
        $productsCatalogJson = json_encode($productsCatalog);

        return view('bag_factory.operator_station', compact(
            'user',
            'targetUser',
            'activeShift',
            'machines',
            'products',
            'productsCatalogJson',
            'shiftProductions',
            'totalEarnedUsd',
            'combinedProgressPercent',
            'totalUnits',
            'totalWeightKg',
            'operatorsList'
        ));
    }

    public function operatorStoreBatch(Request $request)
    {
        $request->validate([
            'product_id'  => 'required|exists:bag_products,id',
            'quantity'    => 'required|numeric|min:0.01',
            'weight'      => 'required|numeric|min:0.01',
            'machine_id'  => 'nullable|exists:bag_machines,id',
            'print_mode'  => 'nullable|string|in:save_only,direct_print',
            'operator_id' => 'nullable|exists:users,id',
        ]);

        $user = Auth::user();
        $targetUserId = (!$user->isOperator() && $request->filled('operator_id'))
            ? (int)$request->operator_id
            : $user->id;
        $targetUser = User::find($targetUserId) ?: $user;

        // Obtener o abrir turno activo del usuario objetivo
        $shift = BagShift::where('user_id', $targetUser->id)
            ->where('status', 'open')
            ->first();

        if (!$shift) {
            $machineId = $request->machine_id ?: BagMachine::where('is_active', true)->value('id');
            $shift = BagShift::create([
                'user_id'    => $targetUser->id,
                'machine_id' => $machineId,
                'shift_type' => 'diurno',
                'start_time' => now(),
                'status'     => 'open',
                'sync_id'    => 'SHIFT-WEB-' . Str::uuid(),
            ]);
        }

        $product = BagProduct::findOrFail($request->product_id);
        $qty = (float)$request->quantity;
        $weight = (float)$request->weight;
        $machineId = $request->machine_id ?: $shift->machine_id;

        // Auto-sanitización si ingresó en gramos (> 500g y producto estándar)
        if ($weight >= 500 && !$product->is_variable_quantity) {
            $weight = round($weight / 1000, 4);
        }

        $breakdown = $product->calculateBreakdown($qty);
        $completedCount = (float)$breakdown['completed_packages'];
        $fractionalUnits = (float)$breakdown['fractional_units'];
        $isCompleted = (bool)$breakdown['is_package_completed'];

        $grading = $product->calculateWeightQualityGrade($weight, $completedCount, $fractionalUnits);
        $grade = $grading['grade'];
        $devPercent = $grading['deviation_percent'];

        // Tarifa laboral y ganancia en USD usando targetUser
        $tariffs = $targetUser->calculateLaborTariff($product);
        $packageTariff = (float)$tariffs['package_tariff'];
        $fractionTariff = (float)$tariffs['fraction_tariff'];

        if ($targetUser->pay_partial_packages) {
            $laborEarned = round(($completedCount * $packageTariff) + ($fractionalUnits * $fractionTariff), 2);
            $laborRetained = 0.00;
        } else {
            $earnedForCompleted = round($completedCount * $packageTariff, 2);
            $retainedForFraction = round($fractionalUnits * $fractionTariff, 2);
            $laborEarned = round($earnedForCompleted + $retainedForFraction, 2);
            $laborRetained = $retainedForFraction;
        }

        $snapshot = [
            'product_name'            => $product->name,
            'sku'                     => $product->sku,
            'unit_weight_kg'          => (float)($product->unit_weight_kg ?? 0),
            'millar_per_bulto'        => (float)($product->millar_per_bulto ?? 1),
            'target_units_per_shift'  => (int)($product->target_units_per_shift ?? 5),
            'cost_per_kg_snapshot'    => (float)$product->getEffectivePricePerKg(),
            'factory_price_snapshot'  => (float)($product->price ?? 0),
            'applied_daily_salary'    => (float)$targetUser->daily_salary,
            'applied_package_tariff'  => $packageTariff,
            'applied_fraction_tariff' => $fractionTariff,
            'batch_entry_mode'        => 'multi_pack_scale',
            'average_weight_per_unit' => $qty > 0 ? round($weight / $qty, 4) : $weight,
        ];

        $isDirectPrint = ($request->get('print_mode') === 'direct_print');

        $production = BagProduction::create([
            'bag_shift_id'             => $shift->id,
            'user_id'                  => $targetUser->id,
            'product_id'               => $product->id,
            'machine_id'               => $machineId,
            'quantity'                 => $qty,
            'weight'                   => $weight,
            'original_weight'          => $weight,
            'recorded_at'              => now(),
            'status'                   => 'pending_review',
            'is_printed'               => $isDirectPrint,
            'printed_at'               => $isDirectPrint ? now() : null,
            'weight_quality_grade'     => $grade,
            'weight_deviation_percent' => $devPercent,
            'completed_packages_count' => $completedCount,
            'fractional_units'         => $fractionalUnits,
            'is_package_completed'     => $isCompleted,
            'labor_earned_amount'      => $laborEarned,
            'labor_retained_amount'    => $laborRetained,
            'qr_code'                  => 'PKG-' . strtoupper(Str::random(10)),
            'sync_id'                  => 'PROD-WEB-' . Str::uuid(),
            'metadata'                 => ['snapshot' => $snapshot],
        ]);

        if ($isDirectPrint) {
            return redirect()->route('ticket', ['id' => $production->id, 'scope' => 'bulto', 'auto_print' => 1]);
        }

        $redirectParams = (!$user->isOperator() && $request->filled('operator_id')) ? ['operator_id' => $targetUser->id] : [];
        return redirect()->route('operator.station', $redirectParams)->with('success', "Pesaje de {$qty} millares registrado correctamente ({$production->weight_grade_label})");
    }

    public function operatorUpdateBatch(Request $request, $id)
    {
        $prod = BagProduction::with(['shift', 'product'])->findOrFail($id);
        $user = Auth::user();

        // Candado de Seguridad Industrial:
        // Si no es admin y ya fue impreso, no puede modificarlo
        if ($user->role !== 'admin' && $prod->is_printed) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este pesaje ya fue impreso y sellado con etiqueta física. Comuníquese con el Jefe de Operaciones para solicitar una corrección.',
                ], 403);
            }
            return redirect()->back()->with('error', 'Este pesaje ya fue impreso y sellado con etiqueta física. Comuníquese con el Jefe de Operaciones para solicitar una corrección.');
        }

        // Si no es admin y no es el dueño del registro
        if ($user->role !== 'admin' && $prod->user_id !== $user->id) {
            abort(403, 'No tiene permiso para modificar este pesaje.');
        }

        $request->validate([
            'product_id' => 'nullable|exists:bag_products,id',
            'quantity'   => 'required|numeric|min:0.01',
            'weight'     => 'required|numeric|min:0.01',
        ]);

        if ($request->filled('product_id')) {
            $prod->product_id = (int)$request->product_id;
        }

        $qty = (float)$request->quantity;
        $weight = (float)$request->weight;
        $product = $prod->product;

        if ($weight >= 500 && $product && !$product->is_variable_quantity) {
            $weight = round($weight / 1000, 4);
        }

        $prod->quantity = $qty;
        $prod->weight = $weight;
        $prod->save();

        // Recalcular métricas laborales y de calidad
        $prod->recalculateLaborAndQuality();

        // Recalcular finanzas del turno
        $prod->shift?->recalculateFinancials();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Pesaje actualizado correctamente ({$prod->weight_grade_label})",
                'production' => $prod->fresh(),
            ]);
        }

        $redirectParams = (!$user->isOperator() && $request->filled('operator_id')) ? ['operator_id' => $prod->user_id] : [];
        return redirect()->route('operator.station', $redirectParams)->with('success', "Pesaje actualizado correctamente ({$prod->weight_grade_label})");
    }

    public function operatorDestroyBatch(Request $request, $id)
    {
        $prod = BagProduction::with('shift')->findOrFail($id);
        $user = Auth::user();

        // Candado de Seguridad Industrial:
        if ($user->role !== 'admin' && $prod->is_printed) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este pesaje ya fue impreso y sellado con etiqueta física. Comuníquese con el Jefe de Operaciones para anularlo.',
                ], 403);
            }
            return redirect()->back()->with('error', 'Este pesaje ya fue impreso y sellado con etiqueta física. Comuníquese con el Jefe de Operaciones para anularlo.');
        }

        // Si no es admin y no es el dueño del registro
        if ($user->role !== 'admin' && $prod->user_id !== $user->id) {
            abort(403, 'No tiene permiso para eliminar este pesaje.');
        }

        $shift = $prod->shift;
        $prodOwnerId = $prod->user_id;
        $qty = $prod->quantity;
        $prod->delete();

        $shift?->recalculateFinancials();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Pesaje pre-cargado de {$qty} millares eliminado correctamente.",
            ]);
        }

        $redirectParams = (!$user->isOperator() && $request->filled('operator_id')) ? ['operator_id' => $prodOwnerId] : [];
        return redirect()->route('operator.station', $redirectParams)->with('success', "Pesaje pre-cargado de {$qty} millares eliminado correctamente.");
    }

    public function operatorOpenShift(Request $request)
    {
        $request->validate([
            'machine_id'  => 'required|exists:bag_machines,id',
            'shift_type'  => 'nullable|in:diurno,nocturno',
            'operator_id' => 'nullable|exists:users,id',
        ]);

        $user = Auth::user();
        $targetUserId = (!$user->isOperator() && $request->filled('operator_id'))
            ? (int)$request->operator_id
            : $user->id;

        $existing = BagShift::where('user_id', $targetUserId)->where('status', 'open')->first();
        if ($existing) {
            $existing->update(['machine_id' => $request->machine_id]);
            $redirectParams = (!$user->isOperator() && $request->filled('operator_id')) ? ['operator_id' => $targetUserId] : [];
            return redirect()->route('operator.station', $redirectParams)->with('info', 'Turno activo continuado en máquina seleccionada.');
        }

        BagShift::create([
            'user_id'    => $targetUserId,
            'machine_id' => $request->machine_id,
            'shift_type' => $request->shift_type ?: 'diurno',
            'start_time' => now(),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-WEB-' . Str::uuid(),
        ]);

        $redirectParams = (!$user->isOperator() && $request->filled('operator_id')) ? ['operator_id' => $targetUserId] : [];
        return redirect()->route('operator.station', $redirectParams)->with('success', 'Turno iniciado correctamente.');
    }

    public function operatorCloseShift(Request $request)
    {
        $request->validate([
            'shift_id'    => 'required|exists:bag_shifts,id',
            'operator_id' => 'nullable|exists:users,id',
        ]);

        $user = Auth::user();
        $query = BagShift::where('id', $request->shift_id);

        // Si es operario, solo puede cerrar su propio turno. Si es admin, puede cerrar el turno que supervisa.
        if ($user->isOperator()) {
            $query->where('user_id', $user->id);
        }

        $shift = $query->firstOrFail();

        $shift->update([
            'status'   => 'closed',
            'end_time' => now(),
        ]);

        $redirectParams = (!$user->isOperator() && $request->filled('operator_id'))
            ? ['operator_id' => $shift->user_id]
            : [];

        return redirect()->route('operator.station', $redirectParams)->with('success', 'Turno cerrado con éxito.');
    }
}
