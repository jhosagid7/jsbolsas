import 'dart:convert';
import 'package:http/http.dart' as http;
import 'local_db.dart';

class SyncResult {
  final bool success;
  final int syncedShifts;
  final int syncedProductions;
  final String? errorMessage;

  SyncResult({
    required this.success,
    this.syncedShifts = 0,
    this.syncedProductions = 0,
    this.errorMessage,
  });
}

class SyncService {
  static final SyncService instance = SyncService._init();
  SyncService._init();

  final LocalDatabaseService _db = LocalDatabaseService.instance;

  /// Fetch product catalog from server and cache locally
  Future<bool> refreshProductCatalog(String baseUrl, String token) async {
    try {
      final cleanUrl = baseUrl.replaceAll(RegExp(r'/+$'), '');
      final response = await http.get(
        Uri.parse('$cleanUrl/api/bag-factory/products'),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 8));

      if (response.statusCode == 200) {
        final List<dynamic> products = json.decode(response.body);
        await _db.saveCachedProducts(products);
        return true;
      }
      return false;
    } catch (_) {
      return false; // Offline or timeout
    }
  }

  /// Fetch machines catalog from server and cache locally
  Future<bool> refreshMachinesCatalog(String baseUrl, String token) async {
    try {
      final cleanUrl = baseUrl.replaceAll(RegExp(r'/+$'), '');
      final response = await http.get(
        Uri.parse('$cleanUrl/api/bag-factory/machines'),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 8));

      if (response.statusCode == 200) {
        final List<dynamic> machines = json.decode(response.body);
        await _db.saveCachedMachines(machines);
        return true;
      }
      return false;
    } catch (_) {
      return false; // Offline or timeout
    }
  }

  /// Synchronize all pending local shifts and productions with VPS
  Future<SyncResult> syncPendingData(String baseUrl, String token) async {
    final cleanUrl = baseUrl.replaceAll(RegExp(r'/+$'), '');
    int syncedShifts = 0;
    int syncedProductions = 0;

    try {
      // 1. Sync Pending Shifts
      final pendingShifts = await _db.getPendingSyncShifts();
      for (var s in pendingShifts) {
        final syncId = s['sync_id'] as String;
        final status = s['status'] as String;
        int? serverId = s['server_id'] as int?;

        if (status == 'open' || serverId == null) {
          final res = await http.post(
            Uri.parse('$cleanUrl/api/bag-factory/shifts/open'),
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'Authorization': 'Bearer $token',
            },
            body: json.encode({
              'shift_type': s['shift_type'],
              'machine_id': s['machine_id'],
              'start_time': s['start_time'],
              'sync_id': syncId,
              'notes': s['notes'],
            }),
          ).timeout(const Duration(seconds: 8));

          if (res.statusCode == 200 || res.statusCode == 422) {
            final data = json.decode(res.body);
            serverId = data['data']?['id'] as int?;
            await _db.markShiftSynced(syncId, serverId);
            syncedShifts++;
          }
        }

        if (status == 'closed') {
          final res = await http.post(
            Uri.parse('$cleanUrl/api/bag-factory/shifts/close'),
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              'Authorization': 'Bearer $token',
            },
            body: json.encode({
              'shift_id': serverId,
              'sync_id': syncId,
              'end_time': s['end_time'] ?? DateTime.now().toIso8601String(),
              'notes': s['notes'],
            }),
          ).timeout(const Duration(seconds: 8));

          if (res.statusCode == 200) {
            await _db.markShiftSynced(syncId, serverId);
            syncedShifts++;
          }
        }
      }

      // 2. Sync Pending Productions
      final pendingProds = await _db.getPendingSyncProductions();
      if (pendingProds.isNotEmpty) {
        final activeShift = await _db.getActiveLocalShift();
        final serverShiftId = activeShift?['server_id'] as int?;
        final shiftSyncId = activeShift?['sync_id'] ?? pendingProds.first['shift_sync_id'];

        final payload = {
          'shift_id': serverShiftId,
          'shift_sync_id': shiftSyncId,
          'productions': pendingProds.map((p) => {
            'sync_id': p['sync_id'],
            'product_id': p['product_id'],
            'machine_id': p['machine_id'],
            'quantity': p['quantity'],
            'weight': p['weight'],
            'recorded_at': p['recorded_at'],
            'metadata': (p['metadata'] != null && p['metadata'].toString().isNotEmpty)
                ? (p['metadata'] is String ? json.decode(p['metadata']) : p['metadata'])
                : null,
          }).toList(),
        };

        final res = await http.post(
          Uri.parse('$cleanUrl/api/bag-factory/productions/sync'),
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'Authorization': 'Bearer $token',
          },
          body: json.encode(payload),
        ).timeout(const Duration(seconds: 10));

        if (res.statusCode == 200) {
          for (var p in pendingProds) {
            await _db.markProductionSynced(p['sync_id'] as String, null);
            syncedProductions++;
          }
        }
      }

      return SyncResult(
        success: true,
        syncedShifts: syncedShifts,
        syncedProductions: syncedProductions,
      );

    } catch (e) {
      return SyncResult(
        success: false,
        syncedShifts: syncedShifts,
        syncedProductions: syncedProductions,
        errorMessage: e.toString(),
      );
    }
  }
}