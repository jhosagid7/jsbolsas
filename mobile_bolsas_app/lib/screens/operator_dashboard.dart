import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:uuid/uuid.dart';
import '../services/local_db.dart';
import '../services/sync_service.dart';
import '../constants.dart';
import 'camera_scanner_screen.dart';

class OperatorDashboardScreen extends StatefulWidget {
  final String baseUrl;
  final String token;
  final String userName;

  const OperatorDashboardScreen({
    super.key,
    required this.baseUrl,
    required this.token,
    required this.userName,
  });

  @override
  State<OperatorDashboardScreen> createState() => _OperatorDashboardScreenState();
}

class _OperatorDashboardScreenState extends State<OperatorDashboardScreen> {
  final LocalDatabaseService _db = LocalDatabaseService.instance;
  final SyncService _sync = SyncService.instance;
  final _uuid = const Uuid();

  // Navigation state
  int _currentTabIndex = 0;

  // Payroll & Tariff State
  double _userDailySalary = 15.0;
  double _userWeeklySalary = 90.0;
  int _userWorkDays = 6;
  bool _payPartialPackages = true;

  // State
  Map<String, dynamic>? _activeShift;
  List<Map<String, dynamic>> _shiftProductions = [];
  List<Map<String, dynamic>> _cachedProducts = [];
  List<Map<String, dynamic>> _cachedMachines = [];
  int? _selectedMachineId;

  bool _isLoading = true;
  bool _isSyncing = false;
  int _pendingSyncCount = 0;
  String _selectedShiftType = 'diurno'; // 'diurno' | 'nocturno'

  // Form Controllers
  Map<String, dynamic>? _selectedProduct;
  final _quantityController = TextEditingController(text: '1');
  final _weightController = TextEditingController();

  // Variable Rolls Mode (for Bobinas & Rollos)
  bool _isIndividualRollMode = false;
  final List<Map<String, dynamic>> _variableRolls = [];
  final _rollWeightCtrl = TextEditingController();
  final _rollColorCtrl = TextEditingController();
  final _rollBatchCtrl = TextEditingController();

  Timer? _syncTimer;

  @override
  void initState() {
    super.initState();
    _loadInitialData();
    _syncTimer = Timer.periodic(const Duration(seconds: 30), (_) => _triggerSync(silent: true));
  }

  @override
  void dispose() {
    _syncTimer?.cancel();
    _quantityController.dispose();
    _weightController.dispose();
    _rollWeightCtrl.dispose();
    _rollColorCtrl.dispose();
    _rollBatchCtrl.dispose();
    super.dispose();
  }

  Map<String, dynamic> _getProductUnitLabels(Map<String, dynamic>? p) {
    if (p == null) {
      return {
        'unit_type': 'bulto',
        'meta_suffix': 'bultos/turno',
        'primary_label': 'por bulto',
        'secondary_label': '',
        'unit_name_singular': 'bulto',
        'unit_name_plural': 'bultos',
        'button_label': 'REGISTRAR PRODUCCIÓN',
        'input_label': 'Cantidad (Bultos)',
        'icon': Icons.inventory_2_outlined,
        'color': const Color(0xFF38BDF8),
      };
    }

    final name = (p['name'] ?? '').toString().toUpperCase();
    final saleUnit = (p['sale_unit'] ?? '').toString().toUpperCase();
    final isVar = p['is_variable_quantity'] == 1 || p['is_variable_quantity'] == true || p['is_variable_quantity'] == '1';
    final millarPerBulto = (p['millar_per_bulto'] != null)
        ? (double.tryParse(p['millar_per_bulto'].toString()) ?? 1.0)
        : 1.0;

    if (name.contains('ROLLO') || saleUnit == 'ROLLO') {
      return {
        'unit_type': 'rollo',
        'meta_suffix': 'rollos/turno',
        'primary_label': 'por rollo',
        'secondary_label': '',
        'unit_name_singular': 'rollo',
        'unit_name_plural': 'rollos',
        'button_label': 'REGISTRAR ROLLOS',
        'input_label': 'Cantidad (Rollos)',
        'icon': Icons.rotate_right_rounded,
        'color': Colors.amberAccent,
      };
    }

    if (isVar || name.contains('BOBINA') || saleUnit == 'BOBINA' || name.contains('CADENETA')) {
      return {
        'unit_type': 'bobina',
        'meta_suffix': 'bobinas/turno',
        'primary_label': 'por bobina',
        'secondary_label': '',
        'unit_name_singular': 'bobina',
        'unit_name_plural': 'bobinas',
        'button_label': 'REGISTRAR BOBINAS',
        'input_label': 'Cantidad (Bobinas)',
        'icon': Icons.rotate_right_rounded,
        'color': Colors.amberAccent,
      };
    }

    if (saleUnit == 'KG') {
      return {
        'unit_type': 'kg',
        'meta_suffix': 'Kg/turno',
        'primary_label': 'por Kg',
        'secondary_label': '',
        'unit_name_singular': 'Kg',
        'unit_name_plural': 'Kg',
        'button_label': 'REGISTRAR KG',
        'input_label': 'Cantidad (Kg)',
        'icon': Icons.scale_rounded,
        'color': Colors.amberAccent,
      };
    }

    if (millarPerBulto > 1) {
      final milCountStr = millarPerBulto == millarPerBulto.roundToDouble()
          ? millarPerBulto.toInt().toString()
          : millarPerBulto.toStringAsFixed(1);
      return {
        'unit_type': 'bulto_multi_millar',
        'meta_suffix': 'bultos/turno ($milCountStr mil/bulto)',
        'primary_label': 'por bulto',
        'secondary_label': 'por millar',
        'unit_name_singular': 'bulto',
        'unit_name_plural': 'bultos',
        'button_label': 'REGISTRAR BULTOS',
        'input_label': 'Cantidad ($milCountStr mil/bulto)',
        'icon': Icons.inventory_2_outlined,
        'color': const Color(0xFF38BDF8),
      };
    }

    if (saleUnit == 'MILLAR' || (name.contains('MILLAR') && !name.contains('BULTO'))) {
      return {
        'unit_type': 'millar',
        'meta_suffix': 'millares/turno',
        'primary_label': 'por millar',
        'secondary_label': '',
        'unit_name_singular': 'millar',
        'unit_name_plural': 'millares',
        'button_label': 'REGISTRAR MILLARES',
        'input_label': 'Cantidad (Millares)',
        'icon': Icons.inventory_2_outlined,
        'color': const Color(0xFF38BDF8),
      };
    }

    if (saleUnit == 'PAQUETE' || name.contains('PAQUETE') || name.contains('UND') || name.contains('UNID')) {
      return {
        'unit_type': 'paquete',
        'meta_suffix': 'paquetes/turno',
        'primary_label': 'por paquete',
        'secondary_label': '',
        'unit_name_singular': 'paquete',
        'unit_name_plural': 'paquetes',
        'button_label': 'REGISTRAR PAQUETES',
        'input_label': 'Cantidad (Paquetes)',
        'icon': Icons.inventory_2_outlined,
        'color': const Color(0xFF38BDF8),
      };
    }

    return {
      'unit_type': 'bulto',
      'meta_suffix': 'bultos/turno',
      'primary_label': 'por bulto',
      'secondary_label': '',
      'unit_name_singular': 'bulto',
      'unit_name_plural': 'bultos',
      'button_label': 'REGISTRAR BULTOS',
      'input_label': 'Cantidad (Bultos)',
      'icon': Icons.inventory_2_outlined,
      'color': const Color(0xFF38BDF8),
    };
  }

  bool _isRollProduct(Map<String, dynamic>? p) {
    if (p == null) return false;
    final type = _getProductUnitLabels(p)['unit_type'] as String;
    return type == 'bobina' || type == 'rollo';
  }

  String _getProductUnitLabel(Map<String, dynamic>? p) {
    return _getProductUnitLabels(p)['input_label'] as String;
  }

  String _getButtonLabel(Map<String, dynamic>? p) {
    return _getProductUnitLabels(p)['button_label'] as String;
  }

  Map<String, double> _calculateLaborTariff(Map<String, dynamic> product) {
    final targetUnits = (product['target_units_per_shift'] != null)
        ? (double.tryParse(product['target_units_per_shift'].toString()) ?? 5.0)
        : 5.0;
    final safeTarget = targetUnits > 0 ? targetUnits : 1.0;
    final packageTariff = _userDailySalary / safeTarget;

    final millarPerBulto = (product['millar_per_bulto'] != null)
        ? (double.tryParse(product['millar_per_bulto'].toString()) ?? 1.0)
        : 1.0;
    final safeMillar = millarPerBulto > 0 ? millarPerBulto : 1.0;
    final fractionTariff = packageTariff / safeMillar;

    return {
      'package_tariff': packageTariff,
      'fraction_tariff': fractionTariff,
      'target_units': safeTarget,
    };
  }

  double _calculateShiftEarnings() {
    double total = 0.0;
    for (var prod in _shiftProductions) {
      final productId = prod['product_id'];
      final qty = (prod['quantity'] as num?)?.toDouble() ?? 0.0;
      final p = _cachedProducts.firstWhere(
        (cp) => cp['id'] == productId,
        orElse: () => <String, dynamic>{},
      );
      if (p.isNotEmpty) {
        final tariffs = _calculateLaborTariff(p);
        final pkgTariff = tariffs['package_tariff'] ?? 0.0;
        final fracTariff = tariffs['fraction_tariff'] ?? 0.0;
        final millarPerBulto = (p['millar_per_bulto'] != null)
            ? (double.tryParse(p['millar_per_bulto'].toString()) ?? 1.0)
            : 1.0;

        if (millarPerBulto > 1) {
          final completed = (qty / millarPerBulto).floorToDouble();
          final frac = qty % millarPerBulto;
          if (_payPartialPackages) {
            total += (completed * pkgTariff) + (frac * fracTariff);
          } else {
            total += (completed * pkgTariff);
          }
        } else {
          total += qty * pkgTariff;
        }
      } else {
        total += qty * (_userDailySalary / 5.0);
      }
    }
    return total;
  }

  Future<void> _loadInitialData() async {
    setState(() => _isLoading = true);

    final prefs = await SharedPreferences.getInstance();
    final dailySal = prefs.getDouble('daily_salary') ?? 15.0;
    final weeklySal = prefs.getDouble('weekly_salary') ?? 90.0;
    final workDays = prefs.getInt('work_days_per_week') ?? 6;
    final payPartial = prefs.getBool('pay_partial_packages') ?? true;

    final products = await _db.getCachedProducts();
    final machines = await _db.getCachedMachines();
    final activeShift = await _db.getActiveLocalShift();
    List<Map<String, dynamic>> prods = [];
    if (activeShift != null) {
      prods = await _db.getShiftProductions(activeShift['sync_id']);
    }

    final pendingShifts = await _db.getPendingSyncShifts();
    final pendingProds = await _db.getPendingSyncProductions();

    setState(() {
      _userDailySalary = dailySal;
      _userWeeklySalary = weeklySal;
      _userWorkDays = workDays;
      _payPartialPackages = payPartial;
      _cachedProducts = products;
      _cachedMachines = machines;
      if (_cachedMachines.isNotEmpty && _selectedMachineId == null) {
        _selectedMachineId = _cachedMachines.first['id'] as int?;
      }
      _activeShift = activeShift;
      _shiftProductions = prods;
      _pendingSyncCount = pendingShifts.length + pendingProds.length;
      _isLoading = false;
      if (_cachedProducts.isNotEmpty && _selectedProduct == null) {
        _selectedProduct = _cachedProducts.first;
      }
    });

    _sync.refreshProductCatalog(widget.baseUrl, widget.token).then((_) async {
      final updated = await _db.getCachedProducts();
      if (mounted) setState(() => _cachedProducts = updated);
    });

    _sync.refreshMachinesCatalog(widget.baseUrl, widget.token).then((_) async {
      final updated = await _db.getCachedMachines();
      if (mounted) {
        setState(() {
          _cachedMachines = updated;
          if (_selectedMachineId == null && updated.isNotEmpty) {
            _selectedMachineId = updated.first['id'] as int?;
          }
        });
      }
    });

    _triggerSync(silent: true);
  }

  Future<void> _triggerSync({bool silent = false}) async {
    if (_isSyncing) return;
    if (!silent && mounted) setState(() => _isSyncing = true);

    final res = await _sync.syncPendingData(widget.baseUrl, widget.token);

    if (!mounted) return;

    final pendingShifts = await _db.getPendingSyncShifts();
    final pendingProds = await _db.getPendingSyncProductions();

    List<Map<String, dynamic>> prods = [];
    if (_activeShift != null) {
      prods = await _db.getShiftProductions(_activeShift!['sync_id']);
    }

    setState(() {
      _pendingSyncCount = pendingShifts.length + pendingProds.length;
      _shiftProductions = prods;
      _isSyncing = false;
    });

    if (!silent && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res.success
              ? '✅ Sincronizado: ${res.syncedProductions} registros y ${res.syncedShifts} turnos.'
              : '☁️ Sincronización en cola (Modo Offline)'),
          backgroundColor: res.success ? const Color(0xFF10B981) : Colors.orange,
          duration: const Duration(seconds: 3),
        ),
      );
    }
  }

  Future<void> _openShift() async {
    if (_cachedMachines.isNotEmpty && _selectedMachineId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('⚠️ Por favor selecciona una máquina para iniciar el turno'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    final now = DateTime.now();
    final syncId = 'SHIFT-${_uuid.v4()}';

    await _db.openLocalShift(
      shiftType: _selectedShiftType,
      machineId: _selectedMachineId,
      startTime: now.toIso8601String(),
      syncId: syncId,
    );

    await _loadInitialData();
    _triggerSync();
  }

  // --- Lector de Código de Barras / QR con Cámara ---
  Future<void> _scanBarcode() async {
    final scannedCode = await Navigator.push<String>(
      context,
      MaterialPageRoute(builder: (_) => const CameraScannerScreen()),
    );

    if (scannedCode != null && scannedCode.isNotEmpty) {
      final query = scannedCode.trim().toLowerCase();
      final match = _cachedProducts.firstWhere(
        (p) => (p['sku'] ?? '').toString().toLowerCase() == query ||
               (p['name'] ?? '').toString().toLowerCase().contains(query) ||
               p['id'].toString() == query,
        orElse: () => <String, dynamic>{},
      );

      if (match.isNotEmpty) {
        setState(() {
          _selectedProduct = match;
          _variableRolls.clear();
          _isIndividualRollMode = _isRollProduct(match);
        });
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('✅ Producto escaneado: ${match['name']}'),
              backgroundColor: const Color(0xFF10B981),
            ),
          );
        }
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('⚠️ No se encontró producto con código: $scannedCode'),
              backgroundColor: Colors.amber.shade900,
            ),
          );
        }
      }
    }
  }

  // --- Modal de Búsqueda Predictiva de Productos ---
  Future<Map<String, dynamic>?> _showProductSearchDialog({Map<String, dynamic>? initial}) async {
    return await showModalBottomSheet<Map<String, dynamic>>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        String query = '';
        return StatefulBuilder(
          builder: (modalCtx, setModalState) {
            final filtered = _cachedProducts.where((p) {
              final name = (p['name'] ?? '').toString().toLowerCase();
              final sku = (p['sku'] ?? '').toString().toLowerCase();
              final q = query.toLowerCase().trim();
              return q.isEmpty || name.contains(q) || sku.contains(q);
            }).toList();

            return Container(
              height: MediaQuery.of(ctx).size.height * 0.85,
              decoration: const BoxDecoration(
                color: Color(0xFF1C2541),
                borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
              ),
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(color: Colors.white24, borderRadius: BorderRadius.circular(2)),
                  ),
                  const SizedBox(height: 14),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          const Icon(Icons.search_rounded, color: Color(0xFF38BDF8), size: 22),
                          const SizedBox(width: 8),
                          Text(
                            'Catálogo de Fábrica (${_cachedProducts.length})',
                            style: GoogleFonts.plusJakartaSans(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                      IconButton(
                        icon: const Icon(Icons.qr_code_scanner, color: Color(0xFF38BDF8)),
                        tooltip: 'Escanear Código / QR',
                        onPressed: () async {
                          final code = await Navigator.push<String>(
                            ctx,
                            MaterialPageRoute(builder: (_) => const CameraScannerScreen()),
                          );
                          if (code != null && code.isNotEmpty) {
                            final match = _cachedProducts.firstWhere(
                              (p) => (p['sku'] ?? '').toString().toLowerCase() == code.trim().toLowerCase() ||
                                     (p['name'] ?? '').toString().toLowerCase().contains(code.trim().toLowerCase()),
                              orElse: () => <String, dynamic>{},
                            );
                            if (match.isNotEmpty) {
                              Navigator.pop(ctx, match);
                            } else {
                              setModalState(() => query = code.trim());
                            }
                          }
                        },
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    autofocus: true,
                    style: const TextStyle(color: Colors.white),
                    decoration: InputDecoration(
                      hintText: 'Buscar por medida, nombre o código SKU...',
                      hintStyle: const TextStyle(color: Colors.white38),
                      filled: true,
                      fillColor: const Color(0xFF0F172A),
                      prefixIcon: const Icon(Icons.filter_alt_outlined, color: Colors.white54),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                    ),
                    onChanged: (val) => setModalState(() => query = val),
                  ),
                  const SizedBox(height: 12),
                  Expanded(
                    child: filtered.isEmpty
                        ? const Center(
                            child: Text('No se encontraron productos con esa búsqueda', style: TextStyle(color: Colors.white38)),
                          )
                        : ListView.separated(
                            itemCount: filtered.length,
                            separatorBuilder: (_, __) => const Divider(height: 1, color: Colors.white10),
                            itemBuilder: (context, idx) {
                              final p = filtered[idx];
                              final isSelected = initial != null && initial['id'] == p['id'];
                              final labels = _getProductUnitLabels(p);
                              final isMultiMillar = labels['unit_type'] == 'bulto_multi_millar';

                              String badgeText = (labels['unit_type'] as String).toUpperCase();
                              if (isMultiMillar) {
                                final milCount = (double.tryParse(p['millar_per_bulto']?.toString() ?? '1') ?? 1).toInt();
                                badgeText = '$milCount MIL/BULTO';
                              }

                              return ListTile(
                                dense: true,
                                contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                leading: Container(
                                  padding: const EdgeInsets.all(8),
                                  decoration: BoxDecoration(
                                    color: isSelected ? const Color(0xFF0284C7) : const Color(0xFF0F172A),
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: Icon(
                                    labels['icon'] as IconData,
                                    color: isSelected ? Colors.white : (labels['color'] as Color),
                                    size: 18,
                                  ),
                                ),
                                title: Row(
                                  children: [
                                    Expanded(
                                      child: Text(
                                        p['name'] ?? '',
                                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 13),
                                      ),
                                    ),
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                      decoration: BoxDecoration(
                                        color: (labels['color'] as Color).withOpacity(0.2),
                                        borderRadius: BorderRadius.circular(4),
                                      ),
                                      child: Text(
                                        badgeText,
                                        style: TextStyle(color: labels['color'] as Color, fontSize: 9, fontWeight: FontWeight.bold),
                                      ),
                                    ),
                                  ],
                                ),
                                subtitle: Text('SKU: ${p['sku'] ?? 'N/A'}', style: const TextStyle(color: Colors.white38, fontSize: 11)),
                                trailing: isSelected ? const Icon(Icons.check_circle, color: Color(0xFF10B981), size: 18) : null,
                                onTap: () => Navigator.pop(ctx, p),
                              );
                            },
                          ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  void _addVariableRoll() {
    final w = double.tryParse(_rollWeightCtrl.text.trim()) ?? 0.0;
    if (w <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Ingrese un peso válido para el rollo'), backgroundColor: Colors.red),
      );
      return;
    }

    setState(() {
      _variableRolls.add({
        'weight': w,
        'color': _rollColorCtrl.text.trim(),
        'batch': _rollBatchCtrl.text.trim(),
      });

      _rollWeightCtrl.clear();
      _rollColorCtrl.clear();
      _rollBatchCtrl.clear();

      _quantityController.text = _variableRolls.length.toString();
      final totalWeight = _variableRolls.fold(0.0, (sum, roll) => sum + (roll['weight'] as double));
      _weightController.text = totalWeight.toStringAsFixed(2);
    });
  }

  Future<void> _recordProduction() async {
    if (_selectedProduct == null) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Seleccione un producto'), backgroundColor: Colors.red),
        );
      }
      return;
    }

    final qty = double.tryParse(_quantityController.text.trim()) ?? 0.0;
    var weight = double.tryParse(_weightController.text.trim()) ?? 0.0;

    if (qty <= 0 || weight <= 0) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Ingrese cantidad y peso válidos mayores a 0'), backgroundColor: Colors.red),
        );
      }
      return;
    }

    // Auto-sanitización si se ingresó en gramos desde báscula de mesa (ej. 12030 g -> 12.03 Kg)
    if (weight >= 500 && !_isRollProduct(_selectedProduct)) {
      weight = double.parse((weight / 1000.0).toStringAsFixed(4));
    }

    final now = DateTime.now();
    final syncId = 'PROD-${_uuid.v4()}';
    String? metadataJson;
    if (_variableRolls.isNotEmpty) {
      metadataJson = json.encode(_variableRolls);
    }

    await _db.saveLocalProduction(
      shiftSyncId: _activeShift!['sync_id'],
      productId: _selectedProduct!['id'],
      machineId: _selectedMachineId,
      productName: _selectedProduct!['name'],
      quantity: qty,
      weight: weight,
      recordedAt: now.toIso8601String(),
      syncId: syncId,
      metadata: metadataJson,
    );

    // Reset Form
    setState(() {
      _weightController.clear();
      _variableRolls.clear();
      _quantityController.text = '1';
    });

    await _loadInitialData();
    _triggerSync(silent: true);
  }

  // --- Modal de Edición Completa ---
  Future<void> _showEditProductionDialog(Map<String, dynamic> item) async {
    Map<String, dynamic> currentProduct = {
      'id': item['product_id'],
      'name': item['product_name'],
      'sku': item['sku'] ?? '',
    };

    final found = _cachedProducts.firstWhere(
      (p) => p['id'] == item['product_id'],
      orElse: () => currentProduct,
    );
    currentProduct = Map<String, dynamic>.from(found);

    List<Map<String, dynamic>> editRolls = [];
    if (item['metadata'] != null && item['metadata'].toString().isNotEmpty) {
      try {
        final decoded = item['metadata'] is String ? json.decode(item['metadata']) : item['metadata'];
        if (decoded is List) {
          editRolls = List<Map<String, dynamic>>.from(decoded.map((x) => Map<String, dynamic>.from(x)));
        }
      } catch (_) {}
    }

    final qtyCtrl = TextEditingController(text: item['quantity'].toString());
    final weightCtrl = TextEditingController(text: item['weight'].toString());

    final rollWeightAddCtrl = TextEditingController();
    final rollColorAddCtrl = TextEditingController();

    await showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (dialogCtx, setDialogState) {
          final isRoll = _isRollProduct(currentProduct);

          void recalcRollTotals() {
            if (editRolls.isNotEmpty) {
              qtyCtrl.text = editRolls.length.toString();
              final sumW = editRolls.fold(0.0, (sum, r) => sum + (double.tryParse(r['weight'].toString()) ?? 0.0));
              weightCtrl.text = sumW.toStringAsFixed(2);
            }
          }

          return AlertDialog(
            backgroundColor: const Color(0xFF1E293B),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            title: Row(
              children: [
                const Icon(Icons.edit_note_rounded, color: Color(0xFF38BDF8), size: 24),
                const SizedBox(width: 8),
                Text('Editar Registro Cargado',
                    style: GoogleFonts.plusJakartaSans(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
              ],
            ),
            content: SizedBox(
              width: double.maxFinite,
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Tipo de Producto:', style: TextStyle(color: Colors.white70, fontSize: 12)),
                    const SizedBox(height: 6),
                    InkWell(
                      onTap: () async {
                        final picked = await _showProductSearchDialog(initial: currentProduct);
                        if (picked != null) {
                          setDialogState(() {
                            currentProduct = picked;
                          });
                        }
                      },
                      borderRadius: BorderRadius.circular(10),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                        decoration: BoxDecoration(
                          color: const Color(0xFF0F172A),
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(color: const Color(0xFF38BDF8).withOpacity(0.5)),
                        ),
                        child: Row(
                          children: [
                            Icon(
                              isRoll ? Icons.rotate_right_rounded : Icons.inventory_2_outlined,
                              color: isRoll ? Colors.amberAccent : const Color(0xFF38BDF8),
                              size: 18,
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                currentProduct['name'] ?? 'Seleccionar producto',
                                style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
                              ),
                            ),
                            const Icon(Icons.arrow_drop_down, color: Colors.white54),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 14),

                    // Desglose de Rollos si es Bobina
                    if (isRoll) ...[
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: const Color(0xFF0F172A),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: Colors.amber.withOpacity(0.3)),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  '🌀 Desglose de Bobinas (${editRolls.length})',
                                  style: const TextStyle(color: Colors.amberAccent, fontWeight: FontWeight.bold, fontSize: 12),
                                ),
                              ],
                            ),
                            const SizedBox(height: 8),

                            // Lista de Bobinas Existentes
                            if (editRolls.isNotEmpty) ...[
                              ...editRolls.asMap().entries.map((entry) {
                                final idx = entry.key;
                                final roll = entry.value;
                                return Container(
                                  margin: const EdgeInsets.only(bottom: 6),
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFF1E293B),
                                    borderRadius: BorderRadius.circular(8),
                                    border: Border.all(color: Colors.white10),
                                  ),
                                  child: Row(
                                    children: [
                                      Text('#${idx + 1}', style: const TextStyle(color: Colors.amberAccent, fontWeight: FontWeight.bold, fontSize: 12)),
                                      const SizedBox(width: 8),
                                      Expanded(
                                        child: Text(
                                          '${roll['weight']} Kg ${roll['color'] != null && roll['color'].toString().isNotEmpty ? '(${roll['color']})' : ''}',
                                          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
                                        ),
                                      ),
                                      IconButton(
                                        icon: const Icon(Icons.delete_outline, color: Colors.redAccent, size: 18),
                                        padding: EdgeInsets.zero,
                                        constraints: const BoxConstraints(),
                                        onPressed: () {
                                          setDialogState(() {
                                            editRolls.removeAt(idx);
                                            recalcRollTotals();
                                          });
                                        },
                                      ),
                                    ],
                                  ),
                                );
                              }),
                              const SizedBox(height: 6),
                            ],

                            // Agregar nueva bobina
                            Row(
                              children: [
                                Expanded(
                                  flex: 3,
                                  child: TextField(
                                    controller: rollWeightAddCtrl,
                                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                    style: const TextStyle(color: Colors.amberAccent, fontWeight: FontWeight.bold),
                                    decoration: InputDecoration(
                                      labelText: 'Peso Bobina (Kg)',
                                      labelStyle: const TextStyle(color: Colors.white60, fontSize: 11),
                                      suffixText: 'Kg',
                                      suffixStyle: const TextStyle(color: Colors.amberAccent, fontSize: 11),
                                      filled: true,
                                      fillColor: const Color(0xFF1E293B),
                                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide.none),
                                      contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 6),
                                Expanded(
                                  flex: 2,
                                  child: TextField(
                                    controller: rollColorAddCtrl,
                                    style: const TextStyle(color: Colors.white),
                                    decoration: InputDecoration(
                                      labelText: 'Color',
                                      labelStyle: const TextStyle(color: Colors.white60, fontSize: 11),
                                      filled: true,
                                      fillColor: const Color(0xFF1E293B),
                                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide.none),
                                      contentPadding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 6),
                                ElevatedButton(
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: const Color(0xFF0284C7),
                                    foregroundColor: Colors.white,
                                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                  ),
                                  onPressed: () {
                                    final w = double.tryParse(rollWeightAddCtrl.text.trim()) ?? 0.0;
                                    if (w > 0) {
                                      setDialogState(() {
                                        editRolls.add({
                                          'weight': w,
                                          'color': rollColorAddCtrl.text.trim(),
                                        });
                                        rollWeightAddCtrl.clear();
                                        rollColorAddCtrl.clear();
                                        recalcRollTotals();
                                      });
                                    }
                                  },
                                  child: const Icon(Icons.add, size: 18),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 14),
                    ],

                    Row(
                      children: [
                        Expanded(
                          flex: 2,
                          child: TextField(
                            controller: qtyCtrl,
                            keyboardType: const TextInputType.numberWithOptions(decimal: true),
                            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                            decoration: InputDecoration(
                              labelText: isRoll ? 'Rollos' : 'Cantidad',
                              labelStyle: const TextStyle(color: Colors.white54),
                              filled: true,
                              fillColor: const Color(0xFF0F172A),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                            ),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          flex: 3,
                          child: TextField(
                            controller: weightCtrl,
                            keyboardType: const TextInputType.numberWithOptions(decimal: true),
                            style: const TextStyle(color: Colors.amberAccent, fontWeight: FontWeight.bold, fontSize: 16),
                            decoration: InputDecoration(
                              labelText: 'Peso Báscula',
                              labelStyle: const TextStyle(color: Colors.white54),
                              suffixText: 'Kg',
                              suffixStyle: const TextStyle(color: Colors.amberAccent),
                              filled: true,
                              fillColor: const Color(0xFF0F172A),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(ctx),
                child: const Text('CANCELAR', style: TextStyle(color: Colors.white54)),
              ),
              ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF0284C7),
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
                icon: const Icon(Icons.check, size: 16),
                label: const Text('GUARDAR CAMBIOS'),
                onPressed: () async {
                  final newQty = double.tryParse(qtyCtrl.text.trim()) ?? 0.0;
                  var newWeight = double.tryParse(weightCtrl.text.trim()) ?? 0.0;

                  if (newQty <= 0 || newWeight <= 0) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Ingrese cantidad y peso válidos'), backgroundColor: Colors.red),
                    );
                    return;
                  }

                  // Auto-sanitización si se ingresó en gramos desde báscula de mesa (ej. 12030 g -> 12.03 Kg)
                  if (newWeight >= 500 && !isRoll) {
                    newWeight = double.parse((newWeight / 1000.0).toStringAsFixed(4));
                  }

                  Navigator.pop(ctx);

                  final metaJson = (isRoll && editRolls.isNotEmpty) ? json.encode(editRolls) : null;

                  await _db.updateLocalProduction(
                    id: item['id'],
                    shiftSyncId: _activeShift!['sync_id'],
                    productId: currentProduct['id'],
                    productName: currentProduct['name'],
                    quantity: newQty,
                    weight: newWeight,
                    metadata: metaJson,
                  );

                  await _loadInitialData();
                  _triggerSync(silent: true);
                },
              ),
            ],
          );
        },
      ),
    );
  }

  Future<void> _confirmDeleteProduction(Map<String, dynamic> item) async {
    await showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF1E293B),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('¿Eliminar este registro?', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        content: Text(
          'Se eliminará "${item['product_name']}" (${item['quantity']} - ${item['weight']} Kg) de este turno.',
          style: const TextStyle(color: Colors.white70, fontSize: 13),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('CANCELAR', style: TextStyle(color: Colors.white54)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFEF4444), foregroundColor: Colors.white),
            onPressed: () async {
              Navigator.pop(ctx);
              await _db.deleteLocalProduction(
                item['id'],
                _activeShift!['sync_id'],
              );
              await _loadInitialData();
              _triggerSync(silent: true);
            },
            child: const Text('ELIMINAR'),
          ),
        ],
      ),
    );
  }

  Future<void> _showCloseShiftDialog() async {
    final notesController = TextEditingController();
    double totalKg = 0;
    double totalUnits = 0;
    for (var p in _shiftProductions) {
      totalUnits += (p['quantity'] as num).toDouble();
      totalKg += (p['weight'] as num).toDouble();
    }

    await showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF1E293B),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Row(
          children: [
            const Icon(Icons.stop_circle_outlined, color: Color(0xFFEF4444), size: 24),
            const SizedBox(width: 8),
            Text('Cierre de Turno',
                style: GoogleFonts.plusJakartaSans(color: Colors.white, fontWeight: FontWeight.bold)),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('¿Desea cerrar su turno de producción?',
                style: GoogleFonts.plusJakartaSans(color: Colors.white70, fontSize: 13)),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFF0F172A),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: Colors.white12),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: [
                  Column(
                    children: [
                      Text('Unidades/Rollos', style: GoogleFonts.plusJakartaSans(color: Colors.white54, fontSize: 11)),
                      Text(totalUnits.toStringAsFixed(0),
                          style: GoogleFonts.plusJakartaSans(color: const Color(0xFF38BDF8), fontSize: 18, fontWeight: FontWeight.bold)),
                    ],
                  ),
                  Container(width: 1, height: 30, color: Colors.white12),
                  Column(
                    children: [
                      Text('Peso Total', style: GoogleFonts.plusJakartaSans(color: Colors.white54, fontSize: 11)),
                      Text('${totalKg.toStringAsFixed(2)} Kg',
                          style: GoogleFonts.plusJakartaSans(color: const Color(0xFF10B981), fontSize: 18, fontWeight: FontWeight.bold)),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),
            TextField(
              controller: notesController,
              style: const TextStyle(color: Colors.white),
              decoration: InputDecoration(
                labelText: 'Notas u observaciones (Opcional)',
                labelStyle: const TextStyle(color: Colors.white54),
                filled: true,
                fillColor: const Color(0xFF0F172A),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('CANCELAR', style: TextStyle(color: Colors.white54)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFEF4444),
              foregroundColor: Colors.white,
            ),
            onPressed: () async {
              Navigator.pop(ctx);
              await _db.closeLocalShift(
                syncId: _activeShift!['sync_id'],
                endTime: DateTime.now().toIso8601String(),
                notes: notesController.text.trim(),
              );
              await _loadInitialData();
              _triggerSync();
            },
            child: const Text('CERRAR TURNO'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0B132B),
      appBar: AppBar(
        backgroundColor: const Color(0xFF1C2541),
        elevation: 0,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('JSBolsas Pro v$kAppVersion',
                style: GoogleFonts.plusJakartaSans(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
            Text('Operario: ${widget.userName}',
                style: GoogleFonts.plusJakartaSans(color: Colors.white70, fontSize: 11)),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.qr_code_scanner, color: Color(0xFF38BDF8)),
            tooltip: 'Escanear Código / QR',
            onPressed: _scanBarcode,
          ),
          InkWell(
            onTap: () => _triggerSync(),
            borderRadius: BorderRadius.circular(20),
            child: Container(
              margin: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: _pendingSyncCount > 0 ? const Color(0x33F59E0B) : const Color(0x3310B981),
                border: Border.all(color: _pendingSyncCount > 0 ? const Color(0xFFF59E0B) : const Color(0xFF10B981)),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Row(
                children: [
                  Icon(
                    _isSyncing ? Icons.sync : (_pendingSyncCount > 0 ? Icons.cloud_upload : Icons.cloud_done),
                    size: 14,
                    color: _pendingSyncCount > 0 ? const Color(0xFFF59E0B) : const Color(0xFF10B981),
                  ),
                  const SizedBox(width: 4),
                  Text(
                    _pendingSyncCount > 0 ? '$_pendingSyncCount pend.' : 'Sync OK',
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.bold,
                      color: _pendingSyncCount > 0 ? const Color(0xFFF59E0B) : const Color(0xFF10B981),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF38BDF8)))
          : _buildTabBody(),
      bottomNavigationBar: Container(
        decoration: const BoxDecoration(
          color: Color(0xFF1C2541),
          border: Border(top: BorderSide(color: Colors.white10)),
        ),
        child: BottomNavigationBar(
          currentIndex: _currentTabIndex,
          onTap: (index) => setState(() => _currentTabIndex = index),
          backgroundColor: const Color(0xFF1C2541),
          selectedItemColor: const Color(0xFF38BDF8),
          unselectedItemColor: Colors.white54,
          type: BottomNavigationBarType.fixed,
          selectedLabelStyle: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.bold, fontSize: 11),
          unselectedLabelStyle: GoogleFonts.plusJakartaSans(fontSize: 10),
          items: const [
            BottomNavigationBarItem(
              icon: Icon(Icons.dashboard_rounded),
              activeIcon: Icon(Icons.dashboard_rounded, color: Color(0xFF38BDF8)),
              label: 'Turno',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.scale_rounded),
              activeIcon: Icon(Icons.scale_rounded, color: Color(0xFF38BDF8)),
              label: 'Pesaje',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.format_list_bulleted_rounded),
              activeIcon: Icon(Icons.format_list_bulleted_rounded, color: Color(0xFF38BDF8)),
              label: 'Historial',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.payments_rounded),
              activeIcon: Icon(Icons.payments_rounded, color: Color(0xFF10B981)),
              label: 'Mi Nómina',
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTabBody() {
    switch (_currentTabIndex) {
      case 0:
        return SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildShiftCard(),
              if (_activeShift != null) ...[
                const SizedBox(height: 16),
                _buildEarningsHighlightCard(),
                const SizedBox(height: 16),
                _buildGoalProgressCard(),
                const SizedBox(height: 16),
                _buildQuickActions(),
              ],
            ],
          ),
        );
      case 1:
        return SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (_activeShift == null) ...[
                _buildOpenShiftNotice(),
              ] else ...[
                _buildProductionFormCard(),
              ],
            ],
          ),
        );
      case 2:
        return SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildShiftHistoryList(),
            ],
          ),
        );
      case 3:
        return _buildPayrollTab();
      default:
        return const SizedBox.shrink();
    }
  }

  Widget _buildEarningsHighlightCard() {
    final earnings = _calculateShiftEarnings();
    double totalUnits = 0;
    for (var p in _shiftProductions) {
      totalUnits += (p['quantity'] as num?)?.toDouble() ?? 0.0;
    }

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF064E3B), Color(0xFF065F46), Color(0xFF0F766E)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: const [
          BoxShadow(color: Color(0x4D000000), blurRadius: 10, offset: Offset(0, 4)),
        ],
        border: Border.all(color: const Color(0xFF34D399).withOpacity(0.4)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.15),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.attach_money_rounded, color: Color(0xFF6EE7B7), size: 20),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    'MI GANANCIA DEL TURNO',
                    style: GoogleFonts.plusJakartaSans(
                      color: const Color(0xFFD1FAE5),
                      fontWeight: FontWeight.bold,
                      fontSize: 12,
                      letterSpacing: 0.5,
                    ),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: Colors.black.withOpacity(0.25),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: const Color(0xFF34D399).withOpacity(0.4)),
                ),
                child: const Text(
                  'Destajo Ganado',
                  style: TextStyle(color: Color(0xFF6EE7B7), fontSize: 10, fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            crossAxisAlignment: CrossAxisAlignment.baseline,
            textBaseline: TextBaseline.alphabetic,
            children: [
              Text(
                '\$ ${earnings.toStringAsFixed(2)}',
                style: GoogleFonts.plusJakartaSans(
                  color: Colors.white,
                  fontSize: 32,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(width: 6),
              Text(
                'USD',
                style: GoogleFonts.plusJakartaSans(
                  color: const Color(0xFFA7F3D0),
                  fontSize: 14,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            'Acumulado hoy según tu producción y tarifa de ficha técnica',
            style: GoogleFonts.plusJakartaSans(color: Colors.white70, fontSize: 11),
          ),
          const SizedBox(height: 14),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: Colors.black.withOpacity(0.25),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                Column(
                  children: [
                    const Text('Salario Base Día', style: TextStyle(color: Colors.white60, fontSize: 10)),
                    const SizedBox(height: 2),
                    Text('\$ ${_userDailySalary.toStringAsFixed(2)}', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13)),
                  ],
                ),
                Container(width: 1, height: 20, color: Colors.white24),
                Column(
                  children: [
                    const Text('Bultos/Rollos', style: TextStyle(color: Colors.white60, fontSize: 10)),
                    const SizedBox(height: 2),
                    Text(totalUnits.toStringAsFixed(0), style: const TextStyle(color: Color(0xFF38BDF8), fontWeight: FontWeight.bold, fontSize: 13)),
                  ],
                ),
                Container(width: 1, height: 20, color: Colors.white24),
                Column(
                  children: [
                    const Text('Jornada', style: TextStyle(color: Colors.white60, fontSize: 10)),
                    const SizedBox(height: 2),
                    Text('$_userWorkDays días', style: const TextStyle(color: Colors.amberAccent, fontWeight: FontWeight.bold, fontSize: 13)),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildGoalProgressCard() {
    double totalUnits = 0.0;
    for (var p in _shiftProductions) {
      totalUnits += (p['quantity'] as num?)?.toDouble() ?? 0.0;
    }

    double targetGoal = 5.0;
    if (_selectedProduct != null && _selectedProduct!['target_units_per_shift'] != null) {
      targetGoal = (double.tryParse(_selectedProduct!['target_units_per_shift'].toString()) ?? 5.0);
    }
    if (targetGoal <= 0) targetGoal = 5.0;
    final progressPercent = (totalUnits / targetGoal).clamp(0.0, 1.0);
    final percentFormatted = (totalUnits / targetGoal * 100).toStringAsFixed(0);

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF1C2541),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  const Icon(Icons.flag_circle_outlined, color: Color(0xFF38BDF8), size: 20),
                  const SizedBox(width: 8),
                  Text('Meta de Producción del Turno',
                      style: GoogleFonts.plusJakartaSans(color: Colors.white, fontSize: 13, fontWeight: FontWeight.bold)),
                ],
              ),
              Text('$percentFormatted%',
                  style: GoogleFonts.plusJakartaSans(
                    color: totalUnits >= targetGoal ? const Color(0xFF10B981) : const Color(0xFF38BDF8),
                    fontWeight: FontWeight.bold,
                    fontSize: 14,
                  )),
            ],
          ),
          const SizedBox(height: 10),
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: LinearProgressIndicator(
              value: progressPercent,
              minHeight: 10,
              backgroundColor: const Color(0xFF0F172A),
              valueColor: AlwaysStoppedAnimation<Color>(
                totalUnits >= targetGoal ? const Color(0xFF10B981) : const Color(0xFF38BDF8),
              ),
            ),
          ),
          const SizedBox(height: 8),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                '${totalUnits.toStringAsFixed(0)} de ${targetGoal.toStringAsFixed(0)} unidades meta',
                style: const TextStyle(color: Colors.white70, fontSize: 11),
              ),
              Text(
                totalUnits >= targetGoal
                    ? '🎯 ¡Meta Alcanzada!'
                    : 'Faltan ${(targetGoal - totalUnits).clamp(0, 9999).toStringAsFixed(0)} unid.',
                style: TextStyle(
                  color: totalUnits >= targetGoal ? const Color(0xFF10B981) : Colors.amberAccent,
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildQuickActions() {
    return Row(
      children: [
        Expanded(
          child: ElevatedButton.icon(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF0284C7),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(vertical: 12),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            icon: const Icon(Icons.scale_rounded, size: 18),
            label: const Text('Cargar Pesaje', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
            onPressed: () => setState(() => _currentTabIndex = 1),
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: OutlinedButton.icon(
            style: OutlinedButton.styleFrom(
              foregroundColor: const Color(0xFF38BDF8),
              side: const BorderSide(color: Color(0xFF38BDF8)),
              padding: const EdgeInsets.symmetric(vertical: 12),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            icon: const Icon(Icons.format_list_bulleted_rounded, size: 18),
            label: const Text('Ver Registros', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
            onPressed: () => setState(() => _currentTabIndex = 2),
          ),
        ),
      ],
    );
  }

  Widget _buildOpenShiftNotice() {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: const Color(0xFF1C2541),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white10),
      ),
      child: Column(
        children: [
          const Icon(Icons.lock_clock_rounded, size: 48, color: Colors.amberAccent),
          const SizedBox(height: 12),
          Text(
            'Turno Cerrado',
            style: GoogleFonts.plusJakartaSans(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 6),
          const Text(
            'Para registrar pesajes de producción, debes abrir un turno primero.',
            textAlign: TextAlign.center,
            style: TextStyle(color: Colors.white70, fontSize: 12),
          ),
          const SizedBox(height: 16),
          ElevatedButton.icon(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF10B981),
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            icon: const Icon(Icons.play_arrow_rounded),
            label: const Text('Ir a Abrir Turno', style: TextStyle(fontWeight: FontWeight.bold)),
            onPressed: () => setState(() => _currentTabIndex = 0),
          ),
        ],
      ),
    );
  }

  Widget _buildPayrollTab() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _buildEarningsHighlightCard(),
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: const Color(0xFF1C2541),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: Colors.white10),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Icon(Icons.badge_outlined, color: Color(0xFF38BDF8), size: 20),
                    const SizedBox(width: 8),
                    Text('Ficha Salarial de Operario',
                        style: GoogleFonts.plusJakartaSans(color: Colors.white, fontSize: 14, fontWeight: FontWeight.bold)),
                  ],
                ),
                const SizedBox(height: 12),
                _payrollInfoRow('Operador:', widget.userName),
                _payrollInfoRow('Salario Semanal:', '\$ ${_userWeeklySalary.toStringAsFixed(2)} USD'),
                _payrollInfoRow('Jornada Laboral:', '$_userWorkDays días / semana'),
                _payrollInfoRow('Salario Diario Base:', '\$ ${_userDailySalary.toStringAsFixed(2)} USD / día'),
                _payrollInfoRow('Pago de Fracciones:', _payPartialPackages ? 'Habilitado (por millar/rollo)' : 'Retenido hasta completar bulto'),
              ],
            ),
          ),
          const SizedBox(height: 16),
          Text('Tarifas por Destajo según Ficha Técnica',
              style: GoogleFonts.plusJakartaSans(color: Colors.white70, fontSize: 13, fontWeight: FontWeight.bold)),
          const SizedBox(height: 10),
          if (_cachedProducts.isEmpty)
            const Center(child: Text('No hay productos en catálogo', style: TextStyle(color: Colors.white38)))
          else
            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: _cachedProducts.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (ctx, idx) {
                final p = _cachedProducts[idx];
                final tariffs = _calculateLaborTariff(p);
                final pkgTariff = tariffs['package_tariff'] ?? 0.0;
                final targetUnits = tariffs['target_units'] ?? 5.0;
                final fracTariff = tariffs['fraction_tariff'] ?? 0.0;
                final labels = _getProductUnitLabels(p);
                final isMultiMillar = labels['unit_type'] == 'bulto_multi_millar';

                return Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: const Color(0xFF1C2541),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.white10),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: (labels['color'] as Color).withOpacity(0.12),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Icon(labels['icon'] as IconData, color: labels['color'] as Color, size: 18),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(p['name'] ?? '',
                                style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis),
                            const SizedBox(height: 2),
                            Text('Meta: ${targetUnits.toStringAsFixed(0)} ${labels['meta_suffix']} • SKU: ${p['sku'] ?? 'N/A'}',
                                style: const TextStyle(color: Colors.white54, fontSize: 10)),
                          ],
                        ),
                      ),
                      const SizedBox(width: 8),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text('\$ ${pkgTariff.toStringAsFixed(2)}',
                              style: const TextStyle(color: Color(0xFF10B981), fontWeight: FontWeight.bold, fontSize: 14)),
                          Text(
                            labels['primary_label'] as String,
                            style: const TextStyle(color: Colors.white70, fontSize: 10, fontWeight: FontWeight.w600),
                          ),
                          if (isMultiMillar) ...[
                            const SizedBox(height: 2),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                              decoration: BoxDecoration(
                                color: const Color(0xFF38BDF8).withOpacity(0.15),
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: Text(
                                '\$ ${fracTariff.toStringAsFixed(3)} / mil',
                                style: const TextStyle(color: Color(0xFF38BDF8), fontSize: 9, fontWeight: FontWeight.bold),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                );
              },
            ),
        ],
      ),
    );
  }

  Widget _payrollInfoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(color: Colors.white60, fontSize: 12)),
          Text(value, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12)),
        ],
      ),
    );
  }

  Widget _buildShiftCard() {
    if (_activeShift == null) {
      return Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: const Color(0xFF1C2541),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.white10),
          boxShadow: const [
            BoxShadow(color: Color(0x33000000), blurRadius: 10, offset: Offset(0, 4)),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.timer_outlined, color: Color(0xFF38BDF8), size: 24),
                const SizedBox(width: 8),
                Text('Apertura de Turno',
                    style: GoogleFonts.plusJakartaSans(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
              ],
            ),
            const SizedBox(height: 8),
            Text('Seleccione el tipo de turno y la máquina asignada:',
                style: GoogleFonts.plusJakartaSans(color: Colors.white70, fontSize: 12)),
            const SizedBox(height: 14),
            Row(
              children: [
                Expanded(
                  child: ChoiceChip(
                    label: const Center(child: Text('☀️ Diurno')),
                    selected: _selectedShiftType == 'diurno',
                    onSelected: (val) => setState(() => _selectedShiftType = 'diurno'),
                    selectedColor: const Color(0xFF0284C7),
                    backgroundColor: const Color(0xFF0B132B),
                    labelStyle: TextStyle(
                      color: _selectedShiftType == 'diurno' ? Colors.white : Colors.white60,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ChoiceChip(
                    label: const Center(child: Text('🌙 Nocturno')),
                    selected: _selectedShiftType == 'nocturno',
                    onSelected: (val) => setState(() => _selectedShiftType = 'nocturno'),
                    selectedColor: const Color(0xFF7C3AED),
                    backgroundColor: const Color(0xFF0B132B),
                    labelStyle: TextStyle(
                      color: _selectedShiftType == 'nocturno' ? Colors.white : Colors.white60,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            Text('🏭 Máquina / Línea de Producción:',
                style: GoogleFonts.plusJakartaSans(color: Colors.white70, fontSize: 12, fontWeight: FontWeight.bold)),
            const SizedBox(height: 6),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
              decoration: BoxDecoration(
                color: const Color(0xFF0B132B),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.white24),
              ),
              child: DropdownButtonHideUnderline(
                child: DropdownButton<int>(
                  value: _selectedMachineId,
                  isExpanded: true,
                  dropdownColor: const Color(0xFF1C2541),
                  icon: const Icon(Icons.keyboard_arrow_down, color: Color(0xFF38BDF8)),
                  hint: const Text('Seleccionar máquina...', style: TextStyle(color: Colors.white38, fontSize: 13)),
                  items: _cachedMachines.map((m) {
                    final code = m['code'] ?? '';
                    final name = m['name'] ?? '';
                    return DropdownMenuItem<int>(
                      value: m['id'] as int,
                      child: Text(
                        '[$code] $name',
                        style: const TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w600),
                        overflow: TextOverflow.ellipsis,
                      ),
                    );
                  }).toList(),
                  onChanged: (val) => setState(() => _selectedMachineId = val),
                ),
              ),
            ),
            const SizedBox(height: 18),
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton.icon(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF10B981),
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                icon: const Icon(Icons.play_arrow_rounded),
                label: Text('INICIAR TURNO',
                    style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.bold, fontSize: 14)),
                onPressed: _openShift,
              ),
            ),
          ],
        ),
      );
    }

    final isDiurno = _activeShift!['shift_type'] == 'diurno';
    final startTime = DateTime.parse(_activeShift!['start_time']);
    final formattedStart = DateFormat('hh:mm a').format(startTime);
    final shiftMachineId = _activeShift!['machine_id'] as int?;
    final assignedMachine = _cachedMachines.firstWhere(
      (m) => m['id'] == shiftMachineId,
      orElse: () => <String, dynamic>{},
    );

    double totalKg = 0;
    double totalUnits = 0;
    for (var p in _shiftProductions) {
      totalUnits += (p['quantity'] as num).toDouble();
      totalKg += (p['weight'] as num).toDouble();
    }

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: isDiurno ? [const Color(0xFF0369A1), const Color(0xFF0C4A6E)] : [const Color(0xFF5B21B6), const Color(0xFF3B0764)],
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: const [
          BoxShadow(color: Color(0x4D000000), blurRadius: 10, offset: Offset(0, 4)),
        ],
      ),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Icon(isDiurno ? Icons.wb_sunny_rounded : Icons.nightlight_round, color: Colors.amberAccent, size: 22),
                  const SizedBox(width: 8),
                  Text('Turno ${isDiurno ? 'Diurno' : 'Nocturno'} Activo',
                      style: GoogleFonts.plusJakartaSans(color: Colors.white, fontSize: 15, fontWeight: FontWeight.bold)),
                ],
              ),
              OutlinedButton.icon(
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.white,
                  side: const BorderSide(color: Colors.white38),
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  visualDensity: VisualDensity.compact,
                ),
                icon: const Icon(Icons.stop_circle_outlined, size: 14, color: Color(0xFFFCA5A5)),
                label: const Text('Cerrar', style: TextStyle(fontSize: 11)),
                onPressed: _showCloseShiftDialog,
              ),
            ],
          ),
          if (assignedMachine.isNotEmpty) ...[
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: Colors.black26,
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: Colors.white24),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.precision_manufacturing_rounded, size: 14, color: Color(0xFF38BDF8)),
                  const SizedBox(width: 6),
                  Text(
                    'Máquina: [${assignedMachine['code']}] ${assignedMachine['name']}',
                    style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                  ),
                ],
              ),
            ),
          ],
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              Column(
                children: [
                  Text('Inicio', style: GoogleFonts.plusJakartaSans(color: Colors.white60, fontSize: 11)),
                  Text(formattedStart,
                      style: GoogleFonts.plusJakartaSans(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w600)),
                ],
              ),
              Container(width: 1, height: 28, color: Colors.white24),
              Column(
                children: [
                  Text('Unidades/Rollos', style: GoogleFonts.plusJakartaSans(color: Colors.white60, fontSize: 11)),
                  Text(totalUnits.toStringAsFixed(0),
                      style: GoogleFonts.plusJakartaSans(color: Colors.amberAccent, fontSize: 17, fontWeight: FontWeight.bold)),
                ],
              ),
              Container(width: 1, height: 28, color: Colors.white24),
              Column(
                children: [
                  Text('Peso Total', style: GoogleFonts.plusJakartaSans(color: Colors.white60, fontSize: 11)),
                  Text('${totalKg.toStringAsFixed(2)} Kg',
                      style: GoogleFonts.plusJakartaSans(color: const Color(0xFF6EE7B7), fontSize: 17, fontWeight: FontWeight.bold)),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildProductionFormCard() {
    final prodLabels = _getProductUnitLabels(_selectedProduct);
    final isRoll = _isRollProduct(_selectedProduct);

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: const Color(0xFF1C2541),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  const Icon(Icons.add_box_rounded, color: Color(0xFF38BDF8), size: 20),
                  const SizedBox(width: 8),
                  Text('Registrar Producción',
                      style: GoogleFonts.plusJakartaSans(color: Colors.white, fontSize: 15, fontWeight: FontWeight.bold)),
                ],
              ),
              IconButton(
                icon: const Icon(Icons.qr_code_scanner, color: Color(0xFF38BDF8), size: 22),
                tooltip: 'Escanear Código / QR',
                onPressed: _scanBarcode,
              ),
            ],
          ),
          const SizedBox(height: 10),

          // Selector Rápido de Máquina Activa (Cambio Dinámico en Turno)
          if (_cachedMachines.isNotEmpty) ...[
            Container(
              margin: const EdgeInsets.only(bottom: 10),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
              decoration: BoxDecoration(
                color: const Color(0xFF0B132B),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: Colors.white12),
              ),
              child: Row(
                children: [
                  const Icon(Icons.precision_manufacturing_rounded, color: Color(0xFF38BDF8), size: 18),
                  const SizedBox(width: 8),
                  const Text('Máquina:', style: TextStyle(color: Colors.white70, fontSize: 12, fontWeight: FontWeight.bold)),
                  const SizedBox(width: 8),
                  Expanded(
                    child: DropdownButtonHideUnderline(
                      child: DropdownButton<int>(
                        value: _selectedMachineId,
                        dropdownColor: const Color(0xFF1C2541),
                        isExpanded: true,
                        style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                        items: _cachedMachines.map((m) {
                          return DropdownMenuItem<int>(
                            value: m['id'] as int?,
                            child: Text(
                              '[${m['code']}] ${m['name']}',
                              overflow: TextOverflow.ellipsis,
                            ),
                          );
                        }).toList(),
                        onChanged: (val) {
                          if (val != null) {
                            setState(() => _selectedMachineId = val);
                          }
                        },
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],

          // Selector de Producto
          InkWell(
            onTap: () async {
              final picked = await _showProductSearchDialog(initial: _selectedProduct);
              if (picked != null) {
                setState(() {
                  _selectedProduct = picked;
                  _variableRolls.clear();
                  _isIndividualRollMode = _isRollProduct(picked);
                });
              }
            },
            borderRadius: BorderRadius.circular(12),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              decoration: BoxDecoration(
                color: const Color(0xFF0B132B),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: (prodLabels['color'] as Color).withOpacity(0.5)),
              ),
              child: Row(
                children: [
                  Icon(
                    prodLabels['icon'] as IconData,
                    color: prodLabels['color'] as Color,
                    size: 22,
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _selectedProduct != null ? _selectedProduct!['name'] : 'Seleccionar tipo de bolsa o bobina...',
                          style: const TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.bold),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                        if (_selectedProduct != null)
                          Text(
                            'SKU: ${_selectedProduct!['sku'] ?? 'N/A'} • ${prodLabels['meta_suffix']}',
                            style: const TextStyle(color: Colors.white54, fontSize: 11),
                          ),
                      ],
                    ),
                  ),
                  const Icon(Icons.search, color: Color(0xFF38BDF8), size: 20),
                ],
              ),
            ),
          ),

          // Si es Bobina / Presentación Variable: Ofrecer Carga de Rollos Individuales
          if (isRoll) ...[
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFF0F172A),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.amber.withOpacity(0.3)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Row(
                        children: [
                          Icon(Icons.layers_outlined, color: Colors.amberAccent, size: 18),
                          SizedBox(width: 6),
                          Text(
                            'Carga por Rollo / Bobina Individual',
                            style: TextStyle(color: Colors.amberAccent, fontWeight: FontWeight.bold, fontSize: 12),
                          ),
                        ],
                      ),
                      Switch(
                        value: _isIndividualRollMode,
                        activeColor: Colors.amberAccent,
                        onChanged: (val) => setState(() => _isIndividualRollMode = val),
                      ),
                    ],
                  ),
                  if (_isIndividualRollMode) ...[
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Expanded(
                          flex: 3,
                          child: TextField(
                            controller: _rollWeightCtrl,
                            keyboardType: const TextInputType.numberWithOptions(decimal: true),
                            style: const TextStyle(color: Colors.amberAccent, fontWeight: FontWeight.bold),
                            decoration: InputDecoration(
                              labelText: 'Peso Rollo (Kg)',
                              labelStyle: const TextStyle(color: Colors.white60, fontSize: 11),
                              suffixText: 'Kg',
                              filled: true,
                              fillColor: const Color(0xFF1C2541),
                              isDense: true,
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          flex: 2,
                          child: TextField(
                            controller: _rollColorCtrl,
                            style: const TextStyle(color: Colors.white),
                            decoration: InputDecoration(
                              labelText: 'Color (Opc.)',
                              labelStyle: const TextStyle(color: Colors.white60, fontSize: 11),
                              filled: true,
                              fillColor: const Color(0xFF1C2541),
                              isDense: true,
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        ElevatedButton(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF0284C7),
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                          ),
                          onPressed: _addVariableRoll,
                          child: const Icon(Icons.add, size: 18),
                        ),
                      ],
                    ),
                    if (_variableRolls.isNotEmpty) ...[
                      const SizedBox(height: 10),
                      Container(
                        constraints: const BoxConstraints(maxHeight: 110),
                        child: ListView.builder(
                          shrinkWrap: true,
                          itemCount: _variableRolls.length,
                          itemBuilder: (ctx, idx) {
                            final r = _variableRolls[idx];
                            return Container(
                              margin: const EdgeInsets.only(bottom: 4),
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(
                                color: const Color(0xFF1C2541),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    'Rollo #${idx + 1}: ${r['weight']} Kg ${r['color'].toString().isNotEmpty ? '(${r['color']})' : ''}',
                                    style: const TextStyle(color: Colors.white, fontSize: 12),
                                  ),
                                  IconButton(
                                    icon: const Icon(Icons.delete_outline, color: Colors.redAccent, size: 16),
                                    onPressed: () {
                                      setState(() {
                                        _variableRolls.removeAt(idx);
                                        _quantityController.text = _variableRolls.length.toString();
                                        final totalWeight = _variableRolls.fold(0.0, (sum, roll) => sum + (roll['weight'] as double));
                                        _weightController.text = totalWeight.toStringAsFixed(2);
                                      });
                                    },
                                  ),
                                ],
                              ),
                            );
                          },
                        ),
                      ),
                      Container(
                        margin: const EdgeInsets.only(top: 6),
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: const Color(0xFF0284C7).withOpacity(0.2),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text('Rollos: ${_variableRolls.length}', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12)),
                            Text('Peso Total: ${_weightController.text} Kg', style: const TextStyle(color: Colors.amberAccent, fontWeight: FontWeight.bold, fontSize: 12)),
                          ],
                        ),
                      ),
                    ],
                  ],
                ],
              ),
            ),
          ],

          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                flex: 2,
                child: TextField(
                  controller: _quantityController,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
                  decoration: InputDecoration(
                    labelText: _getProductUnitLabel(_selectedProduct),
                    labelStyle: const TextStyle(color: Colors.white60, fontSize: 12),
                    filled: true,
                    fillColor: const Color(0xFF0B132B),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                flex: 3,
                child: TextField(
                  controller: _weightController,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  style: const TextStyle(color: Colors.amberAccent, fontSize: 18, fontWeight: FontWeight.bold),
                  decoration: InputDecoration(
                    labelText: 'Peso en Báscula (Kg)',
                    labelStyle: const TextStyle(color: Colors.white60),
                    suffixText: 'Kg',
                    suffixStyle: const TextStyle(color: Colors.amberAccent, fontWeight: FontWeight.bold),
                    filled: true,
                    fillColor: const Color(0xFF0B132B),
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          SizedBox(
            width: double.infinity,
            height: 48,
            child: ElevatedButton.icon(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF0284C7),
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              icon: const Icon(Icons.check_circle_outline),
              label: Text(_getButtonLabel(_selectedProduct),
                  style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.bold, fontSize: 14)),
              onPressed: _recordProduction,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildShiftHistoryList() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Registros de este Turno (${_shiftProductions.length})',
            style: GoogleFonts.plusJakartaSans(color: Colors.white70, fontSize: 13, fontWeight: FontWeight.w600)),
        const SizedBox(height: 10),
        if (_shiftProductions.isEmpty)
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: const Color(0x801C2541),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Center(
              child: Text('No hay registros cargados en este turno aún.',
                  style: GoogleFonts.plusJakartaSans(color: Colors.white38, fontSize: 12)),
            ),
          )
        else
          ListView.separated(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: _shiftProductions.length,
            separatorBuilder: (context, index) => const SizedBox(height: 8),
            itemBuilder: (ctx, idx) {
              final item = _shiftProductions[idx];
              final isSynced = item['is_synced'] == 1;
              final recTime = DateTime.parse(item['recorded_at']);
              final formattedTime = DateFormat('hh:mm a').format(recTime);

              final matchedProd = _cachedProducts.firstWhere(
                (cp) => cp['id'] == item['product_id'] || (cp['name'] == item['product_name']),
                orElse: () => <String, dynamic>{'name': item['product_name'] ?? ''},
              );
              final histLabels = _getProductUnitLabels(matchedProd);
              final isRoll = histLabels['unit_type'] == 'bobina' || histLabels['unit_type'] == 'rollo';
              final qtyNum = (item['quantity'] as num?)?.toDouble() ?? 1.0;
              final unitLabel = (qtyNum == 1.0) ? histLabels['unit_name_singular'] : histLabels['unit_name_plural'];

              return Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFF1C2541),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.white10),
                ),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: (histLabels['color'] as Color).withOpacity(0.15),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Icon(
                        histLabels['icon'] as IconData,
                        color: histLabels['color'] as Color,
                        size: 20,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            item['product_name'] ?? 'Producto',
                            style: GoogleFonts.plusJakartaSans(
                              color: Colors.white,
                              fontSize: 13,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                          const SizedBox(height: 3),
                          Row(
                            children: [
                              Text(
                                '${item['quantity']} $unitLabel',
                                style: const TextStyle(color: Colors.white70, fontSize: 12, fontWeight: FontWeight.bold),
                              ),
                              const SizedBox(width: 8),
                              const Text('•', style: TextStyle(color: Colors.white38)),
                              const SizedBox(width: 8),
                              Text(
                                formattedTime,
                                style: const TextStyle(color: Colors.white38, fontSize: 11),
                              ),
                            ],
                          ),
                          if (isRoll && item['metadata'] != null) ...[
                            Builder(
                              builder: (_) {
                                List<dynamic> rolls = [];
                                try {
                                  rolls = item['metadata'] is String ? json.decode(item['metadata']) : item['metadata'];
                                } catch (_) {}
                                if (rolls.isEmpty) return const SizedBox.shrink();
                                return Padding(
                                  padding: const EdgeInsets.only(top: 4.0),
                                  child: Wrap(
                                    spacing: 4,
                                    runSpacing: 2,
                                    children: rolls.asMap().entries.map((e) {
                                      final r = e.value;
                                      return Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
                                        decoration: BoxDecoration(
                                          color: Colors.amber.withOpacity(0.15),
                                          borderRadius: BorderRadius.circular(4),
                                          border: Border.all(color: Colors.amber.withOpacity(0.3)),
                                        ),
                                        child: Text(
                                          '#${e.key + 1}: ${r['weight']} Kg',
                                          style: const TextStyle(color: Colors.amberAccent, fontSize: 10, fontWeight: FontWeight.bold),
                                        ),
                                      );
                                    }).toList(),
                                  ),
                                );
                              },
                            ),
                          ],
                        ],
                      ),
                    ),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Builder(
                          builder: (_) {
                            var w = (item['weight'] as num?)?.toDouble() ?? 0.0;
                            final name = (item['product_name'] ?? '').toString().toUpperCase();
                            final isRoll = name.contains('BOBINA') || name.contains('ROLLO');
                            if (w >= 500 && !isRoll) {
                              w = w / 1000.0;
                            }
                            return Text(
                              '${w.toStringAsFixed(2)} Kg',
                              style: GoogleFonts.plusJakartaSans(
                                color: const Color(0xFFF59E0B),
                                fontSize: 15,
                                fontWeight: FontWeight.bold,
                              ),
                            );
                          },
                        ),
                        const SizedBox(height: 2),
                        Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(
                              isSynced ? Icons.cloud_done : Icons.cloud_upload_outlined,
                              size: 12,
                              color: isSynced ? const Color(0xFF10B981) : const Color(0xFFF59E0B),
                            ),
                            const SizedBox(width: 3),
                            Text(
                              isSynced ? 'Nube' : 'Local',
                              style: TextStyle(
                                fontSize: 10,
                                color: isSynced ? const Color(0xFF10B981) : const Color(0xFFF59E0B),
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                    const SizedBox(width: 4),
                    PopupMenuButton<String>(
                      icon: const Icon(Icons.more_vert, color: Colors.white54, size: 20),
                      color: const Color(0xFF0F172A),
                      onSelected: (val) {
                        if (val == 'edit') {
                          _showEditProductionDialog(item);
                        } else if (val == 'delete') {
                          _confirmDeleteProduction(item);
                        }
                      },
                      itemBuilder: (context) => [
                        const PopupMenuItem(
                          value: 'edit',
                          child: Row(
                            children: [
                              Icon(Icons.edit, color: Color(0xFF38BDF8), size: 16),
                              SizedBox(width: 8),
                              Text('Editar', style: TextStyle(color: Colors.white)),
                            ],
                          ),
                        ),
                        const PopupMenuItem(
                          value: 'delete',
                          child: Row(
                            children: [
                              Icon(Icons.delete, color: Colors.redAccent, size: 16),
                              SizedBox(width: 8),
                              Text('Eliminar', style: TextStyle(color: Colors.redAccent)),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              );
            },
          ),
      ],
    );
  }
}
