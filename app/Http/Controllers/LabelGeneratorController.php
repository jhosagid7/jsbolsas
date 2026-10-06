<?php

namespace App\Http\Controllers;

use App\Models\BagLabelPrint;
use App\Models\BagProduct;
use App\Models\BagProduction;
use App\Models\BagShift;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LabelGeneratorController extends Controller
{
    public function index(Request $request)
    {
        $products = BagProduct::where('is_active', true)->orderBy('name')->get();
        
        $recentApproved = BagProduction::where('status', 'approved')
            ->with(['product', 'user', 'shift.user', 'shift.machine', 'reviewer'])
            ->orderBy('reviewed_at', 'desc')
            ->take(50)
            ->get();

        $shifts = BagShift::with('user', 'machine')
            ->orderBy('created_at', 'desc')
            ->take(15)
            ->get();

        $operators = User::orderBy('name')->get();

        return view('labels.index', compact('products', 'recentApproved', 'shifts', 'operators'));
    }

    public function generate(Request $request)
    {
        $items = $request->input('items', []); // array of [type => 'catalog'|'production', id => 1, qty => 2]
        $template = $request->input('template', '80mm'); // 80mm, 58mm, sheet
        $output = $request->input('output', 'preview'); // preview, pdf
        $scope = $request->input('label_scope', 'millar'); // millar, bulto, kit

        if (empty($items)) {
            return back()->with('error', 'Debe seleccionar al menos un producto o producción para generar etiquetas.');
        }

        $defaultOperatorId = $request->input('operator_id');
        $defaultOperatorName = null;
        if ($defaultOperatorId) {
            $opUser = User::find($defaultOperatorId);
            if ($opUser) {
                $defaultOperatorName = $opUser->name;
            }
        }
        if (!$defaultOperatorName && $request->filled('operator_name')) {
            $defaultOperatorName = trim($request->input('operator_name'));
        }

        $productionDate = $request->filled('production_date') 
            ? date('d/m/Y', strtotime($request->input('production_date'))) 
            : date('d/m/Y');

        $batchCode = $request->filled('batch_code') 
            ? trim($request->input('batch_code')) 
            : 'LOTE-CATALOGO';

        $catalogOperatorName = $defaultOperatorName ?: (Auth::user()->name ?? 'Planta M&F');

        $labels = [];

        foreach ($items as $item) {
            $type = $item['type'] ?? 'catalog';
            $id = $item['id'] ?? null;
            $qty = max(1, (int)($item['qty'] ?? 1));

            if ($type === 'production') {
                $prod = BagProduction::with(['product', 'user', 'shift.user', 'reviewer'])->find($id);
                if ($prod) {
                    $controller = new BagFactoryWebController();
                    $unitLabels = $controller->formatLabelsList($prod, $scope);
                    for ($k = 0; $k < $qty; $k++) {
                        foreach ($unitLabels as $lbl) {
                            $labels[] = $lbl;
                        }
                    }
                }
            } else {
                $product = BagProduct::find($id);
                if ($product) {
                    $isVariable = (bool)$product->is_variable_quantity;
                    $saleUnit = strtoupper($product->sale_unit ?? 'BULTO');
                    $millarPerBulto = (float)($product->millar_per_bulto > 0 ? $product->millar_per_bulto : 1.0);
                    $prodName = mb_strtoupper($product->name, 'UTF-8');
                    $sku = $product->sku ?? 'S/SKU';

                    if ($isVariable) {
                        $isComposite = (bool)$product->is_composite_rolls;
                        $suggestedRolls = max(1, (int)($product->suggested_rolls_per_package ?: 9));
                        $unitWeight = (float)($product->real_total_weight_kg ?: $product->unit_weight_kg ?: 1.0);
                        $totalWeight = $unitWeight * $suggestedRolls;

                        if ($isComposite && $scope === 'bulto') {
                            for ($k = 0; $k < $qty; $k++) {
                                $bultoQr = $product->sku ? 'PKG-' . $product->sku . '-B' . ($k + 1) . '-' . strtoupper(Str::random(4)) : ('PKG-B-' . strtoupper(Str::random(8)));
                                $labels[] = [
                                    'id'                => $product->id,
                                    'index'             => $k + 1,
                                    'total_in_batch'    => $qty,
                                    'product_name'      => $prodName,
                                    'presentation_info' => "1 BULTO ({$suggestedRolls} BOBINAS)",
                                    'operator_name'     => $catalogOperatorName,
                                    'production_date'   => $productionDate,
                                    'approver_name'     => Auth::user()->name ?? 'Supervisor',
                                    'is_variable'       => true,
                                    'weight_kg'         => $totalWeight,
                                    'batch_code'        => $batchCode,
                                    'qr_code'           => $bultoQr,
                                    'sku'               => $sku,
                                    'label_type'        => 'bulto',
                                ];
                            }
                        } elseif ($isComposite && $scope === 'kit') {
                            $currentIndex = 0;
                            $totalLabels = $qty * ($suggestedRolls + 1);
                            for ($k = 0; $k < $qty; $k++) {
                                $bultoQr = $product->sku ? 'PKG-' . $product->sku . '-B' . ($k + 1) . '-' . strtoupper(Str::random(4)) : ('PKG-B-' . strtoupper(Str::random(8)));
                                $currentIndex++;
                                $labels[] = [
                                    'id'                => $product->id,
                                    'index'             => $currentIndex,
                                    'total_in_batch'    => $totalLabels,
                                    'product_name'      => $prodName,
                                    'presentation_info' => "1 BULTO ({$suggestedRolls} BOBINAS)",
                                    'operator_name'     => $catalogOperatorName,
                                    'production_date'   => $productionDate,
                                    'approver_name'     => Auth::user()->name ?? 'Supervisor',
                                    'is_variable'       => true,
                                    'weight_kg'         => $totalWeight,
                                    'batch_code'        => $batchCode,
                                    'qr_code'           => $bultoQr,
                                    'sku'               => $sku,
                                    'label_type'        => 'bulto',
                                ];
                                for ($r = 1; $r <= $suggestedRolls; $r++) {
                                    $currentIndex++;
                                    $labels[] = [
                                        'id'                => $product->id,
                                        'index'             => $currentIndex,
                                        'total_in_batch'    => $totalLabels,
                                        'product_name'      => "{$prodName} - BOBINA {$r}/{$suggestedRolls}",
                                        'presentation_info' => 'BOBINA - PESO VARIABLE',
                                        'operator_name'     => $catalogOperatorName,
                                        'production_date'   => $productionDate,
                                        'approver_name'     => Auth::user()->name ?? 'Supervisor',
                                        'is_variable'       => true,
                                        'weight_kg'         => round($totalWeight / $suggestedRolls, 2),
                                        'batch_code'        => $batchCode,
                                        'qr_code'           => "{$bultoQr}-R{$r}",
                                        'sku'               => $sku,
                                        'label_type'        => 'bobina',
                                    ];
                                }
                            }
                        } elseif ($isComposite) {
                            $currentIndex = 0;
                            $totalLabels = $qty * $suggestedRolls;
                            for ($k = 0; $k < $qty; $k++) {
                                $bultoQr = $product->sku ? 'PKG-' . $product->sku . '-B' . ($k + 1) . '-' . strtoupper(Str::random(4)) : ('PKG-B-' . strtoupper(Str::random(8)));
                                for ($r = 1; $r <= $suggestedRolls; $r++) {
                                    $currentIndex++;
                                    $labels[] = [
                                        'id'                => $product->id,
                                        'index'             => $currentIndex,
                                        'total_in_batch'    => $totalLabels,
                                        'product_name'      => "{$prodName} - BOBINA {$r}/{$suggestedRolls}",
                                        'presentation_info' => 'BOBINA - PESO VARIABLE',
                                        'operator_name'     => $catalogOperatorName,
                                        'production_date'   => $productionDate,
                                        'approver_name'     => Auth::user()->name ?? 'Supervisor',
                                        'is_variable'       => true,
                                        'weight_kg'         => round($totalWeight / $suggestedRolls, 2),
                                        'batch_code'        => $batchCode,
                                        'qr_code'           => "{$bultoQr}-R{$r}",
                                        'sku'               => $sku,
                                        'label_type'        => 'bobina',
                                    ];
                                }
                            }
                        } else {
                            for ($k = 0; $k < $qty; $k++) {
                                $labels[] = [
                                    'id'                => $product->id,
                                    'index'             => $k + 1,
                                    'total_in_batch'    => $qty,
                                    'product_name'      => $prodName,
                                    'presentation_info' => 'BOBINA - PESO VARIABLE',
                                    'operator_name'     => $catalogOperatorName,
                                    'production_date'   => $productionDate,
                                    'approver_name'     => Auth::user()->name ?? 'Supervisor',
                                    'is_variable'       => true,
                                    'weight_kg'         => $unitWeight,
                                    'batch_code'        => $batchCode,
                                    'qr_code'           => $product->sku ? 'PKG-' . $product->sku . '-' . strtoupper(Str::random(5)) : ('PKG-' . strtoupper(Str::random(10))),
                                    'sku'               => $sku,
                                    'label_type'        => 'bobina',
                                ];
                            }
                        }
                    } elseif ($scope === 'bulto') {
                        for ($b = 0; $b < $qty; $b++) {
                            $pres = $millarPerBulto > 1 ? "1 BULTO (" . (int)$millarPerBulto . " MILLARES)" : "1 {$saleUnit}";
                            $labels[] = [
                                'id'                => $product->id,
                                'index'             => $b + 1,
                                'total_in_batch'    => $qty,
                                'product_name'      => $prodName,
                                'presentation_info' => $pres,
                                'operator_name'     => $catalogOperatorName,
                                'production_date'   => $productionDate,
                                'approver_name'     => Auth::user()->name ?? 'Supervisor',
                                'is_variable'       => false,
                                'weight_kg'         => 0.0,
                                'batch_code'        => $batchCode,
                                'qr_code'           => $product->sku ? 'PKG-' . $product->sku . '-B' . ($b + 1) . '-' . strtoupper(Str::random(4)) : ('PKG-B-' . strtoupper(Str::random(8))),
                                'sku'               => $sku,
                                'label_type'        => 'bulto',
                            ];
                        }
                    } elseif ($scope === 'kit') {
                        $currentIndex = 0;
                        $totalLabels = $qty * ($millarPerBulto > 1 ? ((int)$millarPerBulto + 1) : 1);

                        for ($b = 0; $b < $qty; $b++) {
                            $bultoQr = $product->sku ? 'PKG-' . $product->sku . '-B' . ($b + 1) . '-' . strtoupper(Str::random(4)) : ('PKG-B-' . strtoupper(Str::random(8)));
                            if ($millarPerBulto > 1) {
                                $currentIndex++;
                                $labels[] = [
                                    'id'                => $product->id,
                                    'index'             => $currentIndex,
                                    'total_in_batch'    => $totalLabels,
                                    'product_name'      => $prodName,
                                    'presentation_info' => "1 BULTO (" . (int)$millarPerBulto . " MILLARES)",
                                    'operator_name'     => $catalogOperatorName,
                                    'production_date'   => $productionDate,
                                    'approver_name'     => Auth::user()->name ?? 'Supervisor',
                                    'is_variable'       => false,
                                    'weight_kg'         => 0.0,
                                    'batch_code'        => $batchCode,
                                    'qr_code'           => $bultoQr,
                                    'sku'               => $sku,
                                    'label_type'        => 'bulto',
                                ];

                                for ($m = 1; $m <= (int)$millarPerBulto; $m++) {
                                    $currentIndex++;
                                    $labels[] = [
                                        'id'                => $product->id,
                                        'index'             => $currentIndex,
                                        'total_in_batch'    => $totalLabels,
                                        'product_name'      => $prodName,
                                        'presentation_info' => "1 MILLAR",
                                        'operator_name'     => $catalogOperatorName,
                                        'production_date'   => $productionDate,
                                        'approver_name'     => Auth::user()->name ?? 'Supervisor',
                                        'is_variable'       => false,
                                        'weight_kg'         => 0.0,
                                        'batch_code'        => $batchCode,
                                        'qr_code'           => "{$bultoQr}-M{$m}",
                                        'sku'               => $sku,
                                        'label_type'        => 'millar',
                                    ];
                                }
                            } else {
                                $currentIndex++;
                                $labels[] = [
                                    'id'                => $product->id,
                                    'index'             => $currentIndex,
                                    'total_in_batch'    => $totalLabels,
                                    'product_name'      => $prodName,
                                    'presentation_info' => "1 MILLAR",
                                    'operator_name'     => $catalogOperatorName,
                                    'production_date'   => $productionDate,
                                    'approver_name'     => Auth::user()->name ?? 'Supervisor',
                                    'is_variable'       => false,
                                    'weight_kg'         => 0.0,
                                    'batch_code'        => $batchCode,
                                    'qr_code'           => $bultoQr,
                                    'sku'               => $sku,
                                    'label_type'        => 'millar',
                                ];
                            }
                        }
                    } else {
                        // Modo Millar por Defecto
                        $millarCount = ($millarPerBulto > 1) ? (int)$millarPerBulto : 1;
                        $totalMillares = $qty * $millarCount;
                        $currentIndex = 0;

                        for ($b = 0; $b < $qty; $b++) {
                            for ($m = 1; $m <= $millarCount; $m++) {
                                $currentIndex++;
                                $qr = $product->sku ? 'PKG-' . $product->sku . '-M' . $currentIndex . '-' . strtoupper(Str::random(4)) : ('PKG-M-' . strtoupper(Str::random(8)));
                                $labels[] = [
                                    'id'                => $product->id,
                                    'index'             => $currentIndex,
                                    'total_in_batch'    => $totalMillares,
                                    'product_name'      => $prodName,
                                    'presentation_info' => "1 MILLAR",
                                    'operator_name'     => $catalogOperatorName,
                                    'production_date'   => $productionDate,
                                    'approver_name'     => Auth::user()->name ?? 'Supervisor',
                                    'is_variable'       => false,
                                    'weight_kg'         => 0.0,
                                    'batch_code'        => $batchCode,
                                    'qr_code'           => $qr,
                                    'sku'               => $sku,
                                    'label_type'        => 'millar',
                                ];
                            }
                        }
                    }
                }
            }
        }

        if (empty($labels)) {
            return back()->with('error', 'No se pudieron procesar las etiquetas de los elementos seleccionados.');
        }

        if ($output === 'pdf') {
            // Registrar auditoría de impresiones físicas
            foreach ($labels as $lbl) {
                if (!empty($lbl['qr_code'])) {
                    BagLabelPrint::logPrint(
                        $lbl['qr_code'],
                        $lbl['production_id'] ?? ($lbl['id'] ?? null),
                        Auth::id(),
                        $lbl['label_type'] ?? 'millar',
                        request()->ip(),
                        request()->userAgent()
                    );
                }
            }
            return $this->renderPdf($labels, $template);
        }

        // Vista de Previsualización Térmica Directa (sin registrar impresión aún)
        $pdfUrl = route('labels.pdf.direct', [
            'data' => base64_encode(json_encode([
                'items'           => $items,
                'template'        => $template,
                'label_scope'     => $scope,
                'operator_id'     => $defaultOperatorId,
                'operator_name'   => $defaultOperatorName,
                'production_date' => $request->input('production_date'),
                'batch_code'      => $batchCode,
            ])),
        ]);

        return view('bag_factory.ticket', compact('labels', 'pdfUrl', 'scope'));
    }

    public function confirmPrint(Request $request)
    {
        $labels = $request->input('labels', []);
        $logged = 0;

        foreach ($labels as $lbl) {
            if (!empty($lbl['qr_code'])) {
                BagLabelPrint::logPrint(
                    $lbl['qr_code'],
                    $lbl['production_id'] ?? ($lbl['id'] ?? null),
                    Auth::id(),
                    $lbl['label_type'] ?? 'millar',
                    request()->ip(),
                    request()->userAgent()
                );
                $logged++;
            }
        }

        return response()->json([
            'success' => true,
            'logged'  => $logged,
        ]);
    }

    public function directPdf(Request $request)
    {
        $payload = $request->query('data');
        $size = $request->query('size', '80mm');
        
        if (!$payload) {
            return redirect()->route('labels.index');
        }

        $decoded = json_decode(base64_decode($payload), true);
        if (!$decoded || !isset($decoded['items'])) {
            return redirect()->route('labels.index');
        }

        $items = $decoded['items'];
        $template = $size ?: ($decoded['template'] ?? '80mm');
        $scope = $decoded['label_scope'] ?? 'millar';

        $operatorId = $decoded['operator_id'] ?? null;
        $operatorName = $decoded['operator_name'] ?? null;
        if ($operatorId && !$operatorName) {
            $opUser = User::find($operatorId);
            if ($opUser) {
                $operatorName = $opUser->name;
            }
        }
        $productionDate = !empty($decoded['production_date']) 
            ? date('d/m/Y', strtotime($decoded['production_date'])) 
            : date('d/m/Y');
        $batchCode = !empty($decoded['batch_code']) ? $decoded['batch_code'] : 'LOTE-CATALOGO';
        $catalogOperatorName = $operatorName ?: (Auth::user()->name ?? 'Planta M&F');

        $labels = [];
        foreach ($items as $item) {
            $type = $item['type'] ?? 'catalog';
            $id = $item['id'] ?? null;
            $qty = max(1, (int)($item['qty'] ?? 1));

            if ($type === 'production') {
                $prod = BagProduction::with(['product', 'user', 'shift.user', 'reviewer'])->find($id);
                if ($prod) {
                    $controller = new BagFactoryWebController();
                    $unitLabels = $controller->formatLabelsList($prod, $scope);
                    for ($k = 0; $k < $qty; $k++) {
                        foreach ($unitLabels as $lbl) {
                            $labels[] = $lbl;
                        }
                    }
                }
            } else {
                $product = BagProduct::find($id);
                if ($product) {
                    $isVariable = (bool)$product->is_variable_quantity;
                    $saleUnit = strtoupper($product->sale_unit ?? 'BULTO');
                    $millarPerBulto = (float)($product->millar_per_bulto > 0 ? $product->millar_per_bulto : 1.0);
                    $prodName = mb_strtoupper($product->name, 'UTF-8');
                    $sku = $product->sku ?? 'S/SKU';

                    if ($isVariable) {
                        $isComposite = (bool)$product->is_composite_rolls;
                        $suggestedRolls = max(1, (int)($product->suggested_rolls_per_package ?: 9));
                        $unitWeight = (float)($product->real_total_weight_kg ?: $product->unit_weight_kg ?: 1.0);
                        $totalWeight = $unitWeight * $suggestedRolls;

                        if ($isComposite && $scope === 'bulto') {
                            for ($k = 0; $k < $qty; $k++) {
                                $bultoQr = $product->sku ? 'PKG-' . $product->sku . '-B' . ($k + 1) . '-' . strtoupper(Str::random(4)) : ('PKG-B-' . strtoupper(Str::random(8)));
                                $labels[] = [
                                    'id'                => $product->id,
                                    'index'             => $k + 1,
                                    'total_in_batch'    => $qty,
                                    'product_name'      => $prodName,
                                    'presentation_info' => "1 BULTO ({$suggestedRolls} BOBINAS)",
                                    'operator_name'     => $catalogOperatorName,
                                    'production_date'   => $productionDate,
                                    'approver_name'     => Auth::user()->name ?? 'Supervisor',
                                    'is_variable'       => true,
                                    'weight_kg'         => $totalWeight,
                                    'batch_code'        => $batchCode,
                                    'qr_code'           => $bultoQr,
                                    'sku'               => $sku,
                                    'label_type'        => 'bulto',
                                ];
                            }
                        } elseif ($isComposite && $scope === 'kit') {
                            $currentIndex = 0;
                            $totalLabels = $qty * ($suggestedRolls + 1);
                            for ($k = 0; $k < $qty; $k++) {
                                $bultoQr = $product->sku ? 'PKG-' . $product->sku . '-B' . ($k + 1) . '-' . strtoupper(Str::random(4)) : ('PKG-B-' . strtoupper(Str::random(8)));
                                $currentIndex++;
                                $labels[] = [
                                    'id'                => $product->id,
                                    'index'             => $currentIndex,
                                    'total_in_batch'    => $totalLabels,
                                    'product_name'      => $prodName,
                                    'presentation_info' => "1 BULTO ({$suggestedRolls} BOBINAS)",
                                    'operator_name'     => $catalogOperatorName,
                                    'production_date'   => $productionDate,
                                    'approver_name'     => Auth::user()->name ?? 'Supervisor',
                                    'is_variable'       => true,
                                    'weight_kg'         => $totalWeight,
                                    'batch_code'        => $batchCode,
                                    'qr_code'           => $bultoQr,
                                    'sku'               => $sku,
                                    'label_type'        => 'bulto',
                                ];
                                for ($r = 1; $r <= $suggestedRolls; $r++) {
                                    $currentIndex++;
                                    $labels[] = [
                                        'id'                => $product->id,
                                        'index'             => $currentIndex,
                                        'total_in_batch'    => $totalLabels,
                                        'product_name'      => "{$prodName} - BOBINA {$r}/{$suggestedRolls}",
                                        'presentation_info' => 'BOBINA - PESO VARIABLE',
                                        'operator_name'     => $catalogOperatorName,
                                        'production_date'   => $productionDate,
                                        'approver_name'     => Auth::user()->name ?? 'Supervisor',
                                        'is_variable'       => true,
                                        'weight_kg'         => round($totalWeight / $suggestedRolls, 2),
                                        'batch_code'        => $batchCode,
                                        'qr_code'           => "{$bultoQr}-R{$r}",
                                        'sku'               => $sku,
                                        'label_type'        => 'bobina',
                                    ];
                                }
                            }
                        } elseif ($isComposite) {
                            $currentIndex = 0;
                            $totalLabels = $qty * $suggestedRolls;
                            for ($k = 0; $k < $qty; $k++) {
                                $bultoQr = $product->sku ? 'PKG-' . $product->sku . '-B' . ($k + 1) . '-' . strtoupper(Str::random(4)) : ('PKG-B-' . strtoupper(Str::random(8)));
                                for ($r = 1; $r <= $suggestedRolls; $r++) {
                                    $currentIndex++;
                                    $labels[] = [
                                        'id'                => $product->id,
                                        'index'             => $currentIndex,
                                        'total_in_batch'    => $totalLabels,
                                        'product_name'      => "{$prodName} - BOBINA {$r}/{$suggestedRolls}",
                                        'presentation_info' => 'BOBINA - PESO VARIABLE',
                                        'operator_name'     => $catalogOperatorName,
                                        'production_date'   => $productionDate,
                                        'approver_name'     => Auth::user()->name ?? 'Supervisor',
                                        'is_variable'       => true,
                                        'weight_kg'         => round($totalWeight / $suggestedRolls, 2),
                                        'batch_code'        => $batchCode,
                                        'qr_code'           => "{$bultoQr}-R{$r}",
                                        'sku'               => $sku,
                                        'label_type'        => 'bobina',
                                    ];
                                }
                            }
                        } else {
                            for ($k = 0; $k < $qty; $k++) {
                                $labels[] = [
                                    'id'                => $product->id,
                                    'index'             => $k + 1,
                                    'total_in_batch'    => $qty,
                                    'product_name'      => $prodName,
                                    'presentation_info' => 'BOBINA - PESO VARIABLE',
                                    'operator_name'     => $catalogOperatorName,
                                    'production_date'   => $productionDate,
                                    'approver_name'     => Auth::user()->name ?? 'Supervisor',
                                    'is_variable'       => true,
                                    'weight_kg'         => $unitWeight,
                                    'batch_code'        => $batchCode,
                                    'qr_code'           => $product->sku ? 'PKG-' . $product->sku . '-' . strtoupper(Str::random(5)) : ('PKG-' . strtoupper(Str::random(10))),
                                    'sku'               => $sku,
                                    'label_type'        => 'bobina',
                                ];
                            }
                        }
                    } elseif ($scope === 'bulto') {
                        for ($b = 0; $b < $qty; $b++) {
                            $pres = $millarPerBulto > 1 ? "1 BULTO (" . (int)$millarPerBulto . " MILLARES)" : "1 {$saleUnit}";
                            $labels[] = [
                                'id'                => $product->id,
                                'index'             => $b + 1,
                                'total_in_batch'    => $qty,
                                'product_name'      => $prodName,
                                'presentation_info' => $pres,
                                'operator_name'     => $catalogOperatorName,
                                'production_date'   => $productionDate,
                                'approver_name'     => Auth::user()->name ?? 'Supervisor',
                                'is_variable'       => false,
                                'weight_kg'         => 0.0,
                                'batch_code'        => $batchCode,
                                'qr_code'           => $product->sku ? 'PKG-' . $product->sku . '-B' . ($b + 1) . '-' . strtoupper(Str::random(4)) : ('PKG-B-' . strtoupper(Str::random(8))),
                                'sku'               => $sku,
                                'label_type'        => 'bulto',
                            ];
                        }
                    } elseif ($scope === 'kit') {
                        $currentIndex = 0;
                        $totalLabels = $qty * ($millarPerBulto > 1 ? ((int)$millarPerBulto + 1) : 1);

                        for ($b = 0; $b < $qty; $b++) {
                            $bultoQr = $product->sku ? 'PKG-' . $product->sku . '-B' . ($b + 1) . '-' . strtoupper(Str::random(4)) : ('PKG-B-' . strtoupper(Str::random(8)));
                            if ($millarPerBulto > 1) {
                                $currentIndex++;
                                $labels[] = [
                                    'id'                => $product->id,
                                    'index'             => $currentIndex,
                                    'total_in_batch'    => $totalLabels,
                                    'product_name'      => $prodName,
                                    'presentation_info' => "1 BULTO (" . (int)$millarPerBulto . " MILLARES)",
                                    'operator_name'     => $catalogOperatorName,
                                    'production_date'   => $productionDate,
                                    'approver_name'     => Auth::user()->name ?? 'Supervisor',
                                    'is_variable'       => false,
                                    'weight_kg'         => 0.0,
                                    'batch_code'        => $batchCode,
                                    'qr_code'           => $bultoQr,
                                    'sku'               => $sku,
                                    'label_type'        => 'bulto',
                                ];

                                for ($m = 1; $m <= (int)$millarPerBulto; $m++) {
                                    $currentIndex++;
                                    $labels[] = [
                                        'id'                => $product->id,
                                        'index'             => $currentIndex,
                                        'total_in_batch'    => $totalLabels,
                                        'product_name'      => $prodName,
                                        'presentation_info' => "1 MILLAR",
                                        'operator_name'     => $catalogOperatorName,
                                        'production_date'   => $productionDate,
                                        'approver_name'     => Auth::user()->name ?? 'Supervisor',
                                        'is_variable'       => false,
                                        'weight_kg'         => 0.0,
                                        'batch_code'        => $batchCode,
                                        'qr_code'           => "{$bultoQr}-M{$m}",
                                        'sku'               => $sku,
                                        'label_type'        => 'millar',
                                    ];
                                }
                            } else {
                                $currentIndex++;
                                $labels[] = [
                                    'id'                => $product->id,
                                    'index'             => $currentIndex,
                                    'total_in_batch'    => $totalLabels,
                                    'product_name'      => $prodName,
                                    'presentation_info' => "1 MILLAR",
                                    'operator_name'     => $catalogOperatorName,
                                    'production_date'   => $productionDate,
                                    'approver_name'     => Auth::user()->name ?? 'Supervisor',
                                    'is_variable'       => false,
                                    'weight_kg'         => 0.0,
                                    'batch_code'        => $batchCode,
                                    'qr_code'           => $bultoQr,
                                    'sku'               => $sku,
                                    'label_type'        => 'millar',
                                ];
                            }
                        }
                    } else {
                        // Modo Millar por Defecto
                        $millarCount = ($millarPerBulto > 1) ? (int)$millarPerBulto : 1;
                        $totalMillares = $qty * $millarCount;
                        $currentIndex = 0;

                        for ($b = 0; $b < $qty; $b++) {
                            for ($m = 1; $m <= $millarCount; $m++) {
                                $currentIndex++;
                                $qr = $product->sku ? 'PKG-' . $product->sku . '-M' . $currentIndex . '-' . strtoupper(Str::random(4)) : ('PKG-M-' . strtoupper(Str::random(8)));
                                $labels[] = [
                                    'id'                => $product->id,
                                    'index'             => $currentIndex,
                                    'total_in_batch'    => $totalMillares,
                                    'product_name'      => $prodName,
                                    'presentation_info' => "1 MILLAR",
                                    'operator_name'     => $catalogOperatorName,
                                    'production_date'   => $productionDate,
                                    'approver_name'     => Auth::user()->name ?? 'Supervisor',
                                    'is_variable'       => false,
                                    'weight_kg'         => 0.0,
                                    'batch_code'        => $batchCode,
                                    'qr_code'           => $qr,
                                    'sku'               => $sku,
                                    'label_type'        => 'millar',
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Registrar auditoría de impresiones
        foreach ($labels as $lbl) {
            if (!empty($lbl['qr_code'])) {
                BagLabelPrint::logPrint(
                    $lbl['qr_code'],
                    $lbl['production_id'] ?? null,
                    Auth::id(),
                    $lbl['label_type'] ?? 'millar',
                    request()->ip(),
                    request()->userAgent()
                );
            }
        }

        return $this->renderPdf($labels, $template);
    }

    protected function renderPdf(array $labels, string $template)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        if ($template === 'sheet') {
            $pdf = Pdf::loadView('pdf.labels_sheet_qr', compact('labels'));
            $pdf->setPaper('letter', 'portrait');
        } elseif ($template === '58mm') {
            $pdf = Pdf::loadView('bag_factory.ticket_pdf', [
                'labels' => $labels,
                'size'   => '58mm',
            ]);
            $pdf->setPaper([0, 0, 164.4, 141.7 * max(1, count($labels))], 'portrait');
        } else {
            $pdf = Pdf::loadView('bag_factory.ticket_pdf', [
                'labels' => $labels,
                'size'   => '80mm',
            ]);
            $pdf->setPaper([0, 0, 226.7, 170.0 * max(1, count($labels))], 'portrait');
        }

        return $pdf->stream('etiquetas_generadas.pdf');
    }
}
