import 'package:sqflite/sqflite.dart';
import 'package:path/path.dart';

class LocalDatabaseService {
  static final LocalDatabaseService instance = LocalDatabaseService._init();
  static Database? _database;

  LocalDatabaseService._init();

  Future<Database> get database async {
    if (_database != null) return _database!;
    _database = await _initDB('bag_factory_local.db');
    return _database!;
  }

  Future<Database> _initDB(String filePath) async {
    final dbPath = await getDatabasesPath();
    final path = join(dbPath, filePath);

    return await openDatabase(
      path,
      version: 5,
      onCreate: _createDB,
      onUpgrade: _onUpgrade,
      onOpen: (db) async {
        try {
          await db.execute('''
            UPDATE local_productions 
            SET weight = ROUND(weight / 1000.0, 4) 
            WHERE weight >= 500 AND UPPER(product_name) NOT LIKE '%BOBINA%' AND UPPER(product_name) NOT LIKE '%ROLLO%';
          ''');
        } catch (_) {}
      },
    );
  }

  Future _createDB(Database db, int version) async {
    // 1. Cached Products Catalog (for offline autocomplete)
    await db.execute('''
      CREATE TABLE cached_products (
        id INTEGER PRIMARY KEY,
        name TEXT NOT NULL,
        sku TEXT,
        cost REAL,
        price REAL,
        sale_unit TEXT,
        is_variable_quantity INTEGER DEFAULT 0,
        target_units_per_shift REAL DEFAULT 5,
        millar_per_bulto REAL DEFAULT 1,
        unit_weight_kg REAL DEFAULT 0
      )
    ''');

    // 2. Cached Machines Catalog (for offline selection)
    await db.execute('''
      CREATE TABLE cached_machines (
        id INTEGER PRIMARY KEY,
        code TEXT NOT NULL,
        name TEXT NOT NULL,
        type TEXT NOT NULL,
        status TEXT
      )
    ''');

    // 3. Local Shifts
    await db.execute('''
      CREATE TABLE local_shifts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        server_id INTEGER,
        machine_id INTEGER,
        shift_type TEXT NOT NULL,
        start_time TEXT NOT NULL,
        end_time TEXT,
        status TEXT NOT NULL,
        total_packages REAL DEFAULT 0,
        total_weight REAL DEFAULT 0,
        notes TEXT,
        sync_id TEXT UNIQUE NOT NULL,
        is_synced INTEGER DEFAULT 0
      )
    ''');

    // 4. Local Productions
    await db.execute('''
      CREATE TABLE local_productions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        server_id INTEGER,
        shift_sync_id TEXT NOT NULL,
        product_id INTEGER NOT NULL,
        machine_id INTEGER,
        product_name TEXT NOT NULL,
        quantity REAL NOT NULL,
        weight REAL NOT NULL,
        recorded_at TEXT NOT NULL,
        status TEXT DEFAULT 'pending_review',
        sync_id TEXT UNIQUE NOT NULL,
        metadata TEXT,
        is_synced INTEGER DEFAULT 0
      )
    ''');
  }

  Future _onUpgrade(Database db, int oldVersion, int newVersion) async {
    if (oldVersion < 2) {
      try {
        await db.execute('ALTER TABLE cached_products ADD COLUMN is_variable_quantity INTEGER DEFAULT 0;');
      } catch (_) {}
      try {
        await db.execute('ALTER TABLE local_productions ADD COLUMN metadata TEXT;');
      } catch (_) {}
    }
    if (oldVersion < 3) {
      try {
        await db.execute('''
          CREATE TABLE IF NOT EXISTS cached_machines (
            id INTEGER PRIMARY KEY,
            code TEXT NOT NULL,
            name TEXT NOT NULL,
            type TEXT NOT NULL,
            status TEXT
          )
        ''');
      } catch (_) {}
      try {
        await db.execute('ALTER TABLE local_shifts ADD COLUMN machine_id INTEGER;');
      } catch (_) {}
    }
    if (oldVersion < 4) {
      try {
        await db.execute('ALTER TABLE local_productions ADD COLUMN machine_id INTEGER;');
      } catch (_) {}
    }
    if (oldVersion < 5) {
      try {
        await db.execute('ALTER TABLE cached_products ADD COLUMN target_units_per_shift REAL DEFAULT 5;');
      } catch (_) {}
      try {
        await db.execute('ALTER TABLE cached_products ADD COLUMN millar_per_bulto REAL DEFAULT 1;');
      } catch (_) {}
      try {
        await db.execute('ALTER TABLE cached_products ADD COLUMN unit_weight_kg REAL DEFAULT 0;');
      } catch (_) {}
    }
  }

  // ==================== PRODUCTS ====================
  Future<void> saveCachedProducts(List<dynamic> products) async {
    final db = await instance.database;
    final batch = db.batch();
    batch.delete('cached_products');
    for (var p in products) {
      final isVar = (p['is_variable_quantity'] == true || p['is_variable_quantity'] == 1 || p['is_variable_quantity'] == '1') ? 1 : 0;
      final targetUnits = (p['target_units_per_shift'] != null) ? double.tryParse(p['target_units_per_shift'].toString()) ?? 5.0 : 5.0;
      final millarPerBulto = (p['millar_per_bulto'] != null) ? double.tryParse(p['millar_per_bulto'].toString()) ?? 1.0 : 1.0;
      final unitWeight = (p['unit_weight_kg'] != null) ? double.tryParse(p['unit_weight_kg'].toString()) ?? 0.0 : 0.0;

      batch.insert('cached_products', {
        'id': p['id'],
        'name': p['name'] ?? '',
        'sku': p['sku'] ?? '',
        'cost': (p['cost'] != null) ? double.tryParse(p['cost'].toString()) ?? 0.0 : 0.0,
        'price': (p['price'] != null) ? double.tryParse(p['price'].toString()) ?? 0.0 : 0.0,
        'sale_unit': p['sale_unit'] ?? 'BULTO',
        'is_variable_quantity': isVar,
        'target_units_per_shift': targetUnits,
        'millar_per_bulto': millarPerBulto,
        'unit_weight_kg': unitWeight,
      }, conflictAlgorithm: ConflictAlgorithm.replace);
    }
    await batch.commit(noResult: true);
  }

  Future<List<Map<String, dynamic>>> getCachedProducts() async {
    final db = await instance.database;
    return await db.query('cached_products', orderBy: 'name ASC');
  }

  // ==================== MACHINES ====================
  Future<void> saveCachedMachines(List<dynamic> machines) async {
    final db = await instance.database;
    final batch = db.batch();
    batch.delete('cached_machines');
    for (var m in machines) {
      batch.insert('cached_machines', {
        'id': m['id'],
        'code': m['code'] ?? '',
        'name': m['name'] ?? '',
        'type': m['type'] ?? '',
        'status': m['status'] ?? 'Operativa',
      }, conflictAlgorithm: ConflictAlgorithm.replace);
    }
    await batch.commit(noResult: true);
  }

  Future<List<Map<String, dynamic>>> getCachedMachines() async {
    final db = await instance.database;
    return await db.query('cached_machines', orderBy: 'code ASC');
  }

  // ==================== SHIFTS ====================
  Future<Map<String, dynamic>?> getActiveLocalShift() async {
    final db = await instance.database;
    final maps = await db.query(
      'local_shifts',
      where: 'status = ?',
      whereArgs: ['open'],
      limit: 1,
    );
    if (maps.isNotEmpty) return maps.first;
    return null;
  }

  Future<int> openLocalShift({
    required String shiftType,
    int? machineId,
    required String startTime,
    required String syncId,
    String? notes,
  }) async {
    final db = await instance.database;
    return await db.insert('local_shifts', {
      'shift_type': shiftType,
      'machine_id': machineId,
      'start_time': startTime,
      'status': 'open',
      'total_packages': 0.0,
      'total_weight': 0.0,
      'notes': notes,
      'sync_id': syncId,
      'is_synced': 0,
    });
  }

  Future<void> closeLocalShift({
    required String syncId,
    required String endTime,
    String? notes,
  }) async {
    final db = await instance.database;
    await db.update(
      'local_shifts',
      {
        'end_time': endTime,
        'status': 'closed',
        'notes': notes,
        'is_synced': 0, // Mark as needing update on server
      },
      where: 'sync_id = ?',
      whereArgs: [syncId],
    );
  }

  // ==================== PRODUCTIONS ====================
  Future<int> saveLocalProduction({
    required String shiftSyncId,
    required int productId,
    int? machineId,
    required String productName,
    required double quantity,
    required double weight,
    required String recordedAt,
    required String syncId,
    String? metadata,
  }) async {
    final db = await instance.database;
    
    // 1. Insert production
    final id = await db.insert('local_productions', {
      'shift_sync_id': shiftSyncId,
      'product_id': productId,
      'machine_id': machineId,
      'product_name': productName,
      'quantity': quantity,
      'weight': weight,
      'recorded_at': recordedAt,
      'status': 'pending_review',
      'sync_id': syncId,
      'metadata': metadata,
      'is_synced': 0,
    });

    // 2. Update local shift running totals
    final prods = await getShiftProductions(shiftSyncId);
    double totalWeight = 0;
    double totalPackages = 0;
    for (var p in prods) {
      totalPackages += (p['quantity'] as num).toDouble();
      totalWeight += (p['weight'] as num).toDouble();
    }

    await db.update(
      'local_shifts',
      {
        'total_packages': totalPackages,
        'total_weight': totalWeight,
      },
      where: 'sync_id = ?',
      whereArgs: [shiftSyncId],
    );

    return id;
  }

  Future<List<Map<String, dynamic>>> getShiftProductions(String shiftSyncId) async {
    final db = await instance.database;
    final results = await db.query(
      'local_productions',
      where: 'shift_sync_id = ?',
      whereArgs: [shiftSyncId],
      orderBy: 'recorded_at DESC',
    );
    return results.map((row) {
      final map = Map<String, dynamic>.from(row);
      var w = (map['weight'] as num?)?.toDouble() ?? 0.0;
      final name = (map['product_name'] ?? '').toString().toUpperCase();
      final isRoll = name.contains('BOBINA') || name.contains('ROLLO');
      if (w >= 500 && !isRoll) {
        w = double.parse((w / 1000.0).toStringAsFixed(4));
        map['weight'] = w;
      }
      return map;
    }).toList();
  }

  Future<void> updateLocalProduction({
    required int id,
    required String shiftSyncId,
    required int productId,
    required String productName,
    required double quantity,
    required double weight,
    String? metadata,
  }) async {
    final db = await instance.database;
    final Map<String, dynamic> updateValues = {
      'product_id': productId,
      'product_name': productName,
      'quantity': quantity,
      'weight': weight,
      'metadata': metadata,
      'is_synced': 0, // Needs re-sync
    };

    await db.update(
      'local_productions',
      updateValues,
      where: 'id = ?',
      whereArgs: [id],
    );

    // Recalculate shift totals
    final prods = await getShiftProductions(shiftSyncId);
    double totalWeight = 0;
    double totalPackages = 0;
    for (var p in prods) {
      totalPackages += (p['quantity'] as num).toDouble();
      totalWeight += (p['weight'] as num).toDouble();
    }

    await db.update(
      'local_shifts',
      {
        'total_packages': totalPackages,
        'total_weight': totalWeight,
        'is_synced': 0,
      },
      where: 'sync_id = ?',
      whereArgs: [shiftSyncId],
    );
  }

  Future<void> deleteLocalProduction(int id, String shiftSyncId) async {
    final db = await instance.database;
    await db.delete(
      'local_productions',
      where: 'id = ?',
      whereArgs: [id],
    );

    // Recalculate shift totals
    final prods = await getShiftProductions(shiftSyncId);
    double totalWeight = 0;
    double totalPackages = 0;
    for (var p in prods) {
      totalPackages += (p['quantity'] as num).toDouble();
      totalWeight += (p['weight'] as num).toDouble();
    }

    await db.update(
      'local_shifts',
      {
        'total_packages': totalPackages,
        'total_weight': totalWeight,
        'is_synced': 0,
      },
      where: 'sync_id = ?',
      whereArgs: [shiftSyncId],
    );
  }

  // ==================== SYNC QUEUE ====================
  Future<List<Map<String, dynamic>>> getPendingSyncShifts() async {
    final db = await instance.database;
    return await db.query(
      'local_shifts',
      where: 'is_synced = ?',
      whereArgs: [0],
    );
  }

  Future<List<Map<String, dynamic>>> getPendingSyncProductions() async {
    final db = await instance.database;
    return await db.query(
      'local_productions',
      where: 'is_synced = ?',
      whereArgs: [0],
    );
  }

  Future<void> markShiftSynced(String syncId, int? serverId) async {
    final db = await instance.database;
    await db.update(
      'local_shifts',
      {
        'is_synced': 1,
        'server_id': serverId,
      },
      where: 'sync_id = ?',
      whereArgs: [syncId],
    );
  }

  Future<void> markProductionSynced(String syncId, int? serverId) async {
    final db = await instance.database;
    await db.update(
      'local_productions',
      {
        'is_synced': 1,
        'server_id': serverId,
      },
      where: 'sync_id = ?',
      whereArgs: [syncId],
    );
  }
}