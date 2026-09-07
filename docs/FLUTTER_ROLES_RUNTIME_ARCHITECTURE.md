# Arquitectura de Sesiones, Roles y Flujos en Flutter APK (JSBolsas Pro)

Este documento detalla con máxima precisión la división de responsabilidades, roles humanos, pantallas (`.dart`), estructuras de datos en SQLite local (`local_db.dart`) y endpoints de Laravel API que componen la aplicación móvil **JSBolsas Pro**.

---

## 1. Mapa General de Roles y Módulos en la APK

| Sesión / Módulo | Operador Humano | Pantalla Flutter (`lib/screens/`) | Persistencia Local (SQLite) | Endpoints Laravel (`routes/api.php`) | Estado Actual |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Sesión 1: Turnos & Carga Offline-First** | Operario de Planta (Extrusor / Sellador) | `operator_dashboard.dart` | `local_shifts`<br>`local_productions`<br>`cached_products`<br>`cached_machines` | `POST /bag-factory/shifts/open`<br>`POST /bag-factory/productions/sync`<br>`POST /bag-factory/shifts/close` | **100% Funcional** (Offline-First garantizado) |
| **Sesión 2: Supervisión & Báscula** | Jefe de Operaciones / Auditor de Báscula | `supervisor_dashboard.dart` | Lee caché de productos | `GET /bag-factory/supervisor/feed`<br>`PUT /bag-factory/supervisor/productions/{id}`<br>`POST /bag-factory/supervisor/productions/{id}/approve`<br>`POST /bag-factory/supervisor/productions/{id}/reject`<br>`GET /bag-factory/supervisor/ticket/{id}` | **90% Funcional** (Falta driver nativo Bluetooth térmico) |
| **Sesión 3: Recepción Almacén General (JSPOS)** | Representante de Compras de JSPOS | `warehouse_lifting_screen.dart` | No requiere SQLite (Opera online/cámara) | `GET /bag-factory/lifting/pending`<br>`GET /bag-factory/lifting/scan/{code}`<br>`POST /bag-factory/lifting/receive` | **85% Funcional** (Falta webhook emisor a base de datos externa JSPOS) |

---

## 2. Detalle Exhaustivo por Sesión

---

### SESIÓN 1: Turnos / Carga Offline-First (Operador del Galpón)

#### 1. Usuario / Operador Humano
- **Quién es:** El trabajador u operario de fábrica que se encuentra a pie de máquina (extrusora o selladora) en el galpón de producción industrial.
- **Entorno de trabajo:** Entorno con conectividad WiFi/4G intermitente o inexistente dentro del galpón.

#### 2. Flujo de Trabajo Paso a Paso
1. **Inicio de Sesión / Reconocimiento Offline:**
   - Si no hay red, la aplicación valida las últimas credenciales almacenadas en `SharedPreferences` y permite entrar en **MODO OFFLINE** sin bloquear al trabajador.
2. **Apertura de Turno:**
   - Selecciona el tipo de jornada (**Diurno** o **Nocturno**) y la máquina asignada desde el catálogo precargado (`cached_machines`).
   - Genera un `sync_id` único local (`SHIFT-UUIDv4`) y registra el inicio del turno en SQLite (`local_shifts`).
3. **Selección y Escaneo de Producto:**
   - Puede buscar el producto en el catálogo en caché con búsqueda predictiva por nombre, medida o SKU, o escanear el código de barras del producto con la cámara (`CameraScannerScreen`).
4. **Captura de Pesaje:**
   - **Producto Regular (Bultos cerrados / Paquetes):** Ingresa la cantidad de paquetes y el peso total arrojado por la báscula física ($Kg$).
   - **Bobina / Rollo de Peso Libre (`is_variable_quantity`):** La app habilita el modo de desglose individual de bobinas. El operario ingresa el peso de cada bobina producida (ej: Bobina 1: $24.50\text{ Kg}$, Bobina 2: $23.80\text{ Kg}$), indicando color y lote. La app totaliza los kilos y el conteo de rollos automáticamente.
5. **Guardado Inmediato en SQLite:**
   - Se guarda el registro en la tabla `local_productions` con estado `pending_review`, `is_synced = 0`, y el JSON de bobinas en `metadata`.
6. **Sincronización Transparente en Cola:**
   - Un temporizador en segundo plano cada 30 segundos (`SyncService`) intenta enviar los registros pendientes a `POST /api/bag-factory/productions/sync`. Si hay éxito, marca `is_synced = 1`. Si la red falla, el operario sigue trabajando sin interrupciones.

#### 3. Estructura de Datos en `local_db.dart` (Garantía 100% Offline)
```sql
-- Catálogo de Productos en Caché
CREATE TABLE cached_products (
  id INTEGER PRIMARY KEY,
  name TEXT NOT NULL,
  sku TEXT,
  cost REAL,
  price REAL,
  is_variable_quantity INTEGER DEFAULT 0
);

-- Catálogo de Máquinas en Caché
CREATE TABLE cached_machines (
  id INTEGER PRIMARY KEY,
  code TEXT NOT NULL,
  name TEXT NOT NULL,
  type TEXT NOT NULL,
  status TEXT
);

-- Turnos Locales
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
);

-- Pesajes y Producción Local (Incluyendo Desglose de Bobinas)
CREATE TABLE local_productions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  server_id INTEGER,
  shift_sync_id TEXT NOT NULL,
  product_id INTEGER NOT NULL,
  product_name TEXT NOT NULL,
  quantity REAL NOT NULL,
  weight REAL NOT NULL,
  recorded_at TEXT NOT NULL,
  status TEXT DEFAULT 'pending_review',
  sync_id TEXT UNIQUE NOT NULL,
  metadata TEXT, -- JSON con desglose de bobinas individuales
  is_synced INTEGER DEFAULT 0
);
```

**Estructura del campo `metadata` para Bobinas de Peso Libre:**
```json
[
  {"weight": 24.50, "color": "Azul Rey", "batch": "EXT-01-2026"},
  {"weight": 23.80, "color": "Azul Rey", "batch": "EXT-01-2026"}
]
```

---

### SESIÓN 2: Supervisión / Báscula (Jefe de Operaciones)

#### 1. Usuario / Operador Humano
- **Quién es:** El Jefe de Planta, Supervisor de Turno o Auditor de Báscula Central.
- **Objetivo:** Verificar físicamente en la báscula de control que los bultos/bobinas pesados por los operarios coincidan con el peso real, certificar la calidad y generar la etiqueta de trazabilidad con código QR.

#### 2. Flujo de Trabajo Paso a Paso
1. **Monitoreo en Tiempo Real (Feed de Auditoría):**
   - El supervisor ingresa a `SupervisorDashboardScreen` (Pestaña "Auditoría").
   - La pantalla consulta `GET /api/bag-factory/supervisor/feed?status=pending_review` y muestra la lista ordenada de pesajes que los operarios han sincronizado.
2. **Auditoría Física y Ajuste en Báscula:**
   - Si el bulto requiere corrección de tara, medida o peso, presiona el botón **"Báscula"** (`_showAdjustWeightDialog`).
   - Envía `PUT /api/bag-factory/supervisor/productions/{id}` actualizando el peso neto verificado. Laravel conserva en `original_weight` el valor previo para fines de auditoría interna.
3. **Rechazo por Falla de Calidad:**
   - Si el producto está mal sellado, perforado o fuera de micraje, presiona **"Rechazar"** (`_showRejectDialog`).
   - Envía `POST /api/bag-factory/supervisor/productions/{id}/reject` con el motivo textual. El bulto queda excluido del stock vendible.
4. **Aprobación Oficial y Emisión de QR:**
   - Presiona **"Aprobar"** o **"Aprobar Todos"** (`POST /api/bag-factory/supervisor/productions/{id}/approve`).
   - Laravel asigna el identificador único `qr_code` (ej: `PKG-K9F2A1M8B0`), asigna `reviewed_by` y fecha/hora exacta. El bulto pasa automáticamente al estado `approved` (Pre-Levantamiento).
5. **Generación e Impresión del Ticket Térmico:**
   - En la pestaña "Pre-Levantamiento", presiona el icono de impresora para consultar `GET /api/bag-factory/supervisor/ticket/{id}`.
   - Se despliega el modal del ticket con el código QR renderizado, nombre del producto, operario, máquina, turno, peso total auditado y firma del supervisor.

#### 3. Respuesta Técnica y Estado Honesto
- **Comunicación con Laravel:** Se realiza mediante llamadas HTTP REST seguras con token de Sanctum. Las consultas traen agregaciones en vivo (`totals.total_weight`, `totals.total_packages`).
- **Impresión Térmica:**
  - **Estado Actual:** El modal visual (`_showTicketModal`) y el endpoint de datos del ticket están 100% programados.
  - **Lo que falta:** El botón de imprimir actualmente dispara un `SnackBar` informativo. Falta enlazar el plugin nativo de Flutter para Bluetooth ESC/POS (`flutter_bluetooth_serial` o `esc_pos_utils_plus`) para enviar el comando binario directo a impresoras térmicas portátiles (Zebra / Xprinter de 58mm/80mm).

---

### SESIÓN 3: Recepción Almacén General / JSPOS (Representante del Comprador Único)

#### 1. Usuario / Operador Humano
- **Quién es:** El representante o despachador de la empresa **JSPOS** (nuestro cliente único que adquiere el 100% de la producción de la fábrica para su distribución comercial).
- **Objetivo:** Auditar y retirar de la fábrica los bultos aprobados, escanearlos uno a uno con la cámara para confirmar su recepción y generar la planilla oficial de levantamiento para ingresar la mercancía al inventario comercial de ventas.

#### 2. Flujo de Trabajo Paso a Paso
1. **Acceso al Módulo:**
   - El representante ingresa a la APK con credenciales de almacén/despacho y entra a `WarehouseLiftingScreen`.
2. **Listado de Bultos Disponibles:**
   - La pantalla consulta `GET /api/bag-factory/lifting/pending`, trayendo todos los bultos en estado `approved` que aún no han sido retirados (`lifted_at IS NULL`).
3. **Escaneo Óptico con Cámara:**
   - El representante abre el visor de cámara integrado (`MobileScanner`).
   - Apunta al código QR (`PKG-...`) impreso en la etiqueta física de cada bulto.
   - El método `_onQrScanned` valida el código mediante `GET /api/bag-factory/lifting/scan/{code}` y marca automáticamente el bulto en la lista de recepción con un sonido/vibración de confirmación.
4. **Auditoría de Totales Levantados:**
   - La barra superior calcula en tiempo real el total de bultos seleccionados y el peso total acumulado en kilogramos.
5. **Confirmación de Recepción:**
   - Presiona **"CONFIRMAR RECEPCIÓN E INGRESAR AL POS"**.
   - La APK envía `POST /api/bag-factory/lifting/receive` con el arreglo de `production_ids`.
   - Laravel marca los bultos en estado `lifted`, asigna `lifted_by` y registra `lifted_at = now()`.

#### 3. Respuesta Técnica y Estado Honesto
- **Programación de `warehouse_lifting_screen.dart`:** Está 100% terminada, con cámara integrada, escaneo continuo por hardware, selección masiva manual y envío de payload consolidado.
- **Integración Externa con JSPOS Sales (Lo que falta):**
  - En la actualidad, el endpoint `receiveLifting` solo actualiza el estado interno en la base de datos de **JSBolsas Pro** (`jsbolsas_db`).
  - **Falta programar el Webhook / Cliente API Saliente** en Laravel que, al procesar el levantamiento, envíe una petición HTTP POST hacia el servidor de **JSPOS Sales**:
    ```json
    POST https://pos.plasticosmyf.com/api/purchases/from-factory
    Headers: {
      "Authorization": "Bearer <API_SECRET_KEY_JSPOS>",
      "Content-Type": "application/json"
    }
    Payload:
    {
      "factory_dispatch_id": "DISP-20260907-001",
      "supplier_name": "JSBolsas Pro (Fábrica Central)",
      "total_weight_kg": 450.80,
      "total_packages": 18,
      "received_by": "Juan Perez (JSPOS)",
      "items": [
        {
          "sku": "BOL-BAS-30X40-TRA",
          "product_name": "Bolsa Basura 30x40 Transparente",
          "packages": 10,
          "weight_kg": 250.00,
          "cost_per_kg": 2.10,
          "qr_codes": ["PKG-A1B2C3D4E5", "PKG-F6G7H8I9J0"]
        }
      ]
    }
    ```
  - Asimismo, en el backend receptor de **JSPOS Sales** se debe habilitar el endpoint que reciba este payload y cree la Orden de Compra / Ingreso de Inventario de manera automática.

---

## 3. Matriz de Estado Honesto del Desarrollo

| Componente / Característica | Estado Actual | ¿Qué está 100% listo? | ¿Qué falta por programar? |
| :--- | :---: | :--- | :--- |
| **Operador Galpón (Turnos & Offline)** | 🟢 100% | SQLite local (`local_db.dart`), captura regular y de bobinas individuales (`metadata`), colas de sincronización (`SyncService`), pantalla completa (`operator_dashboard.dart`). | Ninguno en este flujo. |
| **Báscula y Auditoría (Supervisor)** | 🟡 90% | Feed en tiempo real, ajustes de peso/tara (`PUT`), rechazos con motivo (`POST`), aprobación masiva con generación de QR, endpoint y modal de ticket térmico. | Integrar driver Bluetooth ESC/POS nativo para imprimir desde el teléfono en impresora térmica física. |
| **Recepción y Levantamiento (JSPOS)** | 🟡 85% | Pantalla `warehouse_lifting_screen.dart`, escáner QR de cámara (`MobileScanner`), selección múltiple, endpoint de recepción local en fábrica. | Webhook HTTP saliente desde JSBolsas Pro y endpoint receptor en JSPOS Sales para registro automático de la compra. |
