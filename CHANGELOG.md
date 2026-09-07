# Changelog - JSBolsas Pro

Todos los cambios notables de este proyecto se documentarán en este archivo.

## [2.3.0] - 2026-09-07 (Primer Release Oficial - JSBolsas Pro)

### Added
- **Módulo de Control de Planta & Finanzas en Vivo**:
  - Dashboard interactivo con cálculo en tiempo real de kilos producidos, bultos y rollos procesados.
  - Balance financiero en vivo: Ingresos de planta, costo fijo operacional por turno, costo de resina y utilidad neta acumulada.
  - Filtros dinámicos por jornada (Hoy, Esta Semana, Este Mes y Rango Libre).
  - Medidor de meta de utilidad diaria (Target $105.00/día) con porcentaje alcanzado y margen de ganancia real.

- **Módulo de Auditoría en Báscula & Control de Calidad**:
  - Inspección de bultos y bobinas individuales con pesaje real vs teórico.
  - Aprobación individual y por lote para pre-levantamiento.
  - Ajuste de peso en báscula y rechazo con notas de auditoría.
  - Impresión de tickets térmicos con código QR de trazabilidad e identificación de máquina/operario.

- **Módulo de Costos de Producción & Simulador de Precios**:
  - Simulación financiera multinivel automática: Precio Fábrica, Distribuidor (+10%), Mayorista (+17%) y Minorista (+21%).
  - Parámetros financieros globales de fábrica: Costo base de resina ($/kg), costo fijo por turno y meta de ganancia diaria.
  - Soporte completo para Modo Bobina (Peso variable en KG) y bolsas por millar/bulto.
  - Ficha técnica completa con dimensiones (ancho, largo, calibre, fuelle) y cálculo de peso físico exacto.

- **Módulo de Fórmulas de Mezcla & Materias Primas**:
  - Control de fórmulas y recetas con sistema de versionado.
  - Dosificación porcentual de componentes: Resina Virgen (LDPE/HDPE), Resina Reciclada, Pigmentos Masterbatch y Aditivos.
  - Catálogo de materias primas con trazabilidad de historial de precios de compra.
  - Cálculo automático del costo ponderado por kilogramo de mezcla ($/KG).

- **Módulo de Administración & Máquinas de Planta**:
  - Catálogo técnico de 240 productos y bobinas.
  - Catálogo de máquinas y líneas de producción: Extrusoras, selladoras y cortadoras con código único.
  - Gestión de usuarios y control de accesos por roles (Super Admin, Administrador, Jefe de Operaciones, Operario de Planta y Almacén).

- **App Móvil de Operarios (Flutter / Android)**:
  - App móvil dedicada `mobile_bolsas_app` (`JSBolsas.apk`) con versión sincronizada `v2.3.0`.
  - Apertura y cierre de turnos con código único trazable.
  - Registro y pesaje en báscula con sincronización offline/online a la API REST `/api/bag-factory/*`.

---

## [1.10.419] - 2026-08-30

### Changed
- **Nombre y Versión Dinámica de JSPOS en Pestañas del Navegador**:
  - `AppServiceProvider.php`: Se configuró el nombre de la aplicación global de forma dinámica (`JSPOS v{version}`) leyendo directamente del archivo `version.txt` y compartiendo `$systemTitle` y `$systemVersion` a todas las vistas.
  - `app.blade.php`, `app-simple.blade.php`, `login.blade.php`, `layouts/app.blade.php` y `layouts/guest.blade.php`: Se actualizaron las etiquetas `<title>` para mostrar de forma limpia y consistente la identidad oficial `JSPOS vX.X.XXX` (ej. `JSPOS v1.10.419` y `JSPOS v1.10.419 - Acceso` en el login), evitando que el nombre comercial de la empresa cliente sobreescriba el título de la pestaña del navegador.

## [1.10.418] - 2026-08-30

### Added
- **Configuración de Cabecera en Ticket de Cobranza por Operador**:
  - `Configuration.php`, `PrintTrait.php` y `settings.blade.php`: Agregada la sección *Ticket de Cobranza por Operador* en la pestaña 15 de Configuración (Tickets) con el switch `show_company_data` ("Datos de la empresa"), permitiendo mostrar u ocultar el nombre de la empresa en el encabezado del ticket térmico de cobranzas.

## [1.10.417] - 2026-08-30

### Added
- **Configuración Modular e Independiente de Tickets Térmicos (ESC/POS)**:
  - Nueva pestaña 15 en el módulo de Configuración (*Tickets*) que permite personalizar de manera independiente cada tipo de ticket térmico:
    - *Ticket de Venta (POS)*: Auto-impresión al cobrar (`auto_print`), datos de empresa en cabecera, subtotal, IVA, efectivo y cambio recibido (`show_cash_change`), mensaje de pie/agradecimiento, sitio web y código QR para clonación rápida.
    - *Ticket de Pedidos y Cotizaciones*: Cabecera, subtotal, IVA, cambio, pie de página, sitio web y QR.
    - *Ticket de Abonos y Cobranzas de Clientes*: Cabecera, deuda actual y saldo liquidado, pie de página.
    - *Ticket de Pagos a Proveedores*: Cabecera y deuda con el proveedor.
    - *Ticket de Corte y Arqueo de Caja*: Cabecera, desglose de ventas, desglose de cobranzas recibidas y movimientos de billetera/custodia.
    - *Ticket de Historial de Pagos*: Cabecera, notas de crédito/devoluciones y alerta de días de mora.
    - *Ticket Interno (Comprobante Contable)*: Encabezado comanda interna y desglose de recargos/comisiones/fletes.
  - Integración en el trait `PrintTrait.php` y el módulo POS `Sales.php`.

- **Configuración Modular e Independiente de Facturas y Documentos PDF**:
  - Nueva pestaña 16 en el módulo de Configuración (*Facturas PDF*) con controles independientes para:
    - *Facturas de Venta (Contado y Crédito)*: Logotipo, datos de empresa, datos de vendedor y operador, cuentas bancarias autorizadas, subtotal, IVA, recuadro de firma y sello, monto en letras, notas/observaciones, código QR y código alfanumérico de control/auditoría (`footer_code`).
    - *Pedidos y Cotizaciones*: Logotipo, datos de empresa, vendedor, cuentas bancarias, subtotal, IVA, firma/sello, monto en letras, notas, QR y código de auditoría.
    - *Órdenes de Compra a Proveedores*: Logotipo, datos de empresa, subtotal, IVA, firma de autorización, monto en letras, notas y código de auditoría.
    - *Notas de Débito y Ajustes de Inventario (Cargos y Descargos)*: Logotipo, datos de empresa, subtotal, IVA, firma/sello, monto en letras, notas y código de auditoría.
    - *Reportes PDF Generales*: Logotipo institucional, encabezado de empresa y pie con fecha/hora de generación.
  - Adaptación de todas las plantillas Blade en `resources/views/vendor/invoices/templates/` con retrocompatibilidad garantizada.

## [1.10.416] - 2026-08-29

### Fixed
- **Error de Servidor al Cargar 'Generador SaaS' (LicenseGenerator)**:
  - Se solucionó el error `Undefined variable $availableModules` al abrir el Generador Maestro de Licencias SaaS (`license-generator`).
  - Se pasan explícitamente las variables `$availableModules` y `$dependencies` desde el método `render()` del componente Livewire.

## [1.10.415] - 2026-08-29

### Fixed
- **Visualización y Detección de Copias de Seguridad en Disco**:
  - Se mejoró `Backups.php` para escanear recursivamente todos los archivos `.zip` en el disco de respaldos, permitiendo mostrar de inmediato las copias sin importar el nombre o carpeta de la empresa asignada por Spatie Backup.
  - Se optimizó la descarga (`BackupController.php`) para localizar y descargar cualquier archivo de respaldo en cualquier subcarpeta.
  - Se robusteció la autodetección de `mysqldump.exe` en `config/database.php` para verificar la existencia real del binario antes de usar rutas por defecto.

## [1.10.414] - 2026-08-29

### Fixed
- **Copias de Seguridad (Backups) sin Bloqueo por Servidor de Correo Inexistente**:
  - Se corrigió el error `Connection could not be established with host "mailpit:1025"` al crear respaldos de base de datos.
  - Se deshabilitaron las notificaciones por correo por defecto en `config/backup.php` (ahora controladas por `BACKUP_MAIL_ENABLED=true`) y se añadió el flag `--disable-notifications` en la UI de Backups, scripts `.bat` y el planificador de tareas (`Kernel.php`).
  - Las copias de seguridad manuales y automáticas ahora se ejecutan de manera inmediata y sin fallos.

## [1.10.413] - 2026-08-29

### Fixed
- **Soporte de Código de Moneda VED (Bolívares Digitales) en Ticket Térmico**:
  - Se añadió `VED` (código ISO de Bolívares en base de datos) a la detección de monedas nacionales en `PrintTrait.php`.
  - Ahora todas las ventas y transferencias en Bolívares (`VED`, `VES`, `BS`, `BS.`) se imprimen explícitamente con su símbolo `Bs. ` y su monto original en bolívares en el ticket.

## [1.10.412] - 2026-08-29

### Fixed
- **Moneda Correcta ($ / Bs.) y Eliminación del Símbolo '¥' en Ticket de Cobranza**:
  - Se eliminó el caracter UTF-8 `▶` que las impresoras térmicas ESC/POS interpretaban como `¥` (signo de Yen), sustituyéndolo por `>`.
  - Ahora cada fila de pago refleja su moneda original real: `$ ` para pagos en Dólares y `Bs. ` para pagos en Bolívares (usando `total_amount`), evitando asignar el signo `$` a pagos en moneda nacional.
  - Los subtotales y totales indican explícitamente `(USD)` para clarificar que corresponden a la conversión calculada a la tasa de cambio y no a una suma directa entre divisas diferentes.

## [1.10.411] - 2026-08-29

### Fixed
- **Formato del Símbolo de Dólar ($) en el Cuerpo del Ticket de Cobranza**:
  - Se homogeneizó la visualización explícita del símbolo de dólar `$ ` con espacio para cada fila de cobro, subtotales por departamento, subtotales por operador y total general en `PrintTrait.php`.

## [1.10.410] - 2026-08-29

### Fixed
- **Ajuste de Tipografía Condensada (Font B) en Ticket Térmico de Cobranza (58mm)**:
  - Se implementó la fuente compacta `Font B` (40 columnas) en el cuerpo del ticket para impresoras de 58mm en `PrintTrait.php`.
  - Ahora las descripciones de departamentos (`DEP: LOCAL`, `DEP: GRAVADO`), los métodos de pago largos y montos de 5 o más cifras se imprimen alineados en una sola línea sin desbordarse hacia la línea inferior.

## [1.10.409] - 2026-08-29

### Added
- **Interruptor de Resumen Condensado en Reporte de Cobranza por Operador**:
  - `SellerGroupedReport.php` & `seller-grouped-report.blade.php`: Agregado switch *Resumen Condensado* (inicia en `OFF` por defecto). Al activarse (`ON`), consolida de forma compacta el total cobrado por departamentos (`LOCAL` y `GRAVADO`), métodos de pago y totales generales, ocultando las filas repetidas por operador individual.
  - `PrintTrait.php`: Soporte para impresión térmica del ticket en formato resumen condensado cuando el switch está activo.
  - `ReportController.php` & `seller-grouped-report-pdf.blade.php`: Soporte de vista y exportación a PDF en formato resumen condensado.

## [1.10.408] - 2026-08-29

### Added
- **Impresión Térmica de Ticket para Reporte de Cobranza por Operador**:
  - `PrintTrait.php`: Añadido el método `printSellerGroupedTicket()` con soporte completo para 58mm y 80mm, desglose por departamentos (`LOCAL` y `GRAVADO`), métodos de pago, totales en USD y líneas de firmas de entrega/recepción de turno.
  - `SellerGroupedReport.php` & `seller-grouped-report.blade.php`: Integrado botón de acción *Imprimir Ticket Térmico* y método `printTicket()`.

## [1.10.407] - 2026-08-29

### Fixed
- **Persistencia de Departamento en Categorías y Enlace con Modelos**:
  - `Category.php`: Agregado `department_id` al array `$fillable` e implementada la relación `department()` (`belongsTo(Department::class)`).
  - `Categories.php` & `categories.blade.php`: Corregida la directiva de enlace bidireccional en Livewire 3 (`wire:model` en lugar del obsoleto `wire:model.defer`), eager-loading de departamentos en la consulta y mejora visual en la tabla para mostrar el nombre del departamento y su etiqueta de tipo (`LOCAL` o `GRAVADO`).

## [1.10.406] - 2026-08-29

### Fixed
- **Corrección en Clasificación por Defecto Local / Gravado en Reporte Cobranza por Operador**:
  - `SellerGroupedReport.php` & `ReportController.php`: Actualizada la subconsulta de proporciones para aplicar `leftJoin` en categorías y clasificar automáticamente como **LOCAL** cualquier producto/servicio que no pertenezca explícitamente a un departamento de tipo `gravado`. Esto evita que ventas de productos sin departamento asignado desaparezcan o se omitan al activar el desglose *Dividir Local/Gravado*.

## [1.10.405] - 2026-08-29

### Added
- **Renderizado Universal de Códigos QR para Impresoras Térmicas de 58mm y 80mm**:
  - `PrintTrait.php`: Implementado el método `printQrCode()` que genera el código QR como imagen de mapa de bits (raster `bitImage`) mediante `DNS2D` y `EscposImage`. Esto soluciona la omisión del código QR en impresoras de 58mm (las cuales carecen de soporte para comandos ESC/POS nativos `GS ( k`), garantizando compatibilidad del 100% en todas las marcas y tamaños de ticketeras térmicas.

## [1.10.404] - 2026-08-29

### Fixed
- **Resolución Robusta de Dispositivo e Impresora en PrintTrait**:
  - `PrintTrait.php`: Ampliado el alcance de detección del dispositivo autorizando la lectura del token desde cookies de solicitud, sesión, cabeceras HTTP (`X-Device-Token`) e incorporando un respaldo por dirección IP del cliente para garantizar que las preferencias de impresora configuradas en el Administrador de Dispositivos se apliquen de forma inmediata sin depender exclusivamente de cookies locales.

## [1.10.403] - 2026-08-29

### Fixed
- **Sanitización de Codificación UTF-8 en Registro de Logs**:
  - `Utf8LineFormatter.php`: Reemplazado el parámetro `'auto'` en `mb_convert_encoding()` por la lista explícita `'ISO-8859-1, Windows-1252, UTF-8'` para evitar advertencias de detección en PHP 8.x al formatear excepciones o advertencias del sistema.

## [1.10.402] - 2026-08-29

### Fixed
- **Ruta Absoluta a PowerShell y Utilidades de Windows para Impresión Spooler**:
  - `CustomWindowsPrintConnector.php`: Implementados los métodos `getPowerShellPath()` y `getSystemToolPath()` para localizar de manera infalible `powershell.exe`, `net.exe`, `nbtstat.exe` y `arp.exe` en `C:\Windows\System32\...` incluso en equipos donde el entorno de Apache/PHP no incluye el directorio de PowerShell en la variable `PATH`.
  - **Detección Automática de Host Local (`gethostname`)**: Si la ruta configurada corresponde al nombre del equipo local (`DESKTOP-XXXX`), el conector lo reconoce como loopback (`127.0.0.1`) sin demoras de red.
  - `PrinterHostResolverTest.php`: Incorporadas pruebas para validación de rutas del sistema y resolución de hostname local a loopback (100% PASS).

## [1.10.401] - 2026-08-29

### Added
- **Resolución Automática Inteligente de Nombres de Equipo para Impresoras de Red**:
  - `CustomWindowsPrintConnector.php`: Implementado el método estático `resolveHostnameToIp()` con resolución multi-capa (IPv4/IPv6 directa, `localhost`, DNS nativo, mDNS `.local`, tabla de malla JSVPN/Tailscale y descubrimiento dinámico por coincidencia NetBIOS/ARP con validación de puertos SMB `445`/`139`).
  - **Caché en Memoria Ultrarrápida**: Incorporada caché de 5 minutos en `\Illuminate\Support\Facades\Cache` para que las impresiones sucesivas se resuelvan instantáneamente a 0 ms.
  - **Autenticación Previa de Sesión SMB**: Apertura automática de sesión de red (`net use`) con credenciales antes del envío a la cola de impresión de Windows (`sendToWin32Spooler`), permitiendo imprimir tanto por nombre de equipo (`DESKTOP-XXXX`) como por dirección IP fija o dinámica.
  - `PrinterHostResolverTest.php`: Creada suite de pruebas unitarias cubriendo resolución directa de IP, resolución de loopback, resolución Tailscale/Mesh e inicialización UNC/SMB.

## [1.10.400] - 2026-08-26

### Fixed
- **Optimización de Memoria y Rendimiento en Generación de Etiquetas PDF**:
  - `LabelController.php`: Configurados límites de ejecución y memoria (`memory_limit = 1024M`, `set_time_limit = 300`) y resuelta la ruta física local del logo en disco (`$logoPath`) para evitar la repetición masiva de cadenas Base64 en el marcado HTML. Implementada caché de imágenes de código de barras/QR (`$barcodes`) por producto único para evitar la regeneración redundante de imágenes GD en bucles.
  - `labels.blade.php` & `labels_qr.blade.php`: Actualizadas las vistas PDF para utilizar el logo directo desde disco y consumir la estructura de códigos de barras/QR pre-calculados, reduciendo drásticamente el payload HTML transmitido al parser de Dompdf.
  - `LabelGeneratorTest.php`: Incorporada prueba de integración masiva para verificar la generación limpia de 500+ etiquetas sin agotar memoria.

## [1.10.399] - 2026-08-21

### Fixed
- **Solución Definitiva a Subida de Imágenes de Productos y Trazabilidad Integral con Logs**:
  - `LivewireUploadController.php`: Eliminada la comprobación rígida de rutas temporales de Windows que bloqueaba la subida con el mensaje `No se pudo leer la ruta temporal del archivo subido`. Implementada validación oficial basada en códigos de error PHP (`UPLOAD_ERR_OK`), detección de desbordamiento de `post_max_size`, mecanismo de respaldo por copia directa física y logs paso a paso (`[LivewireUpload]`) con registro de IP, host de conexión, nombre original de archivo, tamaño en bytes, tipo MIME y ruta temporal de almacenamiento.
  - `PostProduct.php`: Incorporada trazabilidad y logs detallados en `store()`, `update()`, `sanitizeGallery()` y `deleteSavedImage()` registrando ID de producto, rutas temporales y definitivas en `storage/app/public/products/`, resultado de copias físicas y creación de registros en la tabla `images`.
  - `AppServiceProvider.php`: Eliminada la directiva ineficaz `@ini_set('upload_tmp_dir', ...)` para evitar discrepancias de rutas en tiempo de ejecución en entornos Windows / Laragon / Apache.

## [1.10.398] - 2026-08-20

### Fixed
- **Sanitización Automática de Temporales y Reseteo Estricto de Galería**:
  - `PostProduct.php`: Implementado el método `sanitizeGallery()` que filtra automáticamente de `$gallery` cualquier objeto `TemporaryUploadedFile` cuya referencia en el disco `livewire-tmp` ya haya sido consumida o eliminada.
  - `Products.php`: Agregada la inicialización explícita `$this->form->gallery = []` en `Edit()` para evitar la acumulación o reutilización inadvertida de archivos temporales caducados al seleccionar imágenes previamente usadas o cambiar de producto.

## [1.10.397] - 2026-08-20

### Fixed
- **Controlador a Prueba de Fallos para Subidas de Livewire y Configuración Global de Servidor (`public/.user.ini` y `.htaccess`)**:
  - `LivewireUploadController.php`: Implementado controlador con interceptación y validación previa de archivos subidos. Si PHP descarta un archivo por superar límites o fallar permisos temporales, se captura limpiamente como `ValidationException` en lugar de lanzar la excepción no controlada 500 (`ValueError: Path cannot be empty at FilesystemAdapter.php:414`).
  - `public/.user.ini` & `public/.htaccess`: Añadidas directivas de servidor PHP para `upload_max_filesize = 64M`, `post_max_size = 64M` y `memory_limit = 512M` aplicables en Laragon / Apache / FastCGI / Nginx.

## [1.10.396] - 2026-08-19

### Fixed
- **Corrección Estética en Previsualización de Galería de Productos**:
  - `form.blade.php`: Reemplazadas las anotaciones del motor de plantillas por un bloque nativo de capturas de excepciones en PHP (`<?php try { ... } catch ... ?>`), eliminando el texto literal `@try` y `@catch` alrededor de la miniatura de previsualización.

## [1.10.395] - 2026-08-19

### Fixed
- **Solución Definitiva por Copia Física Directa al Guardar Productos (`ValueError: Path cannot be empty`)**:
  - `PostProduct.php`: Reemplazada la llamada a `$photo->storeAs()` por una copia física directa (`copy($realPath, $targetPath)`) verificando `file_exists($realPath)` y `filesize($realPath) > 0`. Esto elimina por completo cualquier fallo derivado de los flujos de lectura de temporales de Livewire o cierres de stream en Windows/Laravel.

## [1.10.394] - 2026-08-19

### Fixed
- **Auto-Sincronización de Dominio en Firmas Digitales de Subida de Livewire**:
  - `AppServiceProvider.php`: Implementada auto-sincronización dinámica de `app.url` y `URL::forceRootUrl` con el dominio real de la petición HTTP (`HTTP_HOST`). Esto resuelve el fallo de validación de firma (`401 Unauthorized`) que causaba el error `The form.gallery failed to upload` cuando la tienda del cliente corre en un dominio propio (ej. `jspos-insprocari.test`).

## [1.10.393] - 2026-08-19

### Fixed
- **Aumento Dinámico de Límites de Subida de Archivos y Traducción de Errores de Galería**:
  - `AppServiceProvider.php`: Elevados dinámicamente en tiempo de ejecución los límites de subida de PHP a `64M` (`upload_max_filesize` y `post_max_size`) y memoria a `512M`. Restaurado el disco temporal nativo de Livewire (`local`), eliminando el mensaje de error `The form.gallery failed to upload` generado por conflicto de firma/previsualización de discos.
  - `form.blade.php`: Traducidos y formateados los mensajes de error de validación de archivos al español con iconos claros para el usuario final.

## [1.10.392] - 2026-08-19

### Fixed
- **Protección de Almacenamiento Temporal de Livewire y Habilitación de Validaciones de Galería**:
  - `AppServiceProvider.php`: Redirigidos los temporales de subida de PHP (`upload_tmp_dir`) y el disco temporal de Livewire (`livewire.temporary_file_upload.disk`) directamente a `storage/app/public/livewire-tmp/`, eliminando fallos de `C:\Windows\Temp` o de límites de temporales en entornos Windows/Apache.
  - `PostProduct.php` & `form.blade.php`: Incorporadas reglas de validación explícitas para imágenes de galería (máximo 10MB, formatos válidos) y visualización de mensajes de error de validación en la interfaz.

## [1.10.391] - 2026-08-19

### Fixed
- **Solución a Error de Permiso Denegado (`C:\Windows\Temp`) en Descarga de Actualizaciones**:
  - `UpdateService.php`: Modificada la descarga automática de actualizaciones para usar `storage_path('app/...')` dentro del proyecto en lugar de `sys_get_temp_dir()` (`C:\Windows\Temp`), evitando bloqueos de permisos en servidores Windows / Apache / IIS. Asignado un nombre único por intento para evitar colisiones o bloqueos de archivos en reintentos tras caídas temporales de red.

## [1.10.390] - 2026-08-19

### Fixed
- **Corrección en Carga de Imágenes de Productos, Galería en Formulario y Auto-Reparación de Enlace Simbólico de Almacenamiento**:
  - `PostProduct.php`: Corregido error crítico donde `Image::create` asignaba `model_id = 0` en lugar del ID real del producto recién creado. Añadida verificación de archivos válidos (`getRealPath()`) en `store()` y `update()` evitando la excepción `ValueError: Path cannot be empty` cuando se seleccionan archivos inválidos o superiores al límite. Usado el disco explícito `public` para almacenamiento organizado.
  - `AppServiceProvider.php`: Implementada auto-creación y verificación de carpetas de almacenamiento (`storage/app/public/products`, `categories`, `livewire-tmp`) y auto-reparación del enlace simbólico (`storage:link` -> `public/storage`) en el arranque de la aplicación para cualquier instalación de cliente.
  - `form.blade.php` & `Products.php`: Añadida visualización de imágenes guardadas previamente en la base de datos con botón para eliminar imágenes individuales `(X)` y previsualización segura de nuevas imágenes seleccionadas.

## [1.10.389] - 2026-08-18

### Fixed
- **Seguridad en Instalador, Limpieza de Archivos de Desarrollo y Auto-Detección de IP VPN**:
  - `installer.iss`: Excluida estrictamente la clave privada (`private_key.pem`), scripts de generación de claves, herramientas de desarrollo, documentación `.md` y binarios APK del empaquetado del cliente. Configuradas las herramientas auxiliares de instalación para su auto-destrucción (`Flags: deleteafterinstall`) tras la instalación.
  - `open_browser.bat` & `post_install.bat`: Corregido el paso de la ruta real del proyecto para resolver el dominio de la tienda evitando nombres de directorios temporales (`temp.test`).
  - `LicenseService.php` & `LicenseRenewer.php`: Implementado el método `detectPrimaryIp()` para detectar y enviar automáticamente la IP de Tailscale (`100.x.x.x`) o red local durante la sincronización y solicitudes de licencias, activando el indicador en línea en el panel central.

## [1.10.388] - 2026-08-18

### Added
- **Integración con Servidor Cloud de Licencias (`licencias.jhonnypirela.dev`), Detección Multi-VPN y Claves RSA-4096**:
  - `InstallController.php` & `install/license.blade.php`: Conexión directa y segura vía HTTPS con el servidor central de licencias en la nube (`https://licencias.jhonnypirela.dev`). Incorporado selector inteligente de IP VPN (Tailscale, ZeroTier y red local) en el Paso 4 del instalador para registro automático de clientes.
  - `LicenseService.php` & `public_key.pem`: Sincronizada la clave pública de validación criptográfica RSA-4096 para firmas digitales en la nube. Añadido procesamiento dinámico de datos de contacto (`contact_name` y `contact_phone`) embebidos en el payload de la licencia.
  - `LicenseRenewer.php` & `license-renewer.blade.php`: Actualizada la ventana modal de renovación en el POS para notificar registros al servidor central en tiempo real y mostrar claramente la persona de contacto y teléfono asignados.
  - `installer.iss`: Empaquetado oficial del instalador 1-Click con soporte para el ecosistema cloud de licencias.

## [1.10.387] - 2026-08-17

### Fixed
- **Atribución de Operador y Clarificación de Estado en Cortes Diarios de Tesorería**:
  - `BankTreasury.php` & `bank-treasury.blade.php`: Agregada carga impaciente (*eager loading*) de la relación `openedBy` en la consulta de cortes diarios. Mejorada la interfaz gráfica de la tabla para diferenciar cuando un operador registró la **Apertura de Jornada** pero aún falta realizar el **Corte Diario** (cierre), mostrando el indicador de usuario `(Apertura cargada)`, distintivo `Pendiente Cierre`, mensaje explicativo `(Pendiente cierre real)` y acceso directo a la captura de pantalla de apertura, evitando que se etiquete erróneamente como `Sistema (Auto)`.
  - `BankTreasuryOtherIncomeAndAuditTest.php`: Incorporada prueba unitaria `test_opening_only_sets_opened_by_and_leaves_manual_closing_null` para certificar la consistencia del estado `open` y la correcta atribución de usuario.

## [1.10.386] - 2026-08-17

### Added
- **Soporte Completo para Métodos de Pago USDT BINANCE en Web, Reportes, Tickets y App VIP Móvil**:
  - `CurrencyHelper.php`: Creada la función auxiliar `getUsdtLabel()` para formatear dinámicamente el nombre de la cuenta USDT con su ID o número de cuenta de Binance Pay configurado (ej. `USDT BINANCE (ID: 1259627706)`).
  - `ReportController.php`, `DailySalesReport.php`, `PaymentRelationshipReport.php`: Incorporado el desglose y agrupación de ventas al contado y pagos a crédito en USDT BINANCE.
  - `PrintTrait.php` & `cash-count-pdf.blade.php`: Actualizada la impresión del ticket térmico y el reporte PDF de Corte de Caja con los subtotales e ID de la cuenta Binance.
  - `Api/PaymentController.php` & `Api/Vip/PaymentController.php`: Habilitada la recepción de abonos en USDT a través de la API REST con vinculación automática de comprobantes a `UsdtRecord`.
  - `mobile_vip_app/lib/main.dart`: Actualizado el formulario de pagos de la aplicación móvil de Clientes VIP para permitir la selección de USDT BINANCE, validación de referencias y carga de capturas de pantalla.

## [1.10.385] - 2026-08-15

### Fixed
- **Bypass de Límite de Consultas API de GitHub (Error 403 Rate Limit)**:
  - `UpdateService.php`: Implementado respaldo secundario (*fallback*) transparente hacia la CDN sin límite de peticiones (`raw.githubusercontent.com`), permitiendo la detección y descarga continua de actualizaciones sin verse bloqueado por el límite de 60 consultas/hora por IP pública en la API de GitHub.

## [1.10.384] - 2026-08-15

### Fixed
- **Automatización del Instalador, Importación de Productos y Módulo de Categorías**:
  - `installer/installer.iss` & `installer/setup.ps1`: Suprimido reinicio forzado del instalador, configurado auto-inicio de Laragon minimizado, forzado de extensión `zip` en `php.ini` y enlace `public/storage` por Windows Junction Point (`mklink /J`).
  - `Categories.php`: Corregida la evaluación de reglas de validación en Livewire, borrado seguro de imágenes de categorías con `Storage::disk('public')` y manejo correcto de excepciones de validación.
  - `ProductImport.php` & `product-import.blade.php`: Corregida la importación de productos desde Excel/CSV con mapeo por índice numérico de columna, normalización de tildes/espacios, conversión de precios/costos en formato español con comas y sincronización de stock con el almacén principal (`product_warehouse`).
  - `UpdateService.php`: Agregada la creación y reparación automática de enlace `public/storage` durante el proceso de actualización del sistema para clientes existentes.

## [1.10.383] - 2026-08-15

### Added
- **Revisión de Consistencia de Planes y Validación del Instalador**:
  - `PlanConsistencyTest.php` & `config/plans.php`: Agregada configuración centralizada y pruebas de coherencia para módulos y límites en planes SaaS.
  - `InstallController.php` & `GUIA_COMPILACION_INSTALADOR.md`: Mejorada la experiencia de instalación y validación del formulario de datos administrativos.
  - `CreditAuthorizationsList.php` & `CustomerSellerIdFallbackTest.php`: Robustecidas las autorizaciones de crédito y asignaciones de vendedor.

## [1.10.382] - 2026-08-15

### Fixed
- **Corrección de Envío de Correos de Reporte de Producción de Bolsas (Error de Serialización UTF-8 en Queue)**:
  - `ProductionReportMail.php` y `BagsProductionConsolidatedMail.php`: Corregido error `Unable to JSON encode payload. Error (5): Malformed UTF-8 characters, possibly incorrectly encoded` al encolar correos con archivos PDF binarios adjuntos. Se implementó la codificación a Base64 en el constructor y decodificación en `attachments()`, permitiendo una serialización JSON 100% limpia sin alterar el contenido binario del reporte en PDF.

## [1.10.381] - 2026-08-07

### Fixed
- **Corrección de Codificación de Caracteres UTF-8 (Acentos y Caracteres Especiales)**:
  - Corregidas las secuencias con doble codificación UTF-8 / ISO-8859-1 (como `DiagnÃ³stico`, `AnÃ¡lisis`, `proyecciÃ³n`, `recuperaciÃ³n`) en los componentes de reporte (`CashFlowForecastReport`, `ExchangeDiffReport`, `InventoryReport`, `StrategicDashboard`, `BillingOperatorsReport`, `ProductMovementsReport`, `SalesAnalysisReport`, `SellersPerformanceReport`, etc.), restaurando la visualización impecable de tildes y caracteres en español.

## [1.10.380] - 2026-08-07

### Added
- **Integración de Accesos de Auditoría de Actividad y Aprobaciones de Supervisión al Menú**:
  - `sidebar.blade.php`: Agregados los accesos directos de **Auditoría de Actividad** (`reports.audit`) y **Aprobaciones de Supervisión** (`consultation.approvals`) en la sección de **REPORTES** para facilitar la inspección centralizada de permisos y modificaciones otorgadas.

## [1.10.379] - 2026-08-07

### Added
- **Guía del Instalador Web Interactivo de JSPOS Sales**:
  - `GUIA_INSTALADOR_WEB.md`: Creado manual detallado que explica el funcionamiento paso a paso del asistente web (`/install`), cubriendo la auditoría de entorno en el Paso 1, configuración automática de base de datos MySQL en el Paso 2, migraciones y seeders maestros en el Paso 3, activación de licencia en el Paso 4, creación del usuario Administrador en el Paso 5 y la descarga del acceso directo ejecutable para el escritorio.

## [1.10.378] - 2026-08-07

### Added
- **Guías de Instalación Exhaustivas en la Raíz del Proyecto**:
  - `GUIA_INSTALACION_ZIP.md`: Manual paso a paso para el despliegue manual mediante descompresión de archivo comprimido `.zip` (Requisitos, Laragon, MySQL, `.env`, `DB_DUMP_PATH`, comandos Artisan, licencias Base64, respaldos automatizados con Google Drive y servicios NSSM).
  - `GUIA_INSTALACION_GIT.md`: Manual paso a paso para la instalación profesional mediante clonación del repositorio oficial Git (`git clone`, Composer, NPM, migraciones, licencias, respaldos y comandos de actualización continua).

## [1.10.377] - 2026-08-07

### Added
- **Modal de Vista Previa PDF Interactivo en Pantalla**:
  - `GoalCommissionReport.php`: Agregados los métodos `openPdfPreview()` y `closePdfPreview()` para renderizar la previsualización del PDF en modal flotante interactivo.
  - `goal-commission-report.blade.php`: Incorporados los dos botones estándar en paralelo (**Vista Previa** con ícono de ojo y **PDF** con descarga directa) y la ventana emergente modal con visor `<iframe>` embebido.

## [1.10.376] - 2026-08-07

### Added
- **Exportación en PDF PDF Corporativo para Reporte de Comisiones por Metas**:
  - `goal-commission-report-pdf.blade.php`: Creada la plantilla PDF formal alineada al diseño corporativo de JSPOS Sales (Encabezado institucional con logo, RUC/NIT, tabla de métricas KPI y detalle completo por vendedor).
  - `GoalCommissionReport.php`: Agregado el método `exportPdf()` para la generación y descarga en stream de PDF mediante DomPDF.

## [1.10.375] - 2026-08-07

### Refactored
- **Rediseño Visual del Reporte de Comisiones por Metas**:
  - Reestructurada la interfaz gráfica de `goal-commission-report.blade.php` para alinearse estrictamente al diseño visual nativo del sistema JSPOS Sales (Estructura de 2 columnas AdminLTE: barra lateral de filtros en la izquierda `col-md-3` y panel de métricas KPI y tablas de resultados estilizadas a la derecha `col-md-9`).

## [1.10.374] - 2026-08-07

### Fixed
- **Optimización del Reporte de Comisiones por Metas**:
  - `GoalCommissionReport.php`: Filtrado estricto para mostrar **únicamente** a los vendedores que tengan metas activas asignadas. Se eliminan las tarjetas/filas vacías de usuarios sin metas.

## [1.10.373] - 2026-08-07

### Fixed
- **Filtrado Estricto de Vendedores en Caja según Modelo de Comisión**:
  - `User.php`: Al estar seleccionado el modo **Comisiones por Metas Acumuladas de Ventas** (`tiered_goals`), el selector de vendedor en la caja/POS muestra **únicamente** a aquellos usuarios que tienen metas de ventas activas asignadas con casilla marcada en la matriz de asignación.
  - En los modos estándar o dual se mantienen los vendedores por rol y asignados.

## [1.10.373] - 2026-08-07

### Fixed
- **Filtrado de Vendedores y Enlace Directo a Comisiones por Metas**:
  - `User.php`: Al seleccionar el modelo **Comisiones por Metas Acumuladas de Ventas** (`tiered_goals`), el selector desplegable en la caja/POS muestra **únicamente** a los usuarios que tienen metas activas asignadas.
  - `sidebar.blade.php`: Añadido el enlace directo al **Reporte de Comisiones por Metas** (`/reports/goal-commissions`) en el menú lateral bajo la sección de **Reportes**.

## [1.10.372] - 2026-08-07

### Added
- **Configuración Personalizada de Días de Inicio y Cierre para Metas Semanales**:
  - `commission_goals`: Nuevas columnas `start_day_of_week` y `end_day_of_week` en la tabla de metas.
  - `CommissionGoalsManager`: Al seleccionar frecuencia **Semanal**, el formulario despliega dos selectores para definir los días exactos de inicio y cierre de semana (Ej: *Lunes a Viernes*, *Lunes a Sábado*, *Lunes a Domingo*).
  - `GoalCommissionService`: Los cortes semanales automáticos adaptan dinámicamente la ventana de evaluación de ventas al rango de días configurado en la meta.

## [1.10.371] - 2026-08-07

### Added
- **Sistema Flexible de Asignación de Vendedores y Comisiones por Metas Acumuladas (SaaS)**:
  - **Asignación Arbitraria de Vendedores en Ventas**: Conmutador en Configuración para seleccionar la modalidad de asignación de vendedor en caja (`customer_assigned`, `manual_select`, `both`). Añadido selector `<select>` dinámico en el POS filtrado automáticamente por usuarios elegibles.
  - **Periodicidad Configurable por Meta**: Catálogo de metas de ventas con frecuecia configurable (`diaria`, `semanal`, `quincenal`, `mensual`, `trimestral`, `anual`).
  - **Asignación de Metas a Usuarios**: Matriz interactiva de casillas en Configuración para asignar metas a cualquier usuario.
  - **Cortes Automáticos y Reporte de Comisiones por Metas**: Nuevo servicio `GoalCommissionService` y reporte interactivo `GoalCommissionReport` en `/reports/goal-commissions` para evaluar las ventas acumuladas por período y calcular los premios/comisiones alcanzados.
  - **Cero Regresión**: Totalmente compatible con la lógica preexistente de comisiones porcentuales y vendedores asignados a clientes.

## [1.10.370] - 2026-08-07

### Added
- **Configuración de Plantilla de Etiquetas por Defecto**:
  - `configurations`: Nueva columna `default_label_template` en la base de datos para almacenar el diseño preferido de etiquetas (Código de Barras Estándar o Código QR Grande).
  - `Settings.php` & `settings.blade.php`: Añadido selector en el panel de **Configuración General** para elegir la plantilla predeterminada del sistema.
  - `LabelGenerator.php`: Al ingresar al generador de etiquetas, se carga automáticamente la plantilla configurada por defecto, permitiendo al usuario cambiarla manualmente si lo desea en cualquier momento.

## [1.10.369] - 2026-08-07

### Fixed
- **Animación y Barra de Progreso en Tiempo Real para la Instalación ZIP Manual**:
  - `UpdateSystem.php`: Reestructurado el flujo de actualización por ZIP manual para usar el motor por pasos en tiempo real (`run-backup` -> `run-install` -> `run-migrate` -> `run-cleanup`).
  - Ahora se muestra la barra de progreso azul animada con los porcentajes exactos (5% -> 15% -> 60% -> 80% -> 100%) y los estados descriptivos mientras se crea el respaldo, descomprime el ZIP y actualiza la BD.

## [1.10.368] - 2026-08-07

### Fixed
- **Optimización del Proceso de Carga y Validación de Archivos ZIP en Livewire**:
  - `UpdateSystem.php`: Eliminada la regla estricta `mimes:zip` de Laravel (la cual rechazaba la carga en Windows por diferencias de MIME tipo `application/x-zip-compressed`) reemplazándola por validación de extensión real `.zip`.
  - `update-system.blade.php`: Añadido indicador de progreso visual en tiempo real (`wire:model.live` y `wire:loading`) al seleccionar el archivo `.zip` para evitar clics prematuros antes de completar la subida.

## [1.10.367] - 2026-08-07

### Fixed
- **Ampliación del Límite de Carga de Archivos ZIP Manuales a 200 MB**:
  - `config/livewire.php`: Actualizada la regla de subida de archivos temporales de Livewire a `max:204800` (200 MB), corrigiendo el error *"The manualZip field must not be greater than 12288 kilobytes"*.
  - `UpdateSystem.php`: Añadidos mensajes de validación personalizados en español para la subida de archivos ZIP de actualización.

## [1.10.366] - 2026-08-07

### Fixed & Added
- **Mejoras en Verificación de Actualizaciones e Instalación Manual por ZIP (Modo Offline/Conexión Lenta)**:
  - `UpdateService.php`: Incrementado el tiempo de espera HTTP a 25 segundos y agregado soporte de contingencia a la API de etiquetas (`/tags`) de GitHub si la API de releases excede el límite de peticiones (403 Rate Limit).
  - `UpdateService.php`: En caso de fallo de red, se devuelve el mensaje de error explícito para informar al usuario en lugar de responder engañosamente que "el sistema está actualizado".
  - `UpdateSystem.php` & `update-system.blade.php`: Añadida la opción para **Instalar Actualización Manual desde Archivo .ZIP**, permitiendo actualizar cualquier cliente desde una memoria USB o disco local de forma instantánea sin requerir internet.

## [1.10.365] - 2026-08-06

### Changed
- **Ajustes de Diseño en Etiquetas QR segun preferencias del usuario**:
  - `pdf/labels_qr.blade.php`: Actualizadas dimensiones a recuadro de `4.2cm` con borde negro de `2px`, logo en marca de agua de `130px` y fuente del título de producto a `18px Extra Negrita`.

## [1.10.364] - 2026-08-06

### Changed
- **Ajuste de Borde Fino (1.5px) en Etiquetas QR**:
  - `pdf/labels_qr.blade.php`: Reducido el grosor del contorno a `1.5px solid #000000` para brindar un acabado fino, estilizado y limpio, manteniendo la perfecta distribución de 18 etiquetas (3x6) en 1 sola hoja Carta.

## [1.10.363] - 2026-08-06

### Fixed
- **Ajuste de Altura a 4.05cm para 18 Etiquetas de 1 Sola Hoja (3x6)**:
  - `pdf/labels_qr.blade.php`: Ajustada la altura a `4.05cm` y márgenes a `0.1cm` garantizando que las 18 etiquetas completas (3 columnas × 6 filas) entren al 100% en 1 sola hoja Carta sin desbordar ni separar la última fila hacia la hoja 2.

## [1.10.362] - 2026-08-06

### Reverted
- **Revertido Formato de Etiquetas QR al Diseño de la Versión v1.10.360**:
  - Restablecida la plantilla `pdf/labels_qr.blade.php` a las dimensiones y proporciones exactas de la versión `v1.10.360` (borde de `4px`, altura `4.2cm`, título de producto `18px Extra Negrita` y logo de empresa centrado en marca de agua de `130px`).

## [1.10.361] - 2026-08-06

### Fixed
- **Ajuste de Altura para 6 Filas en Hoja Carta (DomPDF)**:
  - `pdf/labels_qr.blade.php`: Ajustada la altura del recuadro a `3.85cm` y margen del documento a `0.1cm` para garantizar que las 6 filas completas (18 etiquetas por hoja) encajen perfectamente en 1 sola hoja Carta sin desbordamiento generado por los bordes.

## [1.10.360] - 2026-08-06

### Changed
- **Aumento de Grosor en el Contorno de Etiquetas QR**:
  - `pdf/labels_qr.blade.php`: Incrementado el grosor del borde del recuadro de la etiqueta a `4px solid #000000` con cálculo interno (`box-sizing: border-box`), haciendo que el marco sea más visible y definido sin desplazar los márgenes ni alterar la distribución de la hoja.

## [1.10.359] - 2026-08-06

### Added
- **Logo de Empresa en Marca de Agua y Nombre Destacado en Etiquetas QR**:
  - `LabelController.php`: Codificación de la imagen del logo de la empresa (`Configuration->logo` o logo predeterminado) en formato Base64 para incrustar en el PDF.
  - `pdf/labels_qr.blade.php`: Añadido logo centrado como marca de agua sutil (`130px × 130px`, opacidad `0.15`) en el fondo de cada etiqueta.
  - Incrementado el tamaño de la fuente del nombre del producto a **18px Extra Negrita** (`font-weight: 900`) asegurando máxima legibilidad sobre la marca de agua.

## [1.10.358] - 2026-08-06

### Changed
- **Ajuste de Formato de Etiqueta Grande QR a 3x6 (18 etiquetas por hoja)**:
  - Rediseñado el alto a `4.2cm` y ajustada la tipografía para maximizar el aprovechamiento de papel a **18 etiquetas por hoja Carta** (3 columnas x 6 filas) conservando la excelente visibilidad del nombre del producto, datos de operador/fecha y Código QR 2D.

## [1.10.357] - 2026-08-06

### Changed
- **Ajuste de Formato de Etiqueta Grande QR a 3x5 (15 etiquetas por hoja)**:
  - Optimizado el alto de las etiquetas a `5.0cm` y la tipografía para aprovechar mejor el papel imprimiendo **15 etiquetas por hoja Carta** (3 columnas x 5 filas) manteniendo el nombre de producto grande, líneas de operador/fecha bien visibles y el Código QR 2D.

## [1.10.356] - 2026-08-06

### Added
- **Diseño Seleccionable en Generador de Etiquetas (Estándar vs. Grande con Código QR)**:
  - **Selector de Formato**: En el Generador de Etiquetas (`livewire/label-generator.blade.php`), ahora se puede elegir entre el diseño "Estándar (Código de Barras 1D - 4x7)" y el nuevo diseño "Grande (Código QR 2D - 3x4)".
  - **Nuevo Formato Grande con Código QR**: Nueva plantilla PDF (`pdf.labels_qr.blade.php`) de 12 etiquetas por hoja (3 columnas x 4 filas), con el nombre del producto en tamaño grande destacado, espacio amplio para datos de *Operador* y *Fecha*, y un Código QR 2D compacto generado con `DNS2D`.
  - **Pruebas Automatizadas**: Creado `tests/Feature/LabelGeneratorTest.php` para validar la selección de formatos y la generación limpia de PDF en ambos modelos.

## [1.10.355] - 2026-08-05

### Fixed
- **Corregida visualización de la casilla del PIN al solicitar autorización de crédito**:
  - `Sales.php`: `requestCreditAuthorization()` ahora establece `$this->pendingCreditAuthId = true;` y limpia `$this->creditAuthPin` al enviar la solicitud de autorización a supervisores.
  - Esto permite que la ventana modal transforme inmediatamente el botón "Solicitar Autorización" en el formulario para ingresar y validar el PIN de 6 caracteres.

## [1.10.354] - 2026-08-05

### Fixed
- **Corregida Consulta SQL Errónea en Tesorería (`BankTreasury.php`)**:
  - Se corrigió el nombre de columnas en la consulta de categorías de ingreso de `BankRecord`. Anteriormente consultaba `type` y `description` (que no existen en la tabla `bank_records`), causando un `QueryException (1054 Unknown column 'description')` que activaba la pantalla de actualización requerida.
  - La consulta ahora utiliza correctamente las columnas `income_type` e `income_category`.

## [1.10.353] - 2026-08-05

### Fixed
- **Auto-Reparación de Base de Datos ante Excepciones de Tablas/Columnas Faltantes**:
  - `Handler.php` ahora intercepta automáticamente excepciones `QueryException` (códigos 42S02/42S22) y ejecuta la migración/reparación de esquema inmediatamente en caliente sin mostrar la tarjeta roja de actualización al usuario.
  - La ruta `/system/upgrade-db` ahora redirige de forma segura al `/dashboard` en caso de error/advertencia para evitar bucles de redirección.

## [1.10.352] - 2026-08-05

### Fixed
- **Ejecución Automática de Migraciones al Instalar Actualizaciones**:
  - `UpdateService::installUpdate()` ahora ejecuta las migraciones de base de datos (`runMigrations()`) y los seeders de permisos inmediatamente al descomprimir y copiar la actualización.
  - `UpdateService::runMigrations()` genera automáticamente el archivo indicador de `AutoMigrate` (`storage/framework/migrated_{version}.log`), garantizando que al actualizar el sistema los clientes NUNCA vean la pantalla de "Actualización de Base de Datos Requerida".
  - Se actualizó el middleware `AutoMigrate` para delegar la ejecución a `UpdateService::runMigrations()`, asegurando consistencia entre actualización manual y automática.

## [1.10.351] - 2026-08-05

### Fixed
- **Corregida Pantalla de "Actualización de Base de Datos Requerida" en Bucle**:
  - El botón "Actualizar Base de Datos Ahora" ahora ejecuta el proceso completo de migración (`UpdateService::runMigrations()` con seeders) en lugar de solo `migrate --force`.
  - Se crea correctamente el flag file de `AutoMigrate` para que el middleware no vuelva a mostrar la pantalla de actualización después de migrar exitosamente.
  - Se agrega log de errores en caso de fallo para diagnóstico.

## [1.10.350] - 2026-08-05

### Added
- **Categorías de Gasto y de Ingreso Dinámicas en Tesorería Bancaria**:
  - Corregida la carga de categorías de gasto (aparecían vacías). Se implementó auto-seed de 10 categorías predeterminadas si la tabla está vacía.
  - Al seleccionar "➕ OTRO (CREAR NUEVA CATEGORÍA)" en el modal de Gasto, aparece un campo de texto para escribir el nombre de la nueva categoría. Se convierte automáticamente a MAYÚSCULAS y no se permiten duplicados.
  - Mismo comportamiento para el modal de Ingreso: al seleccionar "➕ OTRO (CREAR NUEVO TIPO DE INGRESO)", aparece un campo para crear un nuevo concepto de ingreso en MAYÚSCULAS.
  - Todas las categorías existentes se muestran en formato MAYÚSCULAS uniforme.

## [1.10.349] - 2026-08-05

### Improved
- **Generación de PINs Únicos Individuales por Supervisor**:
  - Implementada la generación de **PINs numéricos únicos de 6 dígitos** generados de forma individual para cada supervisor receptor (Email / WhatsApp) en solicitudes de **Crédito, Edición de Facturas y Anulación de Facturas**.
  - Corregida la resolución del supervisor autorizador al validar el PIN, garantizando que el sistema identifique exactamente al supervisor que recibió y utilizó su PIN individual en los registros de auditoría e historiales.

## [1.10.348] - 2026-08-05

### Fixed
- **Corregida Cancelación de Edición de Factura en POS**:
  - Reemplazada la llamada a `resetCart()` (que causaba `BadMethodCallException`) por `$this->clear()`, permitiendo cancelar el modo de edición de forma limpia y restaurar la pantalla.

## [1.10.347] - 2026-08-05

### Fixed
- **Corregida Ejecución del Método de Guardado al Editar Factura**:
  - Cambiada la llamada en `saveEditSale()` para invocar el método principal de procesamiento de ventas `$this->Store($cashRegisterService)` (en lugar del método auxiliar `save()` que solo actualizaba la sesión de carrito sin guardar en la BD).
  - Removida la restricción de validación de efectivo en ventas a crédito/contado durante el modo de edición de factura, asegurando que se guarden los cambios de productos (Servilleta) y se cree la **Edición #3** en el historial.

## [1.10.346] - 2026-08-05

### Fixed
- **Corregida Fuente de Productos del Carrito al Guardar Venta/Edición**:
  - Cambiada la lectura del carrito al guardar para priorizar la propiedad del componente `$this->cart` sobre `session('cart')`. Esto soluciona la falla donde al editar una factura la sesión contenía 0 ítems o ítems desactualizados, asegurando que se guarden los nuevos productos (p. ej. Servilleta) y se emita la Edición #3 en el historial con la notificación `"FACTURA #XXXX EDITADA CON ÉXITO"`.

## [1.10.345] - 2026-08-05

### Fixed
- **Corregida Limpieza de Sesión al Cargar Edición en POS**:
  - Limpiada la variable de sesión `editing_sale_id` inmediatamente al ingresar al POS en `mount()`. Esto evita que Livewire re-ejecute `loadSaleToEdit()` en cada búsqueda o selección de producto, permitiendo agregar productos al carrito y guardar los cambios normalmente.

## [1.10.344] - 2026-08-05

### Improved
- **Mensaje de Notificación de Edición de Factura**:
  - Cambiada la notificación al guardar una edición de `"VENTA REGISTRADA CON ÉXITO"` a **`"FACTURA #XXXX EDITADA CON ÉXITO"`**.
  - Garantizada la creación infalible del registro `SaleHistoryLog` incluso en escenarios de re-renderizado o des-serialización de datos originales.

## [1.10.343] - 2026-08-05

### Fixed
- **Corregida Persistencia de Autorización y Log de Edición en POS**:
  - Garantizada la preservación del estado `$creditAuthApproved = true` y del supervisor autorizador al cargar la venta en el POS.
  - Asegurada la grabación efectiva del nuevo registro de auditoría (`SaleHistoryLog`) con la comparativa real al presionar **`Guardar Cambios de Factura`** (Edición #3).

## [1.10.342] - 2026-08-05

### Fixed
- **Sincronización de Auditoría al Guardar Cambios de Edición**:
  - Removido el registro prematuro de `SaleHistoryLog` que ocurría al validar el PIN antes de editar en el POS.
  - Asegurada la creación del registro de auditoría únicamente cuando el usuario presiona **`Guardar Cambios de Factura`** en el POS, capturando exactamente los productos agregados/eliminados (`old_data` vs `new_data`).
  - Añadido el campo **`🤝 Acuerdo: USD/BCV`** a las fichas comparativas de configuración en el historial de ediciones.

## [1.10.341] - 2026-08-05

### Improved
- **Clarificación de Instrucciones en Banner de Edición POS**:
  - Actualizado el texto e íconos del banner amarillo en `sales.blade.php` para indicar claramente que debe presionarse el botón amarillo **`Guardar Cambios de Factura #XXXX`** para concluir la edición (sin confundir con Guardar Orden).

## [1.10.340] - 2026-08-05

### Fixed
- **Corregido Nombre de Relación `details` en Modelo `Sale`**:
  - Cambiada la llamada `$sale->load('saleDetails')` a `$sale->load(['details.product'])` en `SalesReport.php`, corrigiendo la excepción `BadMethodCallException: Call to undefined relationship [saleDetails] on model [App\Models\Sale]`.

## [1.10.339] - 2026-08-05

### Fixed
- **Corregida Creación de Registro `SaleHistoryLog` en Confirmación de PIN**:
  - Ajustados los parámetros al modelo y esquema de base de datos (`old_data`, `new_data`, `reason`, `authorized_by_id`, `user_id`, `sale_id`), eliminando la excepción `1364 Field 'old_data' doesn't have a default value`.

## [1.10.338] - 2026-08-05

### Fixed
- **Corregida Columna `status` en Tabla `credit_authorizations`**:
  - Modificado el tipo de datos de la columna `status` de `ENUM('pending', 'used', 'expired')` a `VARCHAR(30)` mediante migración para admitir el estado `'approved'`, solucionando la advertencia/excepción MySQL 1265 (Data truncated for column 'status').

## [1.10.337] - 2026-08-05

### Fixed
- **Solución Definitiva a `MultipleRootElementsDetectedException` en `salesr.blade.php`**:
  - Escapadas las etiquetas literales `</div>` dentro del string JavaScript de renderizado de TomSelect (`'<' + '/div>'`).
  - Corregido el cierre redundante de divs en el loop del historial de modificaciones (`$saleHistory`). Esto evita que el parser de `DOMDocument` de PHP confunda código JavaScript con etiquetas de cierre HTML y resuelve el error de Livewire 3 al 100%.

## [1.10.336] - 2026-08-05

### Fixed
- **Corregida Estructura HTML de Nodo Raíz Único en `salesr.blade.php`**:
  - Eliminado el `<div>` sobrante del modal de chofer y consolidados los escuchadores JavaScript de modales dentro del bloque principal, solucionando definitivamente la excepción `MultipleRootElementsDetectedException`.

## [1.10.335] - 2026-08-05

### Fixed
- **Compatibilidad Livewire 3 en `salesr.blade.php`**:
  - Envuelta la etiqueta `<script>` en las directivas `@script` ... `@endscript` para prevenir la excepción `MultipleRootElementsDetectedException`.

## [1.10.334] - 2026-08-05

### Added & Improved
- **Autorización Previa Obligatoria con Motivo y PIN para Editar o Eliminar Facturas**:
  - Al hacer clic en ✏️ **"Editar Factura"** o 🗑️ **"Anular/Eliminar Factura"**, el sistema despliega inmediatamente un modal en el reporte de ventas.
  - **Motivo Obligatorio**: Requiere que el operador escriba obligatoriamente la justificación/motivo de la edición o anulación.
  - **Envío de PIN a Supervisores**: Al solicitar el PIN, el sistema envía un PIN único diferente a los supervisores por Correo y WhatsApp junto con el operador, número de factura, cliente, monto y el motivo explicativo.
  - **Bloqueo hasta Validación**: El sistema únicamente redirige al POS (modo edición) o anula la factura cuando se valida con éxito el PIN ingresado.

## [1.10.333] - 2026-08-05

### Fixed
- **Añadidas Relaciones Eloquent `deletionApprovedBy` y `deletionRequestedBy` en Modelo Sale**:
  - Solucionada la excepción `RelationNotFoundException` al consultar el historial de auditoría de facturas eliminadas/anuladas.

## [1.10.332] - 2026-08-05

### Added & Improved
- **Botón Explícito de Guardado y Solicitud de PIN para Edición de Facturas**:
  - Incorporado en la caja/POS el botón **`🔑 Guardar Cambios de Factura #[ID]`** dentro del panel de Modo Edición Activo.
  - Al presionar este botón, el sistema dispara automáticamente la solicitud de PIN de edición de factura a los supervisores configurados y despliega el modal de validación de PIN.

## [1.10.331] - 2026-08-05

### Fixed
- **Corrección de Método de Consulta en Venta (editSale & getSaleHistory)**:
  - Corregida la llamada a `withTrashed()` en `Sale` reemplazándola por verificación directa de `deleted_at` y `status`, solucionando la excepción `BadMethodCallException`.

## [1.10.330] - 2026-08-05

### Fixed & Improved
- **Permisos de Edición para Super Admin**:
  - Asegurado que los usuarios con rol `Super Admin` o administradores puedan presionar el botón ✏️ **"Editar Factura"** en el reporte de ventas sin bloqueos.
  - Bloqueada la edición en facturas anuladas o eliminadas para mantener integridad del sistema.
- **Claridad del Modo Edición en el POS**:
  - Actualizado el panel **Modo Edición Activo** en el POS para indicar explícitamente cómo solicitar el PIN de autorización y finalizar la edición (haciendo clic en **Guardar Orden** / **Método de Pago**).
  - Añadido botón visible **"❌ Cancelar Edición y Salir"** para descartar cambios en cualquier momento.

## [1.10.329] - 2026-08-05

### Added & Improved
- **Bloques Independientes de Configuración de Autorizaciones POS**:
  - Separada la configuración de destinatarios (Correos y Usuarios de WhatsApp) en 3 bloques independientes dentro de **Ajustes > Notificaciones / WhatsApp**:
    1. **Autorizaciones de Crédito (POS)**
    2. **Autorización de Edición de Facturas**
    3. **Autorización de Anulación / Eliminación de Facturas**
  - Si los destinatarios de Edición o Eliminación se dejan vacíos, el sistema utiliza inteligentemente a los supervisores de Crédito como alternativa de respaldo.

## [1.10.328] - 2026-08-05

### Added & Improved
- **Sistema de Autorizaciones por PIN Único por Supervisor y Auditoría Completa de Facturas**:
  - **PIN Único por Destinatario**: Al solicitar autorización (Crédito, Edición o Eliminación), el sistema ahora genera un PIN de 6 caracteres individualizado para cada supervisor/administrador configurado (por correo electrónico y WhatsApp).
  - **Identificación Automática de Supervisor**: Eliminada la selección manual de supervisor en el modal. Al ingresar el PIN en la caja, el sistema reconoce automáticamente a qué supervisor pertenecía ese PIN y le atribuye la aprobación.
  - **4to Actor en la Auditoría (`🔑 Autorizado por`)**: El historial de auditoría de ventas ahora desglosa los 4 actores clave (Creador original, Vendedor asignado, Operador que editó/eliminó y Supervisor que otorgó la autorización por PIN).
  - **Auditoría de Facturas Eliminadas**: Habilitada la visualización del historial completo de auditoría y motivo de anulación para facturas eliminadas/anuladas.

## [1.10.327] - 2026-08-05

### Fixed
- **Resolución de Vendedor en Auditoría Histórica**:
  - Corregido el mecanismo de respaldo (fallback) al consultar vendedores en registros de edición antiguos. Se evita mostrar "Sin Vendedor" si el vendedor no sufrió cambios o si pertenece al usuario por defecto `OFICINA`.

## [1.10.326] - 2026-08-05

### Added & Improved
- **Visualización de Roles de Usuario en Auditoría de Facturas**:
  - Incorporado desglose explícito de los 3 roles de personas involucradas en cada factura:
    1. **Facturado por (Creador)**: Operador que registró la factura inicialmente en el POS.
    2. **Vendedor Asignado**: Vendedor comisionista asignado a la venta o al cliente.
    3. **Editado por (Operador)**: Usuario autorizado que realizó la modificación.

## [1.10.325] - 2026-08-05

### Fixed
- **Corrección de Sintaxis Blade en Reporte de Ventas**:
  - Corregido el cierre de directiva `@endif` en la plantilla de auditoría `salesr.blade.php`.

## [1.10.324] - 2026-08-05

### Added & Improved
- **Auditoría de Cambios de Configuración de la Factura**:
  - Incorporada detección y visualización automática de cambios en la configuración y condiciones de la factura: Flete (%), Comisión (%), Recargo Base (%), Diferencial Cambiario (%), Días de Crédito y Vendedor Asignado.
  - Añadido bloque de resumen de parámetros de configuración previa vs nueva en el desglose comparativo.

## [1.10.323] - 2026-08-05

### Fixed & Improved
- **Compatibilidad de Despliegue de Comparativa (Bootstrap 4/5)**:
  - Corregido el botón `Ver Comparativa Completa Detallada (Antes vs Después)` agregando los atributos `data-toggle="collapse"` y `data-target` compatibles con Bootstrap 4 para que al hacer clic se despliegue de inmediato la tabla comparativa lado a lado.
- **Tratamiento de Logs Históricos Anteriores**:
  - Para modificaciones realizadas antes del release v1.10.322 (donde los productos del estado posterior no se guardaban en la base de datos), el sistema ahora detecta el registro histórico y evita clasificarlos erróneamente como "productos sacados", aclarando la variación de monto registrada.

## [1.10.322] - 2026-08-05

### Fixed & Improved
- **Rediseño del Historial de Cambios de Ventas (Auditoría de Edición)**:
  - **Resolución Dinámica de Nombres**: Corregido el problema donde aparecía la palabra genérica `"Producto"`; ahora el sistema resuelve y muestra los nombres reales de los productos tanto para logs históricos como nuevos.
  - **Resumen Inteligente de Cambios (Smart Diff)**: Identificación clara y visual de productos agregados 🟢 (`+`), eliminados 🔴 (`-`) y modificados 🟡 (`✏️` en cantidad/precio).
  - **Resumen Financiero Claro**: Muestra el **Monto Anterior**, **Monto Nuevo** y la **Diferencia Total** de la modificación con guía explicativa sobre la lectura de los estados.
  - **Corrección en Registro de Auditoría (`Sales.php`)**: Corregido el momento de grabación de `SaleHistoryLog` para asegurar que capture el nuevo estado completo de la venta tras guardar los nuevos detalles de productos.

## [1.10.321] - 2026-08-04

### Fixed & Improved
- **Prevención de Abonos/Pagos Duplicados**:
  - Implementada validación en backend (`PartialPayment.php` y `AccountsReceivableReport.php`) que previene el registro accidental de pagos idénticos (mismo monto, referencia y cliente) en menos de 60 segundos por doble clic o reenvío de formulario.

## [1.10.320] - 2026-08-04

### Improved
- **Clarificación de Etiqueta de Notificaciones en Fábrica de Bolsas**:
  - Renombrada la sección de configuración de notificaciones de `Fábrica de Bolsas (Cierre de Turno / Producción)` a `Fábrica de Bolsas (Levantamiento de Mercancía / Operador)` para reflejar adecuadamente la emisión del levantamiento original desde la app móvil.

## [1.10.319] - 2026-08-04

### Fixed & Audited
- **Auditoría Global de Reportes PDF y Corrección de PDF de Inventario**:
  - Realizada auditoría automatizada en 20 tipos de reportes PDF del sistema.
  - Corregido fallo de `Undefined array key "sku"` / `"elaborado"` en la generación del PDF del Reporte de Inventario (`inventoryPdf` en `ReportController.php` e `inventory-report-pdf.blade.php`).
  - Añadidas columnas y firmas por defecto en el controlador e implementadas comprobaciones seguras (`!empty()`) en la plantilla Blade.
  - Añadida suite de pruebas automatizadas `AllPdfReportsTest` que verifica que todos los PDF del sistema abren e imprimen sin errores 500.

## [1.10.318] - 2026-08-04

### Fixed
- **Error 500 en PDF de Reporte de Desempeño de Vendedores**:
  - Corregido `ErrorException: Undefined variable $invoiceStatus` y `$invoiceLimit` al generar el PDF del Reporte de Desempeño y Cartera de Vendedores (`sellersPerformancePdf` en `ReportController.php`).
  - Incorporadas las variables de configuración en la llamada a la vista `reports.sellers-performance-pdf` y añadidas validaciones de seguridad para ventas sin cliente o con relaciones nulas.

## [1.10.317] - 2026-08-04

### Fixed & Improved
- **Notificaciones de Levantamiento de Mercancía y Auditoría de Bolsas (Doble PDF)**:
  - Al registrar la **Planilla de Levantamiento de Mercancía** desde la app móvil o web, se envía notificación inmediata (Email + WhatsApp) al operador que levantó el lote y a la planta.
  - Al **aprobar el cargo desde administración**, la notificación por correo adjunta **ambos archivos PDF comparativos**:
    1) `planilla_ORIGINAL_lote_X.pdf`: Levantamiento inicial sin modificaciones (marca de agua *ORIGINAL*).
    2) `planilla_APROBADA_lote_X.pdf`: Levantamiento procesado e ingresado a inventario (marca de agua *PROCESADO/APROBADO*).
  - Notificación de auditoría por WhatsApp enviada automáticamente a los administradores configurados.

## [1.10.316] - 2026-08-04

### Added
- **Centralización de Notificaciones para la Fábrica de Bolsas (Email & WhatsApp)**:
  - Incorporadas dos nuevas secciones en el **Centro de Gestión de Notificaciones**:
    1. **🛍️ Fábrica de Bolsas (Cierre de Turno / Producción)**: Gestión de correos electrónicos y usuarios/grupos de WhatsApp a quienes llega la notificación del lote de producción.
    2. **🏢 Fábrica de Bolsas (Administración / Aprobados)**: Gestión de correos de auditoría y usuarios/grupos de WhatsApp para la aprobación de la planilla y entrada de inventario.
  - Sincronización automática bidireccional entre la plantilla en **Ajustes > Producción** y el menú de **Gestión de Notificaciones**.
  - Despacho automatizado por WhatsApp a los operadores/administradores seleccionados al cerrar turno o aprobar la planilla.

## [1.10.315] - 2026-08-04

### Fixed
- **Código de Control de Flete y Recargo en PDF de Órdenes de Pedido**:
  - Corregida la resolución del porcentaje de **Flete** (ej: `F6` para 6%) y **Recargo** (ej: `RC4` para 4%) en la cadena de código de auditoría imprimible del PDF de la orden de pedido (`getOrderInvoiceFooterData` en `PdfOrderInvoiceTrait.php` y `Order.php`).
  - Ahora toma los valores reales configurados en la ficha comercial del cliente cuando la orden no posee un valor explícito de anulación.

## [1.10.314] - 2026-08-04

### Fixed
- **Error 500 en Impresión de Nota de Crédito / Devoluciones**:
  - Corregido `TypeError: Argument #1 ($title) must be of type string, null given` en `generateCreditNotePdf` (`PdfInvoiceTrait.php:1243`).
  - Añadida validación de nulos y nombres por defecto (`custom_name`, `product->name`, `product->sku`, `customer`, `user`) en la generación de comprobantes y notas de crédito en PDF para devoluciones con productos eliminados o ajustes de deuda.

## [1.10.313] - 2026-08-04

### Added
- **Botón PDF de Transacciones Diarias por Corte**:
  - Incorporado botón directo **`[PDF]`** en la columna **Acciones** de la tabla de **Cortes Diarios**.
  - Permite visualizar e imprimir al instante el reporte en PDF de todas las transacciones de esa jornada bancaria específica (incluyendo desglose de **DEBE**, **HABER**, **SALDO**, auditoría de arqueo y firmas).

## [1.10.312] - 2026-08-04

### Enhanced
- **Estructura Contable Tradicional Bancaria (DEBE, HABER y SALDO)**:
  - Reemplazada la columna única `MONTO` por el formato estándar de extracto bancario contable:
    - **DEBE (+)**: Columna exclusiva para depósitos e ingresos recibidos (en verde).
    - **HABER (-)**: Columna exclusiva para egresos, gastos y retiros (en rojo).
    - **SALDO (=)**: Columna de balance acumulado progresivo por cuenta y moneda.
  - Aplicado tanto en la tabla interactiva de movimientos como en el reporte oficial en PDF.

## [1.10.311] - 2026-08-04

### Fixed
- **Carga Completa de Bancos Activos en Modales de Tesorería**:
  - Corregido el selector desplegable de bancos en los modales de **Registrar Otro Ingreso**, **Registrar Gasto**, **Transferencias entre Bancos**, **Apertura de Banco** y **Corte Diario**.
  - Ahora todas las cuentas bancarias activas del sistema (ej: *Banco de Venezuela, Zelle, Banco Provincial, Mercantil, etc.*) cargan automáticamente en las opciones de selección independientemente del parámetro de auditoría inicial.

## [1.10.310] - 2026-08-04

### Added
- **Módulo de Registro Manual de "Otros Ingresos Bancarios"**:
  - Incorporado botón y modal de **"+ Otro Ingreso"** para registrar aportes de capital, rendimientos bancarios, devoluciones de proveedores, préstamos o abonos de terceros no vinculados a ventas directas.
  - Selección de categoría de ingreso, número de referencia, descripción de la actividad y comprobante adjunto.
  - Actualización automática del saldo conciliado de la cuenta bancaria.

- **Sistema de Apertura y Corte Diario Auditado (Sistema vs Operador)**:
  - **Apertura de Jornada Bancaria (Mañana)**: Modal donde el operador declara el saldo con el que amaneció la cuenta en el portal del banco (`manual_opening_balance`) y comprobante opcional.
  - **Corte Diario Auditado (Tarde/Noche)**: Modal de Cierre donde el operador ingresa el saldo final del banco real y **sube obligatoriamente la captura de pantalla del estado de cuenta bancario** (`closing_proof_image`).
  - **Arqueo y Conciliación Bancaria**: Cálculo automático del diferencial entre el saldo Teórico (Sistema) y el saldo Real (Operador), clasificando descuadres como Sobrante o Faltante.
  - **Reporte PDF de Auditoría Diario**: Desglose con comparativa Teórica vs Real, visualizador de capturas adjuntas y cuadro oficial de firmas de responsabilidad (*Operador vs Supervisor*).

## [1.10.309] - 2026-08-04

### Enhanced
- **Rediseño del Reporte PDF de Tesorería a Formato Extracto / Estado de Cuenta Bancario**:
  - **Tabla de Resumen de Flujo Financiero**: Se incorporó en la parte superior el resumen ejecutivo con el total de **Ingresos / Depósitos (+)**, **Egresos / Gastos (-)** y el **Flujo Neto del Período (=)** agrupado por divisa (VED, USD, COP, etc.).
  - **Fila de Totales en Pie de Tabla (`tfoot`)**: Se añadió al final de la tabla de movimientos el desglose de sumatorias destacadas por moneda con totales de entradas, salidas y resultado neto.
  - **Mejora de Formato Bancario**: Presentación con estilo de estado de cuenta tradicional de los bancos facilitando la conciliación y auditoría rápida para gerentes y contadores.

## [1.10.308] - 2026-08-04

### Fixed
- **Corregido Error 500 al Exportar PDF en Tesorería y Auditoría Bancaria**:
  - **Causa Raíz**: La ruta `reports.bank.treasury.pdf` apuntaba al método `bankTreasuryPdf` en `ReportController.php`, pero dicho método no existía en el controlador, lo que provocaba un error `500 Server Error` en el iframe modal de vista previa al presionar el botón "Exportar PDF".
  - **Solución**: Se implementó el método `bankTreasuryPdf` en `ReportController.php` conectando la plantilla `reports.bank-treasury-pdf` y procesando el filtrado por banco, rangos de fecha y pestañas (Cortes Diarios, Egresos, Transferencias y Movimientos Combinados).
  - **Pruebas**: Creada la prueba de integración `test_bank_treasury_pdf_report_generates_successfully` en `Tests\Feature\BankTreasuryTest.php` comprobando con 16 aserciones que el PDF se genera limpiamente con código HTTP 200.

## [1.10.307] - 2026-08-03

### Fixed & Enhanced
- **Mejoras al Servicio de Verificación de Actualizaciones (`UpdateService`)**:
  - **User-Agent y Timeout**: Agregado encabezado `User-Agent: JSPOS-Updater` y timeout de 10 segundos al consultar la API de GitHub Releases para evitar bloqueos de red.
  - **Sanitización de Versiones**: Normalizada la comparación de versiones eliminando prefijos `'v'` de forma segura y previniendo la duplicación del prefijo en la vista Blade (`update-system.blade.php`).
  - **Manejo de Errores y Logs**: Agregados registros de advertencia (`Log::warning`) en caso de respuestas no exitosas por parte de GitHub.

## [1.10.306] - 2026-08-03

### Fixed & Enhanced
- **Auto-Recuperación Silenciosa de Licencias ("Reverse Auto-Pull")**:
  - **Soporte Multi-Tienda en Misma IP**: Cuando múltiples tiendas comparten la misma IP de VPN (ej: `Arlenis Tovar` y `Cupcake`), la tienda cliente consulta automáticamente a `jspos-licencias` mediante su UUID (`client_system_id`).
  - **Auto-Activación Transparente**: Al detectar que la licencia local ha vencido o requiere renovarse, `CheckLicense` consulta al servidor de licencias en segundo plano, descarga su clave firmada y desbloquea el sistema sin mostrar la pantalla de bloqueo.

## [1.10.305] - 2026-08-03

### Fixed & Improved
- **Sincronización Automática en 1-Clic para Pantalla de Sistema Bloqueado**:
  - **Sincronización Vía VPN**: Agregado el método `sync()` en `LicenseController.php` y el botón **⚡ Sincronizar Licencia por VPN** en la pantalla de `license.expired`.
  - **Recuperación Tras Actualización**: Cuando una tienda cliente se actualiza y la tabla de licencias locales requiere actualizarse, con solo pulsar "Sincronizar Licencia" el sistema consulta al servidor `jspos-licencias` por VPN, obtiene la clave firmada activa y **desbloquea el sistema de inmediato** sin necesidad de copiar/pegar manualmente.

## [1.10.304] - 2026-08-03

### Added
- **Desinstalación Remota por Morosidad y Respaldo Automático de Base de Datos**:
  - **Servicio `WipeService`**: Nuevo servicio encargado de generar la copia de seguridad `.zip` comprimida de la base de datos MySQL y la plantilla de bloqueo `SISTEMA INHABILITADO POR INCUMPLIMIENTO DE CONTRATO`.
  - **Endpoint `POST /api/license/remote-wipe`**: Endpoint seguro autenticado por `LICENSE_SYNC_TOKEN` con validación estricta del `client_system_id` para garantizar que la orden solo desinstale la tienda objetivo.
  - **Middlewares `CheckInstalled` y `CheckLicense`**: Actualizados para detectar la marca de borrado `storage/wiped` y servir de forma fija la pantalla de inhabilitación bloqueando cualquier navegación.
  - **Pruebas Automatizadas**: Creada la prueba de integración `Tests\Feature\RemoteWipeTest.php` comprobando autenticación, coincidencia de client ID, generación de respaldo y bloqueo con 7 aserciones pasadas.

## [1.10.303] - 2026-08-03

### Added
- **Auto-Registro y Verificación En Línea de Licencias Vía VPN**:
  - **Paso 4 del Instalador Rediseñado**: Nueva interfaz en `install/license.blade.php` con pestañas para *Conexión Automática por VPN (ZeroTier / Tailscale)* y *Código Manual Tradicional*.
  - **Auto-Registro de Equipos**: Al colocar la IP del servidor de licencias en el instalador, la nueva tienda se registra automáticamente en el panel de licencias como *En Línea* y queda a la espera de la asignación del plan por el administrador.
  - **Endpoint de Salud `/api/license/ping`**: Nuevo endpoint que reporta el estado en línea y la versión del cliente para el monitoreo en tiempo real desde `jspos-licencias`.
  - **Controladores y Rutas AJAX**: Implementados los métodos `connectLicenseServer` y `checkLicenseStatus` en `InstallController.php`.
  - **Pruebas Automatizadas**: Creada la prueba de integración `Tests\Feature\LicenseAutoConnectTest.php` verificando el ping API y el flujo de conexión AJAX con 8 aserciones limpias.

## [1.10.302] - 2026-08-01

### Fixed
- **Persistencia de Precio Personalizado al Modificar Cantidad o Cambiar Moneda**:
  - **Causa Raíz**: Al incrementar la cantidad (`updateQty`), cambiar la moneda de la venta (`updatedInvoiceCurrencyId`), o recalcular el carrito (`recalculateCartPrices` / `recalculateCartWithSellerConfig`), el sistema recalculaba el precio base llamando a `$this->determinePrice()`. Debido a que los productos de precio variable tienen un precio base por defecto de `0.00` en la base de datos, el precio se restablecía a `0` perdiéndose el monto personalizado introducido por el vendedor.
  - **Solución**:
    - Se agregó el flag `'is_custom_price' => true` al ítem del carrito cuando se introduce un precio personalizado o producto variable.
    - Se actualizaron los métodos de recálculo `updateQty()`, `recalculateCartPrices()`, `recalculateCartWithSellerConfig()` y asignación en grupo de precios para preservar el precio base personalizado de moneda principal (`base_price`), recalculando correctamente los totales al cambiar la cantidad o al convertir de moneda (USD <-> VED/VES).
  - **Pruebas**: Se actualizó la prueba `Tests\Feature\SalesVariablePricePOSModalTest.php` comprobando exitosamente con 11 aserciones la persistencia del precio personalizado ante cambios de cantidad y conversión de divisas.

## [1.10.301] - 2026-08-01

### Fixed
- **Persistencia e Impresión del Nombre/Descripción Personalizada en Factura / Remisión**:
  - **Causa Raíz**: El nombre personalizado asignado durante la venta POS a productos variables o servicios no se guardaba dentro de los datos del detalle (`metadata`) ni se renderizaba en las facturas/remisiones PDF (`PdfInvoiceTrait.php` / `PdfOrderInvoiceTrait.php`), usando siempre el nombre base del producto en base de datos.
  - **Solución**:
    - Se modificó la persistencia de `SaleDetail` y `OrderDetail` en `app/Livewire/Sales.php` para guardar `'custom_name'` dentro de `metadata` cuando el nombre introducido difiera del nombre base del producto.
    - Se implementaron los accesores `$detail->custom_name` en `App\Models\SaleDetail` y `App\Models\OrderDetail` que retornan la descripción personalizada registrada o el nombre original como respaldo.
    - Se actualizaron los generadores de PDF (`PdfInvoiceTrait.php`, `PdfOrderInvoiceTrait.php`) y vistas Blade (`sale-detail.blade.php`, `order-detail.blade.php`) para mostrar el nombre personalizado de la factura/remisión.

## [1.10.300] - 2026-08-01

### Fixed
- **Corrección en Modal de Productos con Precio Variable / Servicios en Ventas (POS)**:
  - **Causa Raíz en JS**: En Livewire 3 el evento emitido `prompt-variable-price` envía un objeto directo en lugar de un arreglo, por lo que `event[0].productName` lanzaba un error `TypeError: Cannot read properties of undefined` en la consola del navegador impidiendo la apertura del SweetAlert modal.
  - **Causa Raíz en PHP**: El método receptor `$this->setVariablePriceAndAdd()` en `Sales.php` no estaba adaptado para extraer el objeto payload `{ price, customName }` enviado por Livewire 3.
  - **Solución**: Se actualizó `resources/views/livewire/pos/partials/script.blade.php` para extraer de manera segura `productName` sin errores de JS. Se actualizó el listener `setVariablePriceAndAdd()` en `Sales.php` para asignar correctamente el precio y la descripción personalizada al carrito.
  - **Pruebas**: Se creó la prueba de integración `Tests\Feature\SalesVariablePricePOSModalTest.php` pasando limpiamente con 6 aserciones.

## [1.10.299] - 2026-08-01

### Fixed
- **Corrección en Guardado de "Precio Variable (Servicios/Diseños)" en Productos**:
  - **Causa Raíz**: El atributo `is_variable_price` no estaba incluido en la propiedad `$fillable` de la clase `App\Models\Product`. Debido a la protección contra asignación masiva de Eloquent, la opción "Precio Variable" se descartaba silenciosamente al guardar o actualizar un producto.
  - **Solución**: Se agregó `is_variable_price` a `$fillable` y `$casts` en `App\Models\Product.php`. Ahora la opción se guarda correctamente en base de datos.
  - **Pruebas**: Se creó la prueba de integración `Tests\Feature\ProductVariablePriceTest.php` para validar la persistencia correcta.

## [1.10.298] - 2026-07-31

### Added
- **Persistencia Offline y Guardado de Borrador en App Fábrica de Bolsas (`mobile_bolsas_app v1.0.6`)**:
  - **Auto-Guardado de Borrador de Levantamiento (`draft_production_bolsas`)**: Guarda automáticamente los lotes de producción cargados por el operador (cantidades, pesos, rollos variables, operador y observaciones) en la memoria interna del teléfono. Si la app se cierra o el teléfono se apaga, los datos cargados permanecen intactos.
  - **Cola de Envíos Offline (`pending_offline_production_bolsas`)**: Si el operador envía el levantamiento sin señal o falla la red, el lote se almacena de forma segura en el dispositivo con aviso: `📱 SIN CONEXIÓN: Lote de producción guardado en el teléfono. Se enviará automáticamente al reconectarse.`
  - **Carga Inmediata 0ms de Catálogo de Bolsas (`cached_products_bolsas`)**: Apertura instantánea del catálogo de productos y tipos de bolsas desde el almacenamiento local sin congelamientos por falta de internet.
  - **Auto-Login Persistente e Inicio Offline**: Permite abrir la app de producción y operar localmente aun sin señal.
  - **Banner de Estado y Botón de Sincronización Manual (`🔄`)**: Muestra `📡 MODO OFFLINE (SIN CONEXIÓN)` con el contador de lotes pendientes `📦 X lote(s) pendientes por enviar`, permitiendo sincronización manual con un toque.

## [1.10.297] - 2026-07-31

### Added
- **Migración de Arquitectura Offline y Persistencia Completa a la App Cliente VIP (`mobile_vip_app v1.1.8`)**:
  - **Eliminación del Límite Backend (50 Productos)**: Removido `->limit(50)` en `app/Http/Controllers/Api/Vip/ProductController.php` para entregar el catálogo completo de productos a los clientes VIP.
  - **Carga Instantánea 0ms desde Caché Local**: La app abre los productos instantáneamente desde `SharedPreferences` (`cached_products_vip`) sin esperar la respuesta de red.
  - **Auto-Login Persistente e Inicio de Sesión Offline**: El token se mantiene guardado en el teléfono permitiendo abrir el panel sin internet. Si el servidor está inalcanzable, la app permite acceso en modo offline.
  - **Motor de Búsqueda Offline en Tiempo Real y Multi-Palabra**: Búsqueda local de 0ms por nombre o SKU que filtra coincidiendo múltiples palabras clave.
  - **Persistencia de Borrador de Pedidos**: Los artículos agregados al carrito y las observaciones se conservan si la app se cierra o reinicia.
  - **Cola de Pedidos Offline (`pending_offline_orders_vip`)**: Si el cliente envía una pre-orden sin conexión, esta se guarda de forma segura en el teléfono y se reenvía al conectar.
  - **Banner de Estado y Botón de Sincronización Manual (`🔄`)**: Muestra `📡 MODO OFFLINE` únicamente cuando está sin red o con órdenes pendientes, permitiendo sincronización manual con un toque.

## [1.10.296] - 2026-07-31

### Fixed
- **Auto-Detección Automática de Red y Ocultamiento Inteligente de Banner (App Vendedor Foráneo v1.1.43)**:
  - **Auto-Actualización de Estado de Red**: Se corrigió el flujo para que `_fetchProducts()` y `_fetchCustomers()` actualicen `_isOnline = true` automáticamente al recibir `200 OK` del servidor sin requerir presionar el botón sincronizar.
  - **Ocultamiento Limpio de Banner**: Cuando la app está conectada a internet y no hay pedidos pendientes por enviar, la barra verde se oculta automáticamente para dejar la pantalla completamente limpia.
  - **Aviso Naranja Exclusivo para Offline/Pendientes**: El banner naranja `📡 MODO OFFLINE` aparece únicamente cuando el dispositivo está sin señal o cuando hay pre-órdenes locales pendientes de sincronización.

## [1.10.295] - 2026-07-31

### Added
- **Indicador de Conexión en Tiempo Real y Botón Manual de Sincronización (App Vendedor Foráneo v1.1.42)**:
  - **Banner de Estado Superior**: Muestra una barra visible superior que indica en tiempo real si el vendedor está `🟢 EN LÍNEA` o `📡 MODO OFFLINE (SIN CONEXIÓN)`.
  - **Contador de Pedidos Pendientes**: Si el vendedor guardó pre-órdenes offline, la barra muestra el contador exacto (ej: `📦 2 pedido(s) guardado(s) pendiente(s) por enviar`).
  - **Botón de Sincronización Manual (🔄)**: Ubicado en la barra superior (AppBar) y en el banner. Al tocarlo o hacer pull-to-refresh en la lista:
    1. Verifica la conexión con el servidor.
    2. Envía automáticamente todos los pedidos guardados offline al servidor de la oficina.
    3. Descarga la versión más fresca del catálogo completo (537 productos y 674 clientes).

## [1.10.294] - 2026-07-31

### Fixed
- **Eliminación del Límite de 50 Productos en la API para Caché Móvil Completo**:
  - **Causa**: La API de Laravel (`Api\ProductController::index`) tenía un límite estricto `->limit(50)`, por lo cual la app móvil sólo recibía y guardaba los primeros 50 productos de la base de datos (ignorando los otros 487 productos activos).
  - **Solución**: Se removió el límite de 50 ítems en la API para el catálogo completo de ventas. Ahora la app recibe y almacena localmente los 537 productos completos en la memoria del teléfono para búsqueda offline 100% precisa.

## [1.10.293] - 2026-07-31

### Fixed
- **Carga de Catálogo Offline Instantánea (0ms) y Búsqueda Multipalabra (App Vendedor Foráneo v1.1.41)**:
  - **Carga Inmediata desde Caché (0ms)**: Al abrir la pantalla de Catálogo sin conexión, la app lee inmediatamente el catálogo en caché guardado sin esperar timeouts de red de 8-15 segundos.
  - **Failover a 3 Segundos**: Los intentos de actualización por red pasan a segundo plano con un tiempo de espera de 3s sin bloquear la pantalla ni vaciar los productos en pantalla.
  - **Búsqueda Inteligente Multipalabra**: La búsqueda ahora permite buscar múltiples términos en cualquier orden (ej: "vaso 8" o "vaso plastico").

## [1.10.292] - 2026-07-31

### Fixed
- **Buscador Offline en Tiempo Real y Apertura Offline Instantánea (App Vendedor Foráneo v1.1.40)**:
  - **Eliminación de Dependencia Web de Fuentes**: Se eliminó la descarga HTTP de Google Fonts para evitar bloqueos/esperas de renderizado al abrir la app completamente sin internet.
  - **Auto-Login Robusto en Primer Frame**: Envolvió la redirección de sesión guardada dentro de `addPostFrameCallback` para garantizar que la app abra instantáneamente sin internet.
  - **Motor de Búsqueda Offline en Tiempo Real**: Se implementó filtrado in-memory con `_allProducts` y `_filterProducts()` conectado al evento `onChanged` de la barra de búsqueda. Ahora al escribir cualquier letra o SKU, los productos se filtran inmediatamente (0ms de retraso) tanto online como offline.

## [1.10.291] - 2026-07-31

### Added
- **Auto-Login Persistente y Modo Offline sin Internet (App Vendedor Foráneo v1.1.39)**:
  - **Auto-Inicio de Sesión**: Si el vendedor ya inició sesión previamente en la app, al abrir la aplicación sin conexión a internet entra directamente al panel sin pedir volver a autenticarse.
  - **Fallback de Autenticación Offline**: Si la app está en la pantalla de login sin señal, al presionar "ENTRAR" valida el token previamente guardado permitiendo acceso inmediato a la app en modo offline.

## [1.10.290] - 2026-07-31

### Added
- **Caché Offline Completo de Clientes y Productos (App Vendedor Foráneo v1.1.38)**:
  - **Caché Local Automatizado**: Al estar conectado a internet o ZeroTier, la app guarda en caché la lista completa de clientes y productos.
  - **Funcionamiento 100% Offline**: Si el vendedor se encuentra sin internet/cobertura, la app carga automáticamente los clientes y productos desde el almacenamiento local, permitiendo buscar clientes, consultar catálogo, armar carritos y guardar pre-órdenes totalmente offline.

## [1.10.289] - 2026-07-31

### Added
- **Persistencia Local de Borradores y Modo Offline (App Vendedor Foráneo)**:
  - **Auto-Guardado del Borrador**: Los productos agregados, cliente seleccionado y observaciones en el pedido se guardan automáticamente en la memoria local del teléfono (`SharedPreferences`). Si el vendedor minimiza la app, responde una llamada o la app se cierra, al reabrir el pedido se recupera intacto.
  - **Cola de Pedidos Offline**: Si el vendedor intenta enviar la orden a la oficina sin conexión a internet, la app guarda el pedido localmente en el teléfono notificando al vendedor. Al recuperar la conexión o abrir la sección de pedidos, se sincronizan automáticamente.

## [1.10.288] - 2026-07-31

### Improved
- **Íconos de Colores en la Botonera de Acciones POS**: Se agregaron íconos temáticos de colores (`icofont`) para todos los botones de la barra de acciones (Ordenes, Cancelar, Abonos, Ticket y Factura) brindando una apariencia moderna y consistente.

## [1.10.287] - 2026-07-31

### Added
- **Botones de Impresión Dual en POS (Ticket 80mm vs Factura Carta PDF)**: Se separó el botón *"Última"* en la barra de herramientas del POS en dos acciones independientes:
  - **🎟️ Ticket**: Imprime el ticket térmico de 80mm directamente en la impresora POS mediante el Spooler.
  - **📄 Factura**: Abre la factura PDF en tamaño carta en una pestaña del navegador con el diálogo de impresión listo para enviarse a impresoras de red (ej. Ricoh MP 2554).

## [1.10.286] - 2026-07-31

### Fixed
- **Impresión Directa Garantizada y Escape de Cadenas en PowerShell**: Se restauró la invocación directa de `$this->printSale($sale->id)` al finalizar una venta y se corrigió el escape de comillas/rutas en PowerShell en `CustomWindowsPrintConnector.php`.
  - **Causa Raíz**: El comando en segundo plano no mantenía las cookies de sesión del navegador (`device_token`), lo que provocaba que se omitiera la configuración de la impresora autorizada del dispositivo. Adicionalmente, el escapado de barras invertidas en cadenas de PowerShell hacía que el Spooler fallara silenciosamente.
  - **Solución**: Al imprimir directamente de forma síncrona mediante el Spooler ultrarrápido Win32 y corregir el formateo de PowerShell, los tickets se imprimen físicamente de manera instantánea y 100% confiable tanto al cerrar la venta como al presionar el botón "Ticket Venta".

## [1.10.285] - 2026-07-31

### Fixed
- **Soporte Unicode `OpenPrinterW` para Impresoras de Red UNC en Windows**: Se actualizó el Spooler de impresión en `CustomWindowsPrintConnector.php` para utilizar las llamadas Unicode `OpenPrinterW` y `StartDocPrinterW`.
  - **Causa Raíz**: La API anterior `OpenPrinterA` (ANSI) retornaba el error Win32 1801 (`ERROR_INVALID_PRINTER_NAME`) al recibir nombres de impresoras de red compartidas UNC como `\\Desktop-cp7495d\POS80`.
  - **Solución**: La migración a la API Unicode `OpenPrinterW` resuelve correctamente los nombres de impresoras de red compartidas en Windows 10/11 y envía los datos crudos ESC/POS a la cola de impresión de red física.

## [1.10.284] - 2026-07-31

### Fixed
- **Integración Nativa con el Servicio de Impresión de Windows (Win32 Print Spooler API)**: Se actualizó `CustomWindowsPrintConnector.php` para utilizar las llamadas a la API de Spooler nativa de Windows (`OpenPrinter`, `StartDocPrinter`, `WritePrinter`).
  - **Causa Raíz**: Los métodos anteriores basados en comandos `copy` o `file_put_contents` fallaban con errores de permisos o rutas no encontradas cuando PHP se ejecutaba en entorno web/CGI al enviar datos a impresoras locales o de red (`\\DESKTOP-CP7495D\POS80` y `\\192.168.20.105\pos80`).
  - **Solución**: La API nativa de Windows Spooler entrega la ráfaga de comandos ESC/POS directamente al administrador de colas de impresión de Windows para cualquier impresora instalada, garantizando la impresión física inmediata.

## [1.10.283] - 2026-07-31

### Fixed
- **Soporte Completo para Impresoras de Red UNC (`\\EQUIPO\IMPRESORA`) y Puertos LPT en Windows**: Se mejoró `CustomWindowsPrintConnector` para parsear automáticamente rutas de red compartidas (`\\COMPUTER\SHARE`), sanitizar nombres de puertos con dos puntos (`LPT1:`) y ejecutar reconexiones vía `net use`.
  - **Causa Raíz**: Las rutas UNC que iniciaban con `\\` eran tratadas por error como archivos locales (`isLocal = true`), intentando escribir directamente con `file_put_contents`, lo cual arrojaba `Permission denied` en PHP sobre Windows.
  - **Solución**: Se separó el hostname y sharename para reconectar el flujo de red por SMB/Spooler, y se añadió fallback automático a puerto `LPT1` en caso de requerir reescritura directa.

## [1.10.282] - 2026-07-31

### Fixed
- **Sintaxis de Lanzamiento de Impresión en Segundo Plano en Windows**: Se corrigió el comando ejecutor en `Sales.php` (`storeSale`), modificando la posición del argumento de título de `START /B ""` a `START "" /B`. En la consola CMD de Windows, la posición anterior provocaba que el proceso en segundo plano de impresión fallara en silencio al facturar.

## [1.10.281] - 2026-07-31

### Fixed
- **Restauración de Órdenes/Ventas y Apagado Estricto de Flete**: Se corrigió un bug en `loadSaleToEdit` y `loadOrderToCart` donde al cargar una venta u orden previa creada con comisiones, el sistema leía `applied_freight_percent > 0` e inicializaba simultáneamente `applyFreight = true`. Esto causaba que al apagar el interruptor `Aplicar Comisiones`, el flete quedara activo internamente ($175.2615) en lugar de limpiar el precio base ($165.3410).
  - **Solución**: Se actualizó la carga de ventas y órdenes para discriminar si la factura fue emitida con comisiones (`applied_commission_percent > 0`), asegurando que `applyFreight` se establezca en `false`. Asimismo, los manejadores `updatedApplyCommissions` y `updatedApplyFreight` ahora limpian de forma explícita ambos interruptores al apagar cualquiera de los controles.

## [1.10.280] - 2026-07-31

### Fixed
- **Desactivación de Comisiones y Retorno a Precio Base Limpio**: Se corrigió una falla en `Sales.php` por la cual al desactivar el control `Aplicar Comisiones`, el sistema activaba automáticamente en segundo plano el flete (`applyFreight = true`). Esto causaba que el monto total mostrara un recargo del 6% de flete ($175.2615) en lugar de retornar al precio base limpio ($165.3410).
  - **Causa Raíz**: En las funciones de hidratación y selección de cliente (`setCustomer`), la rama `else` ejecutaba una asignación forzada (`$this->applyFreight = $this->config->sales_show_freight ? true : false;`), activando el flete cuando la comisión se apagaba.
  - **Solución**: Se eliminó la asignación forzada en el `else`, asegurando que apagar `Aplicar Comisiones` mantenga `applyFreight` en `false`, garantizando el cálculo exacto sobre la suma base pura del carrito.

### Added
- **Test de Regresión para Desactivación de Comisiones**: Se incorporó la prueba `test_toggling_off_commissions_does_not_auto_enable_freight` en `SalesControlsMutualExclusivityTest` (6/6 tests pasando).

## [1.10.279] - 2026-07-31

### Fixed
- **Impresión Automática de Tickets de Venta**: Se solucionó el problema por el cual los tickets de factura no se imprimían automáticamente al completar una venta cuando estaba activo el módulo de optimización del POS (`module_pos_optimizations`).
  - **Causa Raíz**: En `Sales.php`, para no bloquear la respuesta HTTP, el sistema llamaba en segundo plano al comando `php artisan pos:print-sale {id}`. Sin embargo, dicho comando de Artisan no existía en el proyecto, provocando que la ejecución fallara en silencio.
  - **Solución**: Se creó el comando Artisan `App\Console\Commands\PrintSaleCommand` (`pos:print-sale`), el cual ejecuta el proceso de impresión térmica utilizando `PrintTrait`.
  - **Compatibilidad CLI**: Se protegieron los llamados a `$this->dispatch()` en `PrintTrait` para prevenir errores al ejecutarse fuera del contexto de Livewire.
  - **Soporte Ejecutable PHP**: Se añadió resolución inteligente de `PHP_BINARY` para entornos CGI (`php-cgi.exe` -> `php.exe`) y soporte multiplataforma (Windows/Linux).

### Added
- **Test de Impresión en Segundo Plano**: Se creó `PrintSaleCommandTest` para verificar la ejecución correcta del comando `pos:print-sale`.

## [1.10.278] - 2026-07-30

### Fixed
- **Exclusividad Mutua entre Controles de Comisión y Flete**: Se corrigió la lógica de los controles del POS para garantizar que `Aplicar Comisión` y `Aplicar Solo Flete` sean mutuamente excluyentes. Al activar uno, el otro se desactiva automáticamente.
- **Fórmula de Precio Integral con Comisión**: La fórmula con `Aplicar Comisión` activo ahora calcula correctamente: `(Base + Comisión% + Recargo% + Flete%) × (1 + Diferencial%)`. Ejemplo: base $10 + comm $0.50 + recargo $0.40 + flete $0.60 = $11.50, luego se aplica el diferencial cambiario del 30% → $11.50 × 1.30 (**donde 1.30 = 1 + 30%**) = **$14.95**.
- **Fórmula Solo Flete**: Con `Aplicar Solo Flete` activo, el precio resulta únicamente `Base + Flete%` sin aplicar comisión, recargo ni diferencial cambiario.

### Added
- **Suite de Pruebas Automatizadas (Feature Tests)**: Se creó `SalesControlsMutualExclusivityTest` con 4 pruebas de integración que validan empíricamente la lógica de negocio: exclusividad mutua, fórmula comisión (14.95), solo flete (10.60) y precio base sin controles (10.00). Todos los tests pasan satisfactoriamente.

## [1.10.277] - 2026-07-29

### Fixed
- **Cálculo de Precios en AddProduct (Bug Crítico)**: Se corrigió un error grave donde el método `AddProduct` en el POS tenía su propia fórmula de cálculo inline que **no incluía el Recargo (base_markup_percent)** y **no aplicaba redondeo intermedio**. Esto causaba que los totales fueran incorrectos al agregar productos al carrito. Se reemplazó la lógica duplicada con una llamada al método `Calculator` unificado, que es el mismo usado por `updateQty` y `recalculateCartWithSellerConfig`, garantizando consistencia en todos los caminos de cálculo (Comisión + Recargo + Flete + Diferencial con redondeo correcto).

## [1.10.276] - 2026-07-29

### Added
- **App VIP Clientes (`v1.1.7+70`)**: Se portaron las mejoras de pagos multimoneda a la aplicación de clientes VIP:
  - Soporte multimoneda dinámico (VED, COP, USD, EUR) con conversión automática del monto según la divisa del instrumento seleccionado.
  - Recálculo en tiempo real del monto y equivalencia en USD al cambiar fecha, banco o tasa.
  - Bloqueo de edición manual en la tasa de cambio (campo no editable, solo lectura del sistema).
  - Selector de tasa cuando existen múltiples opciones para una misma fecha (BCV, Binance, Paralelo).
  - Indicador verde de "Equivalente a pagar" en USD visible cuando la moneda seleccionada no es dólares.
  - Restricción de fechas futuras en el calendario de abonos.

## [1.10.275] - 2026-07-29

### Fixed
- **Redondeo de Facturación**: Se unificó y corrigió el redondeo de los componentes en los cálculos de facturas (comisión, flete, recargo y diferencial cambiario). Ahora el sistema redondea cada término individualmente a los decimales configurados antes de realizar sumas intermedias y aplicar la diferencia cambiaria, alineándose con el cálculo esperado.

## [1.10.274] - 2026-07-29

### Added
- **Módulo de Abonos (Cuentas por Cobrar)**: Se incorporó la columna **Por Bajar** (Abono Pendiente) en la tabla del módulo de Abonos a Cuenta:
  - Muestra una etiqueta resaltada (`badge-warning`) con el icono de reloj y el monto exacto en dólares de abonos/pagos notificados desde la app móvil que aún están pendientes por bajar/aprobar.
  - En la columna **Debe**, se muestra un indicador explicativo que indica el saldo que restaría al cliente una vez aprobado dicho abono pendiente (`Restaría: $XX.XX`).

## [1.10.273] - 2026-07-29

### Changed
- **API Ventas / Tasas de Cambio**: Se ajustó la consulta de tasas Binance para la App Móvil:
  - Se eliminaron de los desplegables las tasas con ajuste ("Binance Ajustada"). Únicamente se muestran las tasas reales.
  - Se renombró la etiqueta visual de "Binance Real" a simplemente "Binance" (ej: `845.68 Bs. (Binance - PM)`).

## [1.10.272] - 2026-07-29

### Added
- **API y App Vendedor Foráneo**: Mejoras integrales en la pantalla de abonos de la aplicación móvil (`v1.1.35+67`):
  - Soporte multimoneda dinámico (VED, COP, USD, EUR) en la app móvil.
  - Conversión automática del monto inicial según la divisa seleccionada y tasa del día (`Monto = Deuda * Tasa`).
  - Recálculo en tiempo real del monto en moneda local y su equivalencia exacta en Dólares ($ USD) al cambiar la fecha o seleccionar diferentes tasas del día.
  - Bloqueo de edición manual en la tasa de cambio con selector deslizable cuando existen múltiples tasas en una misma fecha.
  - Restricción de selección de fechas futuras en el calendario de abonos.

## [1.10.271] - 2026-07-29

### Added
- **Configuración**: Se agregó la opción "Auto-seleccionar cliente genérico en ventas" en Ajustes. Permite decidir si el cliente "Consumidor Final" se carga automáticamente al abrir el punto de venta. Por defecto, esta opción está apagada para forzar la selección manual.

### Changed
- **Reportes**: Se eliminó el auto-cargado del cliente seleccionado en POS al entrar a los reportes. Ahora los reportes iniciarán con el buscador vacío.

## [1.10.270] - 2026-07-26

### Added
- **Configuración**: Se agregaron 5 nuevos interruptores en el módulo de Configuraciones (Pestaña Ventas) para controlar la visibilidad de los siguientes elementos en la vista del cajero:
  - Selector de "Tienda Principal" (Depósito).
  - Selector de "Chofer" (Módulo Delivery).
  - Control de "Aplicar Comisiones".
  - Control de "Aplicar Solo Flete".
  - Control de "Desglosar Flete".
- A petición, estos controles vienen **ocultos por defecto** para limpiar aún más la pantalla del cajero. Quienes los necesiten, deben encenderlos explícitamente en el panel de Configuraciones.

## [1.10.269] - 2026-07-26

### Added
- **Configuración**: Se agregó un nuevo switch en el panel de Configuraciones ("Mostrar etiqueta de Tasa de Cambio (Ventas)") que permite ocultar el globo flotante que dice "Tasa: 900.00" cuando se factura en una moneda distinta a la principal. Por defecto viene activado para mantener la compatibilidad anterior, pero ahora puede apagarse a petición del cliente para limpiar aún más la interfaz del cajero.

## [1.10.268] - 2026-07-26

### Added
- **Diseño / Interfaz**: Se agregó una función de `auto-zoom` (alejamiento inteligente) que detecta monitores y laptops con pantallas intermedias o pequeñas (entre 992px y 1400px de ancho) y reduce automáticamente la escala visual al 90%. Esto garantiza que el módulo de ventas y otros componentes pesados se ajusten a la perfección sin amontonar los botones ni desbordar la pantalla.

## [1.10.267] - 2026-07-26

### Changed
- **Diseño / Interfaz**: Se modificó la configuración de visualización por defecto para todas las instalaciones nuevas y usuarios sin preferencias guardadas. Ahora el sistema viene de fábrica con la barra lateral cerrada (`Sidebar Collapsed` y `Sidebar Mini`), menú lateral compacto (`Nav Compact`), y tanto el encabezado como el pie de página fijos. Esto soluciona los problemas de visualización en pantallas de menor resolución (como laptops) asegurando que el módulo de ventas tenga suficiente espacio sin descuadrarse.

## [1.10.266] - 2026-07-26

### Fixed
- **Clientes**: Se corrigió un error en instalaciones nuevas (con modo estricto de MySQL) que impedía crear clientes desde el módulo de clientes, debido a que los campos de comisiones vacíos (`customer_commission`) se enviaban como cadenas vacías (`""`) en lugar de `null` a columnas de tipo numérico.

## [1.10.265] - 2026-07-26

### Fixed
- **Licencias**: Se agregó una migración faltante para la columna `client_name` en la tabla `licenses` que causaba que la activación de la licencia fallara silenciosamente en los clientes no actualizados.

## [1.10.263] - 2026-07-26

### Fixed
- **Licencias**: Se agregó una migración faltante de base de datos para la columna `license_request_phone` en la tabla `configurations` que causaba errores al guardar ajustes generales en instalaciones de clientes.

## [1.10.262] - 2026-07-26

### Changed
- **Licencias**: La notificación de WhatsApp para solicitudes de renovación ahora se envía silenciosamente a través del servicio interno (`WhatsappService`) en lugar de abrir una nueva pestaña del navegador con WhatsApp Web.

## [1.10.261] - 2026-07-26

### Added
- **Licencias**: Se incluyó el ID del cliente en la metadata interna del correo de "Solicitud de Renovación de Licencia" para permitir que el Servidor de Licencias procese la solicitud y notifique automáticamente a la oficina central.

## [1.10.260] - 2026-07-24

### Fixed
- **Estado de Cuenta (PDF)**: Se corrigió una discrepancia en el "Saldo Pendiente" total donde facturas pagadas, anuladas o devueltas se incluían erróneamente en el PDF pero no en la vista en pantalla (UI).

## [1.10.259] - 2026-07-23

### Added
- **Reporte Cobranza por Vendedor**: Se agregó la funcionalidad para dividir la cobranza proporcionalmente entre los departamentos LOCAL y GRAVADO.
- **Reporte Cobranza por Vendedor**: Se añadieron casillas de configuración visual para mostrar u ocultar el Monto Original, Tasa de Cambio y Equivalente USD.
- **Reporte Cobranza por Vendedor (PDF)**: Se agregó la opción para imprimir firmas de "Cajero" y "Supervisor" al final del reporte en PDF.

### Fixed
- **Reporte Cobranza por Vendedor**: Se corrigió el espaciado y cálculo de columnas (colspan) en el reporte de pantalla y PDF para evitar el desalineamiento visual en los totales cuando se ocultan ciertas columnas.

## [1.10.258] - 2026-07-23

### Added
- **Módulo de Ventas**: Se agregó la columna "Días Vencidos" en la ventana modal de "Facturas Pendientes", calculando y mostrando en tiempo real los días de retraso para facturas vencidas.
- **Reportes (PDF)**: Se incorporó la columna "Días Vencidos" en el reporte en PDF de "Facturas Pendientes de Pago" (Estado de Cuenta del Cliente) para mantener la consistencia con la vista en pantalla.

## [1.10.257] - 2026-07-23

### Changed
- Mejoras visuales en el título principal de las páginas (breadcrumbs).
- Se implementó la asignación de iconos dinámicos para cada sección de la plataforma (Ventas, Productos, Clientes, Reportes, Configuraciones, etc).
- Se limpiaron los textos redundantes y variables de sesión cruzadas que mostraban etiquetas erróneas en los títulos de vistas.
- Corrección de iconos en módulos como Finanzas y Producción para que armonicen visualmente con el menú lateral principal.

## [1.10.256] - 2026-07-22
### Added
- **Módulo Clientes**: Se añadieron insignias (badges) visuales en la tabla de clientes para mostrar la configuración crediticia (Límite, Plazo, Descuento) y un resumen dinámico del estado de la deuda para clientes morosos (total vencido y días de retraso).

### Fixed
- **Evaluación Crediticia**: Se corrigió el algoritmo de "Cliente Nuevo" que ocultaba el historial real y la morosidad de un cliente con crédito activo si no cumplía el requisito inicial de compras de contado.
- **Formulario de Clientes**: Se resolvió un error de base de datos que impedía guardar los cambios cuando el usuario dejaba en blanco el campo de límite de crédito, convirtiéndolo correctamente a un valor nulo.

## [1.10.255] - 2026-07-22
### Added
- **POS**: Se añadió visualización de notas de orden en el Resumen (Checkout), mostrando las instrucciones del vendedor.

### Fixed
- **Creación de Folios API**: Se corrigió el problema de folios duplicados donde los vendedores foráneos desde la API y el cajero web pisaban los mismos números de orden, al sincronizar la API con el contador global de configuración del sistema.

### Changed
- **POS Sugerencias**: La tarjeta de Tendencias y Sugerencias de Compra ahora inicia colapsada y utiliza estado persistente de AlpineJS para evitar cerrarse durante los refrescos de Livewire.

## [1.10.254] - 2026-07-21
### Added
- **Reporte de Desempeño de Vendedores: Filtros de Facturas en PDF**:
  * Se añadieron nuevos filtros para limitar el historial de ventas mostrado en la exportación a PDF (Últimas 100, Todas, o Ninguna) y para filtrar las facturas por su estatus (Todas, Pendientes, Pagadas), brindando mayor control sobre la generación del documento y permitiendo ahorrar páginas de impresión cuando no se requiere el detalle.

### Fixed
- **Reporte Recuperación PDF**:
  * Se corrigió un problema donde la dirección del cliente no se estaba imprimiendo en el documento PDF de "Reporte de Recuperación de Clientes Inactivos", a pesar de que la columna estuviera seleccionada en la configuración visual.

## [1.10.253] - 2026-07-17
### Changed
- **Auditoría de Diferencial Cambiario: Evaluación Neta de Rentabilidad con Cojín de Recargo (Opción A)**:
  * Se implementó el cálculo y evaluación del diferencial neto para facturas con acuerdo `BCV` y recargo por diferencial configurado.
  * El estado de auditoría en la tabla y en el PDF ahora evalúa si el recargo cobrado absorbe exitosamente la brecha cambiaria (`Diferencial Neto = Diferencial Directo + Cojín Facturado >= -0.01`).
  * Cuando el cojín es suficiente, la fila se califica en verde como `Cojín Eficiente` (o `Cumple`), evitando falsas alertas de fuga de capital por el diferencial directo negativo de la tasa BCV vs Binance.
  * Si la devaluación supera el recargo configurado (`Diferencial Neto < -0.01`), se marca en rojo como `Fuga Real (Cojín Insuficiente)`.
  * La columna de Diferencial en la tabla web y PDF ahora muestra como monto principal el resultado neto e incluye un desglose claro (`Directo: -X.XX (+Cojín Y.YY)`).

## [1.10.252] - 2026-07-17
### Changed
- **Dashboard Estratégico: Etiquetas de Semanas con Rango de Fechas Legibles**:
  * Las etiquetas del gráfico de tendencia semanal ahora muestran el rango de fechas exacto de cada semana (ej. `27/05-02/06`) en lugar del número de semana ISO (ej. `Sem 22`), facilitando la lectura e interpretación de los datos históricos.

## [1.10.251] - 2026-07-17
### Added
- **Dashboard Estratégico: Gráfico de Tendencia Lineal de Crecimiento**:
  * Se añadió un segundo gráfico de tipo línea (`Highcharts line`) al lado del gráfico de barras existente en el tab de crecimiento del Dashboard Estratégico.
  * El nuevo gráfico muestra la **evolución histórica** de Ventas Netas y Utilidad Neta usando los datos de `$linearTrend`, permitiendo identificar visualmente si la empresa está creciendo, estancada o decreciendo.
  * La fila de gráficos pasó de una sola columna (`col-md-12`) a dos columnas (`col-md-6`): "Detalle del Periodo Seleccionado" y "Gráfico de Crecimiento y Tendencia Lineal".

### Changed
- **Dashboard Estratégico: Etiquetas de Comparación Dinámicas en KPIs**:
  * Las tarjetas KPI de "Ventas Netas" y "Utilidad Neta Real" ahora muestran etiquetas de comparación dinámicas (`{{ $prevLabel }}` / `{{ $yearAgoLabel }}`) en lugar del texto fijo "vs mes anterior" / "vs año anterior".
  * Las etiquetas se adaptan automáticamente al rango seleccionado (ej. "vs día anterior", "vs semana anterior", "vs mes anterior", "vs trimestre anterior", "vs año anterior").

### Fixed
- **Dashboard Estratégico: Corrección de Método Eliminado en Cálculo de Patrimonio**:
  * Se corrigió `estimateEquityDeltaForMonths()` que llamaba al método eliminado `calculatePeriodMetrics()`. Ahora utiliza correctamente `calculateRangeMetrics($start, $end)` con fechas de inicio/fin de mes calculadas dinámicamente.
- **Tests: Tolerancia de Precisión Flotante en OPEX**:
  * Se corrigió el test `consolidates bank expenses in opex` cambiando `assertEquals(450.00)` por `assertEqualsWithDelta(450.00, ..., 0.01)` para manejar correctamente imprecisiones de punto flotante en sumas monetarias.

## [1.10.250] - 2026-07-15
### Changed
- **Búsqueda Flexible Multi-Tag (OR) en Reporte de Inventario**:
  * Se optimizó la consulta del buscador en `InventoryReport` y `ReportController::inventoryPdf` para implementar una lógica de búsqueda flexible (`OR`) entre múltiples etiquetas.
  * Esto permite que buscar términos como "soplados pet" devuelva todos los productos asociados con el tag "soplados" O con el tag "pet" simultáneamente, evitando listas vacías producto del comportamiento estricto `AND` anterior.
  * Se mantiene la búsqueda estricta (`AND`) para campos de texto planos (Nombre, SKU, Categoría) para garantizar la relevancia de los resultados.
  * Se actualizó la suite de pruebas unitarias `InventoryReportFilterTest` para cubrir la búsqueda combinada multi-tag.

## [1.10.249] - 2026-07-15
### Added
- **Búsqueda por Tag/Etiqueta en Reporte de Inventario**:
  * Se modificó la consulta del buscador tanto en el componente Livewire `InventoryReport` como en el controlador PDF `ReportController` para incluir la relación `tags` utilizando `orWhereHas('tags')`.
  * Esto permite buscar términos asignados como etiquetas a los productos (por ejemplo, "soplados", "pet", "tapas") desde la barra de búsqueda de Inventario.
  * Se añadieron casos de prueba a la suite `InventoryReportFilterTest` para verificar el correcto funcionamiento de la búsqueda por etiquetas.

## [1.10.248] - 2026-07-15
### Added
- **Filtro de Productos e Insumos / Materias Primas en Reporte de Inventario**:
  * Se integró una nueva opción de filtrado ("Filtro de Inventario") en la columna lateral del módulo "Reporte de Inventario / Stock" para permitir al usuario alternar entre visualizar "Solo Productos" (donde `is_raw_material = false`), "Solo Insumos / Materia Prima" (donde `is_raw_material = true`) y "Todos".
  * Se configuró "Solo Productos" como la opción seleccionada por defecto para no alterar el comportamiento histórico del módulo.
  * Se adaptó la lógica de selección de elementos individuales/todos, el renderizado de la tabla y la exportación a formato PDF en `ReportController::inventoryPdf` para heredar este criterio de filtrado dinámicamente.
  * Se añadieron pruebas unitarias completas en `InventoryReportFilterTest` para validar los comportamientos de filtrado y parámetros de PDF correspondientes.

## [1.10.247] - 2026-07-14
### Changed
- **Formato Limpio y Remoción de Emojis en Notificaciones de WhatsApp y Correo**:
  * Se removieron todos los emojis (como 📄, 🏢, 📅, 📝, 📊, 📦, ⚙️, 🔍, 📍, 💵, 💳, 📈, 💰, 📥) de las notificaciones enviadas a WhatsApp y correos electrónicos del sistema (tasas de cambio, cierres de turno de soplados, reportes semanales de ingresos, reportes consolidados semanales de soplados y cierres diarios de ventas).
  * Se formatearon los títulos y subtítulos clave con negritas de WhatsApp (`*texto*`) y saltos de línea dobles para una presentación clara, limpia y muy legible.
  * Se actualizaron las pruebas de notificaciones de WhatsApp correspondientes para validar la salida estructurada con negritas y sin emojis.

## [1.10.246] - 2026-07-14
### Fixed
- **Optimización de Memoria en Exportación de Base de Datos para Copias de Seguridad (Rollback)**:
  * Se modificó la función `exportDatabaseSql` en `UpdateService` para utilizar flujos de archivos nativos de PHP (`fopen`, `fwrite`, `fclose`) al generar respaldos SQL de la base de datos antes de las actualizaciones.
  * Esto sustituye la acumulación previa del dump completo en un único string de memoria PHP (que causaba desbordamientos de memoria RAM "Allowed memory size exhausted" y dejaba el asistente de actualización colgado indefinidamente en 10% "Creando copia de seguridad...") por una escritura directa a disco de muy bajo consumo de recursos, garantizando la velocidad y estabilidad del proceso en producción con bases de datos de gran tamaño.

## [1.10.245] - 2026-07-14
### Changed
- **Detalle de Facturas Vencidas en Notificación de Crédito**:
  * Se modificó `requestCreditAuthorization` para adjuntar una lista desglosada de las facturas vencidas del cliente moroso (número de factura, fecha de vencimiento, días de retraso y monto pendiente por cobrar) en el cuerpo del mensaje de autorización de crédito.
  * Se eliminaron los emojis de la notificación y se dio formato a los títulos con negritas de WhatsApp (`*texto*`) para una presentación más formal y legible en WhatsApp y correo electrónico.
  * Se mejoró la generación del asunto en el correo para asociar la variable del PIN directamente de forma limpia.

## [1.10.244] - 2026-07-14
### Fixed
- **Exclusión Exclusiva del Botellón de Segunda Surtido en Inventario (Soplados)**:
  * Se refinó la exclusión del `InventoryController` para que solo oculte de la lista de conteo de la app los productos de segunda calidad que se deriven de productos de tipo botellón (como `BOTELLON 18.9LTS 2DA SURT.`), manteniendo los productos de segunda calidad de los envases PET visibles e independientes para conteo individual si así se requiere.

## [1.10.243] - 2026-07-14
### Fixed
- **Exclusión de Productos de Segunda Calidad Destino en Inventario (Soplados)**:
  * Se modificó `InventoryController` para excluir todos los productos marcados como destino de segunda calidad (`second_quality_product_id`) de los listados de inventario (`index` y `productsForCount`) de la app de soplados.
  * Esto previene que se muestren tarjetas de conteo individuales redundantes o fuera de lugar (como `BOTELLON 18.9LTS 2DA SURT.` o `ENVASE DE SEGUNDA PET 330ML`), garantizando que en Soplados solo se cuenten y reporten por color u original, y la acumulación en el producto de segunda se realice tras bambalinas.

## [1.10.242] - 2026-07-14
### Fixed
- **Autovinculación y Autoetiquetado de Productos de Segunda Calidad (Soplados)**:
  * Se modificó `SopladosSecondQualityLinkerSeeder` para buscar productos de segunda calidad en la base de datos basándose únicamente en el nombre, sin filtrar inicialmente por la etiqueta `soplados`.
  * Se agregó autoetiquetado automático de modo que si encuentra el producto homólogo de segunda calidad (creado manualmente por el usuario), le asocia la etiqueta `soplados` automáticamente en el sistema y luego realiza el enlace. Esto soluciona el problema por el cual los campos de segunda calidad desaparecían de la app al no contar previamente con la etiqueta en base de datos.

## [1.10.241] - 2026-07-14
### Fixed
- **Validación y Bloqueo de Crédito solo por Facturas Vencidas**:
  * Se corrigió `CreditConfigService` para que el bloqueo de crédito y la posterior solicitud de PIN de supervisor ocurra únicamente cuando el cliente tenga facturas vencidas activas (sin saldar).
  * Se eliminó el bloqueo automático por defecto para clientes nuevos o sin límite de crédito personalizado asignado, permitiendo que operen con el límite heredado (vendedor o global) sin requerir PIN si no están en mora.

## [1.10.240] - 2026-07-14
### Fixed
- **Seeder `SopladosSecondQualityLinkerSeeder` actualizado para PET individuales**:
  * Cada medida PET (330ml, 500ml, 1000ml, 1500ml, Galón) se vincula a su propio producto de segunda calidad por separado, a diferencia de los botellones 18.9L que comparten un único producto de 2da.
  * Mapeo correcto: 330ML → `ENVASE DE SEGUNDA PET 330ML`, 500ML → `ENVASE DE SEGUNDA PET 500ML`, 1000ML → `ENVASES DE SEGUNDA PET 1000ML`, 1500ML → `ENVASES DE SEGUNDA PET BULTO 1500ML`, Galón → `ENVASE DE SEGUNDA PET GALON 3.785`.

## [1.10.239] - 2026-07-14
### Added
- **Campo `second_quality_product_id` en Productos (Inventario Soplados)**:
  * Nueva migración que agrega `second_quality_product_id` a la tabla `products`, separando la lógica de agrupación de colores/variantes (`production_target_id`) de la lógica de producto homólogo de segunda calidad.
  * Nuevo seeder `SopladosSecondQualityLinkerSeeder` que vincula automáticamente los botellones 18.9L (todos los colores) al producto `BOTELLON DE 2DA`, y los PET (330ml, 500ml, 1000ml, 1500ml, galón) al producto `PET DE 2DA` si existe. Idempotente: seguro de ejecutar múltiples veces.
  * El seeder se ejecuta automáticamente en cada actualización del sistema (`UpdateService`).

### Fixed
- **Campo "2da Calidad" no aparecía en inventario para PET 330/500/1000/1500/Galón**:
  * El `InventoryController` ahora usa `second_quality_product_id` para determinar si un producto tiene homólogo de segunda calidad, en lugar de `production_target_id`. Esto permite que los envases PET muestren la sección de "2da Calidad" en la app de inventario una vez que el producto `PET DE 2DA` esté creado y vinculado.
  * La lógica de `storeCount` y `acceptCount` fue actualizada con el mismo campo para guardar y aplicar correctamente el stock de segunda calidad al aceptar el inventario.

## [1.10.238] - 2026-07-13
### Changed
- **Desglose de Calidades en Tabla de Rendimiento por Turno (Reporte Soplados)**:
  * La tabla "Rendimiento de Producción por Turno" ahora muestra tres columnas de cantidad separadas por color: **1ra Cal.** (verde), **2da Cal.** (naranja) y **Merma** (rojo), en lugar de una única columna que sumaba 1ra y 2da juntas.
  * Las columnas de 2da Calidad y Merma muestran un guión (`-`) cuando no hay unidades de ese tipo, mejorando la lectura del reporte.
  *44565563'¿ Aplica tanto al PDF enviado por correo/WhatsApp (`SendSopladosWeeklyReport`) 4como al descargado manualmente desde la web (`ProductionReport`).

## [1.10.237] - 2026-07-13
### Changed
- **Preservación de Días de Crédito en Bypass por PIN**:
  * Se corrigió `CreditConfigService` para que retorne los días de crédito configurados originalmente (del cliente, vendedor o global) en lugar de `0` al resolver la configuración de un cliente con crédito bloqueado. Esto asegura que si el bloqueo es puenteado vía código PIN, la venta se guarde y la factura se imprima con sus días de crédito correspondientes en lugar de salir como "CONTADO / Sin días de crédito".

## [1.10.236] - 2026-07-13
### Added
- **Campos de Scoring en Cliente**:
  * Se agregaron las columnas `credit_score`, `credit_status`, `credit_limit_recommended`, `last_credit_scoring_at` al array `$fillable` del modelo `Customer` para soportar actualizaciones de scoring.
### Changed
- **Desbloqueo Dinámico del Crédito de Clientes**:
  * Modificada la resolución de la configuración del crédito en `CreditConfigService` para que los clientes bloqueados históricamente (`defaulted`) puedan volver a comprar a crédito dinámicamente una vez paguen todas sus facturas de crédito vencidas.
- **Flujo de Caja en Ventas a Crédito**:
  * Corregido el modelo `Sale` para excluir los métodos de pago `'CREDITO'` de los montos iniciales pagados, evitando que las ventas a crédito sean marcadas falsamente como pagadas de contado.
- **Registro Correcto de Ventas a Crédito**:
  * Modificado el guardado de venta en el panel POS (`Sales.php`) para no insertar registros de pago ficticios en la base de datos para créditos y guardar el estado y tipo de venta correctamente como pendiente (`pending`) y a crédito (`credit`).

## [1.10.235] - 2026-07-13
### Added
- **KPI de Segunda Calidad en Rendimiento Semanal**:
  * Se agregó una tarjeta KPI dedicada en la sección "Rendimiento Consolidado de la Semana" del PDF para mostrar por separado el total de unidades producidas de segunda calidad (2da Calidad).
  * Rediseñada la distribución del bloque KPI a 5 columnas (Primera Calidad, Segunda Calidad, Merma, Total Procesado y Rendimiento) para mantener el formato equilibrado.

## [1.10.234] - 2026-07-13
### Changed
- **Desagrupación Visual de Variantes en Tablas del Reporte**:
  * Modificada la sección de producción semanal ("Total Producido por Envase") y el rendimiento por turno ("Rendimiento de Producción por Turno") para listar cada color o variante de envase en su propia fila con su respectiva cantidad producida (sin agruparlos bajo el nombre del producto base representador).
  * La validación de metas de producción por turno ahora suma internamente las cantidades de todas las variantes de una misma familia durante el turno (por ejemplo, amarillo + morado), mostrando el cumplimiento (%) y estatus correspondiente para cada variante de forma individual basándose en el total combinado de la familia.

## [1.10.233] - 2026-07-13
### Changed
- **Vinculación Automática de Variantes de Productos**:
  * Se agregó lógica automática en `SopladosProductionTargetSeeder` para relacionar y vincular las variantes existentes en la base de datos (por ejemplo, botellones de colores y galón con/sin asa) con su producto base representador, asegurando la correcta consolidación de metas en los reportes de rendimiento por turno sin requerir configuración manual por parte del cliente.

## [1.10.232] - 2026-07-13
### Added
- **Validación de Stock en Despacho de Traspasos**:
  * Implementada validación en tiempo real para bloquear los despachos de traspasos si el depósito origen no cuenta con suficiente stock disponible, previniendo valores de inventario negativos.
### Changed
- **Seguridad en Seeding de Metas**:
  * Modificado el seeder de metas base de soplados (`SopladosProductionTargetSeeder`) para utilizar `firstOrCreate` en lugar de `updateOrCreate`. Esto previene que una actualización automática del sistema sobrescriba las metas de producción personalizadas por el usuario.

## [1.10.231] - 2026-07-13
### Added
- **Módulo de Metas de Producción de Soplados**:
  * Nuevo panel CRUD completo para gestionar las metas de producción mínima y máxima por envase para los turnos de Fábrica Soplados.
  * Nuevo botón web "Descargar Reporte Soplados (PDF)" en el historial de reportes de producción, permitiendo exportar el reporte consolidado de cualquier rango de fechas.
### Changed
- **Consolidación y Agrupación por Envase Base**:
  * El cálculo del rendimiento por turno y del reporte consolidado semanal de Soplados ahora agrupa automáticamente las variantes (con/sin asa, diferentes colores) bajo su producto meta representador (vinculado mediante `production_target_id`).
- **Separación estricta de depósitos**:
  * Los movimientos de materia prima se clasifican y muestran por destino (PLANTA SOPLADOS vs ZONA).
- **Estabilidad de Envíos de Reportes**:
  * Cambiado el envío del reporte semanal en PDF por correo electrónico de encolado a envío síncrono directo para garantizar que el archivo PDF adjunto se procese y entregue siempre sin problemas de persistencia.

## [1.10.230] - 2026-07-10
### Added
- **Autorización de Crédito vía PIN (Bypass)**:
  * Nuevo flujo para solicitar autorización de crédito cuando el cliente está moroso o no cumple las políticas.
  * Modal en el POS para solicitud a supervisores.
  * Envío automático de PIN temporal (15 min) vía Correo y WhatsApp a los autorizadores configurados.
  * Validación de PIN en tiempo real para autorizar la factura específica en proceso.
- **Módulo de Capacidad de Pago y Score Crediticio de Clientes**:
  * Nuevo algoritmo inteligente para calcular score crediticio de 0-100 y recomendación automática de límite de crédito en base a compras previas y puntualidad en pagos.
  * Análisis e interpretación tipo "Inteligencia Artificial" (IA) visual en la pestaña "Estudio de Crédito" en ficha del Cliente.
  * Bloqueo automático en el POS al tratar de realizar ventas a crédito a clientes nuevos ('new') sin límite manual, o clientes caídos en mora/default ('defaulted').
  * Leyendas informativas en el sistema que explican a los operadores (y a los clientes) la política de compras mínimas de contado ("Cupo Semilla").

## [1.10.229] - 2026-07-10
### Fixed
- **Listado de Productos en App de Bolsas**:
  * Modificada la API de producción de bolsas para que retorne todos los productos pertenecientes a la categoría "BOLSAS", solucionando el problema donde algunas bolsas (por ejemplo, variantes "Baja") no se listaban en la app móvil debido a la falta de etiquetas.

## [1.10.228] - 2026-07-09
### Added
- **Reportes Semanales y Cierres de Turno en Soplados**:
  * Implementado envío automático de PDF adjunto de cierre de turno de Soplados por Email y WhatsApp a grupos y usuarios individuales configurados.
  * Agregada opción de descarga de PDF del reporte de turno directamente desde el historial de turnos (Listado de Soplados).
  * Diseñado y programado el Reporte Semanal Consolidado de Soplados, que incluye el último inventario físico cargado al sistema.
  * Agregada configuración en el panel de WhatsApp para seleccionar qué grupos y usuarios de WhatsApp reciben el reporte de turno y el reporte semanal consolidado.
  * Añadida planificación dinámica en `Console/Kernel` para configurar el día de la semana y la hora de envío de los reportes semanales desde la interfaz.

## [1.10.227] - 2026-07-09
### Added
- **Eliminación de Pedidos Pendientes desde el Listado**:
  * Agregado un botón de eliminar (ícono papelera roja) en la pantalla de "Listado de Compras" exclusivo para compras en estado "PENDIENTE", permitiendo limpiar órdenes huérfanas o duplicadas de forma directa y segura.

## [1.10.226] - 2026-07-09
### Fixed
- **Carga de Stock en Pedidos Pendientes y Duplicidad de Folios**:
  * Removido el incremento inmediato de stock al guardar un pedido de compra en estado pendiente ("Guardar como Orden"). El stock ahora solo se actualiza al finalizarse.
  * Corregida la duplicidad de folios en la base de datos al procesar un pedido o requisición pendiente. Ahora el sistema actualiza el registro original (folio) en lugar de crear una compra nueva duplicada.
  * Añadida validación de colección segura en la lectura del carrito desde la sesión para prevenir fallas de tipo en peticiones concurrentes de Livewire.

## [1.10.225] - 2026-07-09
### Added
- **Notificaciones Adicionales (Correos y Contactos WhatsApp)**:
  * Agregado soporte para ingresar listas de correos electrónicos personalizadas para recibir notificaciones de tasa de cambio, cierre de caja diario y reporte semanal.
  * Implementado un buscador y gestor dinámico de usuarios en la configuración para seleccionar contactos específicos que reciban notificaciones de tasa, cierre y reporte semanal mediante WhatsApp.
  * Separados los canales de envío para garantizar que el correo electrónico siga funcionando incluso si WhatsApp se desconecta.

## [1.10.224] - 2026-07-09
### Fixed
- **Actualización del Sistema (Fallo Silencioso por Bloqueos)**:
  * Implementada parada automática de los servicios de Windows (`JSPOS_WhatsApp_API`, `JSPOS_Queue_Worker`, `JSPOS_Scheduler`) antes de la actualización para evitar archivos bloqueados.
  * Añadida copia con rastreo de fallos (`copyDirectoryWithTracking`) para detectar fallas al sobrescribir archivos y reportar error ruidoso en la interfaz.
  * Reactivación de los servicios de Windows después de la actualización.

## [1.10.223] - 2026-07-09
### Fixed
- **Carga de Grupos de WhatsApp en Configuración**:
  * Corregido el problema de desprendimiento de frames de Puppeteer (*detached Frame*) desactivando el aislamiento de sitios (`site-per-process`).
  * Implementado ciclo de vida dinámico del cliente y auto-sanación automática en caso de pérdida de contexto del navegador.
  * Optimizado el endpoint de cierre de sesión (`/logout`) para liberar bloqueos y borrar carpetas de sesión limpiamente.

## [1.10.222] - 2026-07-08
### Added
- **Notas Personalizadas en PDF de Lista de Precios**:
  * Agregado un campo textarea de "Nota Personalizada en PDF" en la interfaz del generador de listas de precios. Este texto se renderiza en un recuadro destacado de advertencia justo arriba de la tabla del PDF.
  * Configurada una nota predeterminada sobre la validez diaria de los precios basada en la fluctuación de tasas, evitando malentendidos con listas impresas días anteriores.

## [1.10.221] - 2026-07-08
### Added
- **Conversión de Tasas Binance / BCV en Lista de Precios**:
  * Agregada la opción "Habilitar Conversión Binance / BCV" y "Incluir Puntos de Ajuste (Incremento)" en la UI de generación de listas de precios. Esto permite calcular y mostrar precios en dólares inflados según la tasa Binance y reconvertidos a tasa oficial BCV, permitiendo al comercio cobrar en bolívares a la tasa oficial manteniendo el valor de reposición real del inventario.

## [1.10.220] - 2026-07-08
### Added
- **Configuración de Destinatarios de Administración (Fábrica de Bolsas)**:
  * Agregado el campo `bags_admin_email_recipients` en la base de datos y un nuevo control textarea en la pestaña de Ajustes de Producción para configurar los correos electrónicos específicos de la administración de la fábrica de bolsas.

### Changed
- **Redirección de Notificaciones de Levantamiento de Producción**:
  * Modificado el envío de la copia del levantamiento original desde la app móvil. Ahora se envía directamente al correo personal del operador registrado (`auth()->user()->email`) para servirle como comprobante digital de su jornada, liberando de correos individuales e innecesarios la bandeja de entrada de la gerencia general.
- **Auditoría Dual en Cargos de Bolsas**:
  * Modificada la aprobación de cargos de bolsas en la Web. Cuando se aprueban los cargos, el sistema envía un correo consolidado a la administración de bolsas que adjunta, en el mismo mensaje, tanto la planilla con los datos del levantamiento original del operador (Marca de agua: `ORIGINAL`) como la planilla con las cantidades definitivas ingresadas al inventario tras las correcciones del administrador (Marca de agua: `APROBADO`).

## [1.10.219] - 2026-07-07
### Fixed
- **Actualización Forzada de Scripts de Instalación (.bat)**:
  * Agregado paso explícito de copia y asignación de permisos de escritura a todos los archivos por lotes de Windows (`.bat`) durante el proceso de instalación de actualizaciones. Esto soluciona problemas en los que archivos de instalación antiguos de servicios o scripts de backup (como `instalar_servicios.bat`) no se sobrescribían con las nuevas versiones en los clientes, garantizando la correcta instalación de nuevos servicios como `JSPOS_Scheduler`.

## [1.10.218] - 2026-07-07
### Fixed
- **Validación de Stock de Último Momento (Facturación POS)**:
  * Implementada una validación final de stock en tiempo real en el método `Store()` de la facturación. Esta validación comprueba la disponibilidad física de los productos en el depósito seleccionado inmediatamente antes de procesar el pago y guardar la venta, previniendo sobrefacturaciones y saldos negativos de inventario provocados por condiciones de carrera o demoras al facturar con múltiples cajas abiertas de manera simultánea.

## [1.10.217] - 2026-07-07
### Added
- **Resúmenes en Tarjetas e Interpretador de Resultados IA en Análisis Estratégico**:
  * Implementados resúmenes flotantes (tooltips con cursor de ayuda) explicativos en las tarjetas KPI de las pestañas de Crecimiento Operativo y Patrimonio y Balance.
  * Añadido el botón "Analizar Resultados (IA)" y su respectivo modal interactivo de interpretación analítica, que calcula y expone un diagnóstico financiero dinámico del periodo y sugerencias/alertas para la toma de decisiones.

## [1.10.216] - 2026-07-06
### Added
- **Exclusión de Insumos/Materias Primas en Análisis Estratégico**:
  * Implementado filtro global en todas las consultas del Módulo de Análisis Estratégico (Ventas Netas, Devoluciones, COGS, Valor de Inventario, Clasificación ABC y Márgenes de Productos) para excluir los productos donde `is_raw_material` sea verdadero. Esto evita que los insumos y materias primas (como preformas, resinas, tapas, etc.) distorsionen los cálculos financieros del módulo debido a discrepancias en el ingreso de sus costos unitarios.

## [1.10.215] - 2026-07-06
### Fixed
- **Sincronización mediante Eventos Personalizados del Servidor**:
  * Resuelto el problema de desincronización y retraso de un paso en el renderizado de gráficos de Highcharts al cambiar de mes. Al cambiar la propiedad en Livewire, el evento se gatillaba en el cliente antes de recibir la respuesta del servidor, resultando en datos desfasados. Ahora, el controlador de Livewire despacha explícitamente el evento `chart-updated` tras actualizar los datos del servidor, y AlpineJS lo escucha mediante `@chart-updated.window` para redibujar el gráfico con `$nextTick()`.

## [1.10.214] - 2026-07-06
### Fixed
- **Vinculación Reactiva en AlpineJS mediante Entangle**:
  * Sustituido el observador directo `$watch('$wire.selectedMonth', ...)` por un enlace de propiedad nativo Alpine `@entangle('selectedMonth')` sincronizado con el backend de Livewire. Esto corrige el problema en el cual los gráficos no recibían la señal de cambio de mes y se quedaban mostrando el mes anterior (Mayo) cuando el usuario seleccionaba Junio.

## [1.10.213] - 2026-07-06
### Fixed
- **Actualización de Datos en los Gráficos de Highcharts**:
  * Corregido el problema donde la directiva `wire:ignore` evitaba que Livewire actualizara los datos dentro de las directivas Alpine al cambiar el mes seleccionado. Se trasladaron las propiedades de datos (`labels`, `sales`, `profit`, `equity`) a atributos de datos HTML (`data-`) en el contenedor padre (que no tiene ignore) y se implementó un observador reactivo Alpine `$watch('$wire.selectedMonth', ...)` junto con `$nextTick()`. Esto garantiza que los gráficos se redibujen con los datos correctos calculados del mes seleccionado en el backend (por ejemplo, Junio).

## [1.10.212] - 2026-07-06
### Fixed
- **Inicialización y Renderizado de Gráficos en Análisis Estratégico**:
  * Implementado un wrapper reactivo con AlpineJS (`x-data` + `x-effect` + `wire:ignore`) para los gráficos de Highcharts (`weeklySalesChart` y `equityTrendChart`). Esto garantiza que los gráficos se redibujen automáticamente y con los datos correctos cada vez que el usuario cambia de mes, corrigiendo el problema de los gráficos que se quedaban vacíos o sin cargar.

## [1.10.211] - 2026-07-06
### Added
- **Visualización de Comparativas en Análisis Estratégico**:
  * Agregadas comparaciones visuales con porcentajes y flechas de tendencia directamente en las tarjetas KPI para **Ventas Netas** y **Utilidad Neta Real**, mostrando los rendimientos con respecto al **mes anterior** y al **mismo mes del año anterior (YoY)**.

## [1.10.210] - 2026-07-06
### Fixed
- **Diseño del Menú Lateral**:
  * Corregida la estructura de etiquetas HTML en `sidebar.blade.php` restaurando la etiqueta `<ul class="nav nav-treeview">` en la sección de Centro de Reportes. Esto soluciona de inmediato el descuadre visual y los problemas de indentación del menú.

## [1.10.209] - 2026-07-06
### Added
- **Módulo de Análisis Estratégico y Crecimiento de Patrimonio**:
  * Diseñado e implementado el nuevo panel analítico avanzado que compara ventas netas, márgenes y utilidad neta mes a mes y año a año, con velocidades semanales.
  * Agregado sistema de registro de Gastos Operativos (OPEX) mensuales para deducir costos fijos de las ganancias.
  * Implementado cálculo en tiempo real de Patrimonio Neto Operativo (activos líquidos, mercancía a costo, cuentas por cobrar menos cuentas por pagar) y gráfico de tendencia de capitalización.
  * Agregada clasificación ABC de clientes (regla 80/20) y análisis de margen de productos.

## [1.10.208] - 2026-07-06
### Added
- **Decimales Configurables en Listas de Precios**:
  * Añadida la opción "Decimales a mostrar" en el Generador de Listas de Precios, permitiendo configurar de forma interactiva si se imprimen los precios con 2, 3 o 4 decimales en el PDF.
  * Agregada opción para guardar esta configuración de decimales como predeterminada del sistema desde el panel de administración.

## [1.10.207] - 2026-07-06
### Added
- **Filtro de Insumos y Materia Prima en Reporte de Rotación**:
  * Añadida la opción para filtrar el Reporte de Rotación por tipo de producto ("Solo Productos Terminados", "Solo Insumos / Materia Prima" o "Todos").
  * El filtro por defecto ("Solo Productos Terminados") oculta la materia prima e insumos (que tienen `is_raw_material = true`), corrigiendo de inmediato el cálculo inflado del capital en inventario e inmovilizado generado por registrar costos por millar/bulto.

### Fixed
- **Límites de tiempo en actualización del sistema**:
  * Añadida prevención de corte por límite de tiempo de ejecución de PHP (`set_time_limit(0)`) durante las fases de respaldo (backup), descarga, instalación y migraciones del actualizador del sistema.

## [1.10.206] - 2026-07-03
### Added
- **Secuencia y Ordenación Personalizada (Drag and Drop)**:
  * Añadido panel interactivo de reordenamiento de productos seleccionados mediante botones de subir/bajar y soporte completo de **arrastrar y soltar (Drag and Drop)** con AlpineJS.
  * La secuencia personalizada se aplica al PDF y Orden de Compra en el Reporte de Rotación, así como en los detalles de las órdenes generadas en el módulo de Requisición.
- **Control de Mermas de Soplado**:
  * Implementada migración para los campos de control de merma en la tabla de inventarios (`expected_merma_qty`, `real_merma_qty` y `diff_merma_qty`).
  * Implementado cálculo dinámico de merma esperada desde el último inventario de fábrica aprobado en el controlador de Inventarios.
  * Añadida visualización detallada de merma esperada, real y diferencia en el modal de detalles del inventario.
- **Selección de Columnas y Tarjetas KPI en PDF**:
  * Implementada la capacidad de elegir qué columnas y tarjetas de KPI incluir en la exportación de PDF y vista de pantalla, permitiendo ocultar todas las tarjetas.

### Changed
- **Separación de Stocks en Producción**: Corregida la separación de stocks de primera y segunda calidad en la producción de soplado.

### Fixed
- **Filtro de Productos en Matriz de Rotación**: Corregido bug donde la exportación en PDF y la generación de órdenes de compra ignoraban los productos seleccionados por casillas de verificación.


## [1.10.205] - 2026-07-03
### Changed
- **Ampliación de Horario de Respaldos**:
  * Modificado el rango horario en `app/Console/Kernel.php` para que los respaldos de base de datos se ejecuten cada 2 horas entre las 6:00 AM y las 10:00 PM (hora de Caracas).

## [1.10.204] - 2026-07-03
### Changed
- **Frecuencia de Respaldos de Base de Datos**:
  * Modificada la programación de respaldos de base de datos en `app/Console/Kernel.php` para que se ejecuten cada 2 horas entre las 6:00 AM y las 8:00 PM (hora de Caracas), en lugar de una sola vez al día. Esto restablece de forma nativa la misma frecuencia de respaldos que se tenía en el programador externo `ssfree.exe`.

## [1.10.203] - 2026-07-02
### Added
- **Botón de Cierre Manual en UI de Ventas Diarias**:
  * Añadido el botón "Enviar Cierre a WhatsApp" en la barra lateral del Reporte de Ventas Diarias. Al hacer clic, se ejecuta manualmente el cierre de caja para el día filtrado y se envía el resumen por WhatsApp a los grupos correspondientes.

## [1.10.202] - 2026-07-02
### Added
- **Ruteo Multigrupo Granular en WhatsApp**:
  * Implementadas casillas independientes en Ajustes de WhatsApp para seleccionar grupos para: "Tasa de Cambio", "Cierre Diario" y "Reporte Semanal PDF".
- **Cierre Diario Automático por WhatsApp**:
  * Creado comando `app:send-daily-closure` que calcula los totales de venta de contado, cobranzas, y ventas a crédito del día, enviando el resumen detallado de forma automática a los grupos seleccionados.
- **Reporte Semanal en PDF por WhatsApp**:
  * Creado comando `app:send-weekly-report` que compila el PDF del Reporte Semanal de Ingresos y lo despacha como archivo adjunto de WhatsApp a los grupos elegidos.
- **Servicio Planificador JSPOS_Scheduler (NSSM)**:
  * Añadida la instalación automática del servicio de Windows `JSPOS_Scheduler` que corre `artisan schedule:work` continuamente en segundo plano. Esto unifica y automatiza las tareas de respaldos de base de datos diarios (`backup:clean` y `backup:run`), cierres diarios (10:00 PM) y reportes semanales (Sábados a las 10:00 PM), eliminando la dependencia de `ssfree.exe` y scripts de procesamiento manuales.

## [1.10.201] - 2026-07-02
### Added
- **Configuración Multigrupo para Tasa de Cambio**:
  * Ahora el usuario puede seleccionar múltiples grupos en la tabla de grupos disponibles para recibir la notificación diaria de la tasa de cambio.
  * Si no se selecciona ningún grupo, el sistema continuará utilizando el comportamiento anterior (buscar por nombre de grupo "Diferencial").

### Fixed
- **Desconexión y Regeneración de QR**:
  * Corregido bug donde al desconectar la cuenta de WhatsApp el código QR se congelaba en estado de generación. Ahora el backend fuerza la destrucción del navegador Chromium, limpia los archivos de sesión localmente y re-inicializa el cliente desde cero, garantizando que el nuevo QR se cargue en segundos.

## [1.10.200] - 2026-07-02
### Added
- **Visualización de Grupos de WhatsApp en la Configuración**:
  * Añadida sección de "Grupos de WhatsApp Disponibles" al final del panel de Configuración de WhatsApp.
  * Muestra una tabla con todos los chats grupales activos detectados y sus identificadores únicos (JIDs).
  * Destaca visualmente el grupo "Diferencial" para que el usuario verifique al instante si el sistema lo tiene identificado.

## [1.10.199] - 2026-07-02
### Added
- **Notificación Automática de Tasa de Cambio por WhatsApp a Grupos**:
  * Añadida notificación automática al guardar las tasas de cambio globales en la configuración. El sistema formatea y envía de forma automática el reporte de tasas (BCV, Monitor, Diferencial y Sistema) al grupo de WhatsApp denominado "Diferencial".
  * El cálculo del Diferencial (Monitor / BCV) se realiza mediante truncamiento (floor) a 4 decimales para alinearse exactamente con el formato requerido.
  * Añadido soporte en la API de WhatsApp de Node (`whatsapp-api/index.js`) y en `WhatsappService` en Laravel para manejar JIDs de grupos (`@g.us`), incluyendo un endpoint para listar todos los grupos de forma dinámica.
  * Añadidas pruebas de integración automatizadas para verificar el flujo de notificación y formato de mensaje.

## [1.10.198] - 2026-07-02
### Fixed
- **Sincronización de Stock en Productos de Peso Variable (Bobinas)**:
  * Corregido bug donde el stock global del producto y el desglose de stock por almacén quedaban desalineados debido a ediciones manuales en el formulario y a diferencias de precisión (la tabla `products` almacenaba stock como entero y `product_warehouse` como decimal).
  * Cambiada la columna `products.stock_qty` a tipo `decimal(10, 2)` mediante una migración automática para alinearse con el tipo de columna del depósito y evitar redondeos no deseados.
  * Deshabilitada la edición manual del stock (global y por depósito) en el formulario para productos con "Venta por Peso/Separado (Bobinas)" (`is_variable_quantity`), calculando su stock actual de forma dinámica y automática a partir del peso de las bobinas físicas disponibles en `ProductItem`.
  * Sincronización automática de stock de todos los depósitos y el stock global al crear o actualizar el producto.
  * Añadidas pruebas de integración automatizadas para verificar el cálculo dinámico y la sincronización desde `ProductItem`.

## [1.10.197] - 2026-07-02
### Fixed
- **Conversión de Moneda en Cuentas por Cobrar y Cuentas por Pagar**:
  * Corregido bug matemático y de inconsistencia donde el modal de "Facturas Pendientes" del POS ocultaba facturas activas o calculaba incorrectamente sus abonos (debido a sumar la columna bruta `amount` de los pagos registrados en bolívares directamente, sin dividirlos por la tasa de cambio).
  * Corregida vulnerabilidad comercial crítica en el validador de límites de crédito (`CreditConfigService::validateCreditLimit`) que calculaba saldos deudores negativos para clientes con abonos en bolívares, permitiéndoles facturar crédito virtualmente ilimitado.
  * Corregido cálculo de deudas en los modelos `Sale` y `Purchase`, asegurando que `debt` se calcule dividiendo cada pago y abono por su tasa de cambio registrada.
  * Corregido el reporte en PDF de estado de cuenta de clientes (`customerDebtPdf` en `DataController`) para convertir abonos a USD con sus tasas y evitar ocultar facturas.
  * Corregida la visualización e inicialización de abonos y saldos de cuentas por pagar a proveedores en `AccountsPayableReport` y `PurchasePartialPayment`.
  * Añadidas pruebas de regresión e integración automatizadas cubriendo todos estos escenarios.

## [1.10.196] - 2026-07-01
### Fixed
- **Optimización de Copias de Seguridad en Actualizador**:
  * Corregido bug donde la exclusión de la carpeta `public/storage` no se aplicaba en sistemas Windows al resolverse la ruta real de los junctions (apuntando a `storage/app/public` en lugar de `public/storage`).
  * Añadidas exclusiones para archivos pesados no relacionados con el código fuente en las copias de seguridad de rollback (como paquetes instaladores de Android `.apk`, videos `.mp4`, archivos comprimidos `.zip`/`.rar` e imágenes estáticas `.png`/`.jpg`/`.svg`/`.webp`/`.ico` y archivos `.map`).
  * Reducido el tamaño de la copia de seguridad rollback en más del 80% y el tiempo de ejecución en más de 5 veces, previniendo que el actualizador se quede congelado o consuma demasiado espacio en disco.

## [1.10.195] - 2026-07-01
### Fixed
- **Conversión de Moneda en Reportes de Ventas**:
  * Corregido bug crítico donde los reportes de ventas (Matriz de Rotación, Actividad de Clientes, Más Vendidos, Estadísticas de Productos y Dashboard de Bienvenida) calculaban los montos en USD multiplicando la cantidad vendida directamente por el precio cobrado (`sale_price`), omitiendo que dicho precio se almacena en la moneda de la factura (ej. Bolívares/Pesos con tasas de cambio altas).
  * Ahora todas las sumas y métricas financieras de ventas se dividen por la tasa de cambio de la venta (`primary_exchange_rate`), convirtiendo todos los valores a USD de forma uniforme y previniendo márgenes y montos de facturación falsos/inflados en el sistema.
  * Añadidas pruebas unitarias y de integración automatizadas para validar la conversión de ventas con tasas de cambio.

## [1.10.194] - 2026-07-01
### Added
- **Agrupamiento Dinámico en Generador de Listas de Precios**:
  * Añadido selector en la interfaz para elegir cómo agrupar los productos en la lista de precios PDF: por Categoría (predeterminado), por Proveedor, por Etiqueta (Tag), o sin agrupación (lista continua).
  * Si se agrupa por Etiqueta, los productos se listan bajo cada una de sus etiquetas (o en "Sin Etiqueta" si no tienen).
  * Si se selecciona "Sin Agrupamiento", se omiten las filas de encabezados de sección para generar una tabla continua y limpia.
  * Los grupos en el PDF se ordenan alfabéticamente de manera automática.
  * Añadidas pruebas de validación automatizadas cubriendo los flujos de agrupación dinámica.

## [1.10.193] - 2026-07-01
### Added
- **Filtros Avanzados y Opciones al Vuelo en Generador de Listas de Precios**:
  * Implementada la capacidad de filtrar productos por Categoría, Proveedor (Supplier) y Tag desde la vista.
  * Añadida opción para filtrar la lista mostrando exclusivamente productos comprados previamente por el cliente seleccionado (basado en el historial de ventas reales del cliente, omitiendo ventas anuladas o canceladas).
  * Añadido interruptor para ignorar/omitir el cálculo de comisiones, fletes y diferenciales cambiarios en el PDF.
  * Permitido aplicar valores "al vuelo" directamente en la generación de la lista de precios (comisión, flete, recargo, mora, días de crédito y reglas de pronto pago) sin alterar las configuraciones permanentes del cliente/vendedor en la base de datos.
  
### Changed
- **Optimización de Limpieza Automática de Backups**:
  * Modificado el script `backup_cliente.bat` para ejecutar `php artisan backup:clean` de forma previa al proceso de respaldo local, garantizando que el mantenimiento y purga de backups antiguos se realice de forma automática y consistente.

### Fixed
- **Estabilidad de Suite de Pruebas**:
  * Corregida la falta del campo `'discount'` (sin valor por defecto) en la creación manual de `SaleDetail` dentro de la suite de pruebas.
  * Corregido mock de `Barryvdh\DomPDF\Facade\Pdf::loadView` para que retorne un mock de la clase tipada correcta `Barryvdh\DomPDF\PDF` en lugar de una clase anónima que disparaba errores de tipado (TypeError).

## [1.10.192] - 2026-06-30
### Fixed
- **Preservación y Recálculo de Pagos en POS**: Corrección de bug donde la reapertura del modal de cobros del POS limpiaba la lista de abonos del operador pero dejaba obsoleto el total cobrado. Ahora se preservan y recalculan dinámicamente con la tasa configurada (Binance/BCV) según los cambios de moneda de factura o toggle de comisiones.
- **Validación del Diferencial Cambiario**: Se corrigió el cálculo para que solo aplique la tasa BCV especial en la facturación y validaciones si la casilla de "Aplicar Comisión" está activa, ignorando recargos automáticos erróneos cuando no está aplicada.

## [1.10.191] - 2026-06-29
### Fixed
- **Notificación por Correo de Actualizaciones**: Restaurado el envío automático de notificaciones de correo electrónico a los destinatarios configurados al completarse con éxito una actualización del sistema. Se utiliza el mailable global `GenericNotificationMail` y se listan las novedades de la nueva versión del CHANGELOG.md en el cuerpo del correo.

## [1.10.190] - 2026-06-29
### Added
- **Clasificación Explícita de Insumos y Productos Terminado (Soplados)**:
  * Añadida la columna `is_raw_material` a la tabla `products` con migración de backfill automática para clasificar ingredientes previos como insumos.
  * Agregado interruptor "Es Insumo / Materia Prima (Soplados)" en el formulario de creación/edición de productos de la administración web.
  * Filtrado en el buscador del módulo de recetas para que "Producto Terminado" solo muestre productos no insumos y "Insumo" solo muestre insumos.
  * Refactorizada la API de Soplados (`InventoryController` y `ProductionController`) para clasificar dinámicamente y filtrar materias primas utilizando la columna explícita de base de datos.
  * Añadidas y validadas pruebas de integración para la búsqueda filtrada en Livewire y REST API.

## [1.10.189] - 2026-06-29
### Added
- **Edición de Recetas en Fábrica Soplados**: Añadida la posibilidad de editar ingredientes, cantidades y combinaciones de recetas (fórmulas) directamente desde la interfaz web de Soplados (Livewire component y Blade view).

## [1.10.188] - 2026-06-29
### Added
- **Módulo de Inventario Físico de Soplados con Control Cruzado (Supervisor-Operario)**:
  * Creadas las tablas `soplados_inventories` y `soplados_inventory_details` con sus respectivos modelos Eloquent, relaciones y casts.
  * Implementados endpoints de API REST para la aplicación móvil: listado de productos activos e insumos de fórmulas (`GET /api/soplados/inventory/products`), registrar conteo del supervisor en estado pendiente (`POST /api/soplados/inventory`), consulta de pendientes (`GET /api/soplados/inventory/pending`), aceptación con ajuste automático de stock (`POST /api/soplados/inventory/{id}/accept`) e historial (`GET /api/soplados/inventory/history`).
  * Creado el componente Livewire y vista Blade `SopladosInventoriesList` para el panel administrativo web, enlazado en el menú lateral de "Fábrica Soplados".
  * Integradas las pantallas móviles en Flutter (`SupervisorInventoryFormScreen`, `OperatorConformityScreen`, `InventoryHistoryScreen`) en `main.dart` con cálculos en tiempo real y peticiones API.
  * Añadida la suite de pruebas unitarias y de integración en `SopladosInventoryTest.php`, con 4 casos de prueba validados con éxito (100% PASS).
- **Compilación de App de Soplados v1.17.0+10**:
  * Compilada de forma optimizada la aplicación móvil de Soplados mediante `flutter build apk --release --split-per-abi`, y copiado el archivo de 64 bits a la raíz del proyecto renombrado como `JSPOS_Mobile_Soplados_v1.17.0_AppManufactura_SuWeb.apk`.

## [1.10.187] - 2026-06-26
### Fixed
- **Optimización de Memoria en Descarga de Logs (Evitar allowed memory size exhausted)**:
  * Corregido un fallo crítico de agotamiento de memoria PHP en producción cuando los administradores descargaban archivos de log de gran tamaño.
  * Reemplazado el disparador Livewire asíncrono (que serializaba la respuesta binaria dentro del payload JSON de Livewire) por un enlace HTML directo `<a>` que invoca una ruta GET estándar.
  * Creada la ruta `/system/logs/download` protegida bajo los middlewares `auth`, `can:settings.update` y `module:module_updates`. Esta ruta retorna un `BinaryFileResponse` nativo de Laravel, permitiendo transmitir en streaming (chunk-by-chunk) archivos de cualquier tamaño con consumo de memoria constante y cercano a cero.
  * Actualizada la suite de pruebas automatizadas en `UpdateRollbackTest.php` para validar el comportamiento y encabezados HTTP del nuevo endpoint de descarga directa, manteniendo una tasa de éxito de pruebas del 100% (10/10 PASS).
  * Eliminados del repositorio archivos temporales de respaldo obsoletos que causaban interferencia con la suite de pruebas.

## [1.10.186] - 2026-06-26
### Added
- **Visor y Administrador de Logs Seguro para Administradores**:
  - Implementado visor de logs seguro en el panel de actualizaciones para permitir a los administradores diagnosticar problemas en servidores de producción sin acceso FTP/SSH.
  - Implementado lector eficiente de logs (`getLatestLogLines`) en `UpdateService` utilizando lectura en bloques (chunk-based) desde el final del archivo en tiempo constante y sin sobrecargar la memoria de PHP.
  - Agregado método `clearLog()` para vaciar y truncar el archivo `laravel.log` para liberar espacio en disco, llamando a `clearstatcache()` para consistencia de estado del sistema.
  - Agregados métodos `loadLogs()`, `clearLogs()` y `downloadLogs()` en el componente Livewire `UpdateSystem` para cargar de forma asíncrona, vaciar y exportar logs completos en formato de descarga.
  - Diseñada sección visual de **Bitácora de Errores (Logs)** en la vista Blade `update-system.blade.php` con un `<textarea>` estilo terminal oscuro de solo lectura, botones de recarga, descarga y limpieza rápida guiada por SweetAlert v1.
- **Suite de Pruebas Automatizadas (TDD)**:
  - Añadidos 4 casos de prueba robustos en `UpdateRollbackTest.php` para validar la lectura de logs en bloques, la limpieza de archivos, la carga, la limpieza y descarga asíncrona del componente Livewire.
  - Todas las pruebas de logs validadas al 100% con éxito (10/10 PASS).

## [1.10.185] - 2026-06-26
### Fixed
- **Actualización sin bloqueos en Windows (sys_get_temp_dir)**:
  * Modificada la descarga de actualizaciones para usar el directorio temporal del sistema operativo y un nombre de archivo único por intento (`temp_update_[uniqid].zip`), evitando conflictos de permisos y bloqueos de archivos en entornos Windows de producción.
- **Robustez en la Copia de Seguridad**:
  * Corregido un fallo en `zipDirectories` que intentaba añadir enlaces simbólicos de directorios (como `public/storage`) como archivos, provocando un error de "Permission denied" al cerrar el archivo ZIP y congelando la pantalla al 10%.
  * Añadida validación de tipo en la vista Blade (`is_array($rollbacks)`) para evitar excepciones `TypeError` si la variable de rollbacks es nula debido a la caché de OPcache.

## [1.10.184] - 2026-06-26
### Fixed
- **Diálogos de confirmación en Puntos de Restauración**:
  * Corregido el uso incorrecto de la API de SweetAlert2 (`Swal.fire()`) cuando el proyecto usa SweetAlert v1 (`swal()`). Los botones "Restaurar" y "Eliminar" no mostraban ningún diálogo al hacer clic.
  * Reescritas las funciones `confirmRollback()` y `confirmDeleteRollback()` usando la API correcta de SweetAlert v1 con `dangerMode: true` y textos en español.

## [1.10.183] - 2026-06-26
### Added
- **Sistema de Rollback de Actualizaciones (Puntos de Restauración)**:
  * Implementado sistema completo de backup y restauración antes de cada actualización automática del sistema.
  * `UpdateService` genera automáticamente un punto de restauración en `storage/backups/antes_de_vX.X.X` antes de instalar cualquier actualización, incluyendo un dump SQL nativo de la base de datos (`database_backup.sql`) y un ZIP de los directorios críticos (`files_backup.zip`).
  * El mecanismo de poda automática mantiene únicamente los **3 puntos de restauración más recientes** para evitar el consumo excesivo de disco.
  * Panel de **Puntos de Restauración** en la interfaz de Sistema de Actualizaciones con tabla de versiones, fecha, tamaño, y botones de **Restaurar** y **Eliminar**.
  * Diálogos de confirmación con **SweetAlert2** antes de ejecutar la restauración (acción irreversible).
  * Restauración completa: extrae el ZIP de archivos sobre la raíz del proyecto y restaura el SQL sentencia por sentencia con `FOREIGN_KEY_CHECKS=0` para máxima compatibilidad.
  * `importDatabaseSql` refactorizado para ejecutar sentencias SQL individualmente, evitando fallas por multi-statement en drivers MySQL PDO.
- **Suite de Pruebas Automatizadas (TDD)**:
  * Creada la prueba `UpdateRollbackTest.php` con 6 casos de prueba cubriendo: exportación SQL, compresión ZIP, creación de backup, mecanismo de poda, restauración de archivos y gestión vía componente Livewire.
  * Uso de `RefreshDatabase` + mock de `importDatabaseSql` para evitar contaminación de estado de BD entre suites (MySQL DDL auto-commit).
  * Todas las pruebas validadas con éxito (6/6 PASS en 17.96s).

## [1.10.182] - 2026-06-26
### Added
- **Protección de Reportes Analíticos Avanzados (SaaS)**:
  * Agregado el middleware `'module:module_advanced_reports'` a las rutas de los 5 reportes analíticos avanzados (y sus respectivos PDFs) en `routes/web.php`.
  * Protegidos los enlaces a estos reportes en el menú lateral (`sidebar.blade.php`) usando la directiva Blade `@module('module_advanced_reports')`.
- **Restricción de Dispositivos (SaaS Limit Enforcer)**:
  * Implementada la validación de `max_devices` en `DeviceManager::approve()` impidiendo aprobar dispositivos si se alcanza o supera el límite de la licencia, mostrando un mensaje de error tipo `msg-error`.
  * Modificado el middleware `CheckDeviceAuthorization` para forzar el estatus de nuevos dispositivos a `'pending'` (en lugar de auto-aprobarlos) si el límite `max_devices` de la licencia actual ya se cumplió, incluso en modo de acceso abierto.
- **Suite de Pruebas Automatizadas (TDD)**:
  * Creada la prueba `SaasModulesAndLimitsTest.php` en `tests/Feature` cubriendo la protección de accesos sin licencia, la restricción de aprobaciones por límite en `DeviceManager`, y el comportamiento del middleware con nuevos dispositivos cuando se excede la cuota de la licencia.
  * Todas las pruebas validadas con éxito (100% PASS).

## [1.10.181] - 2026-06-26
### Added
- **Selección de Clientes y PDF Filtrados**:
  * Añadida columna con checkboxes en la tabla del **Reporte de Clientes** (`customer-report.blade.php`) junto con una casilla de selección global en el encabezado.
  * Implementadas las propiedades `$selectedCustomerIds` y `$selectAll` en `CustomerReport.php` con sincronización bidireccional automática.
  * Al presionar "Consultar", el sistema preselecciona automáticamente todos los clientes resultantes por defecto para facilitar una exportación completa.
  * Integrada la filtración por clientes seleccionados (`selectedCustomers` como parámetro en la URL) en los endpoints de generación de PDF: `customersPdf()`, `customersTrackingPdf()` y `customersRecoveryPdf()` de `ReportController.php`.
  * Desarrolladas pruebas automatizadas en `CustomerReportTest.php` para validar los observadores de Livewire y las restricciones en las consultas PDF.

## [1.10.180] - 2026-06-26
### Added
- **PDF de Selección en Reporte de Flujo de Caja**:
  * Integrado el botón **"PDF de Selección"** en la cabecera de la tabla que permite a los operadores generar la vista previa del reporte filtrado únicamente por el bucket de antigüedad seleccionado.
  * Actualizada la lógica de generación del PDF tanto en el controlador `ReportController` como en el componente Livewire para soportar la filtración del bucket seleccionado e incluir el criterio de filtro activo en el encabezado de parámetros del PDF.
  * Añadida la prueba unitaria `test_cash_flow_forecast_pdf_endpoint_with_filtered_bucket` para comprobar que el endpoint PDF responde correctamente (200 OK y tipo `application/pdf`) al recibir el parámetro de filtración temporal.

## [1.10.179] - 2026-06-26
### Added
- **Filtrado Interactivo por Antigüedad en Proyección de Flujo (Prioridad 3)**:
  * Implementada la capacidad de hacer clic en cada una de las tarjetas de antigüedad (Buckets) en `cash-flow-forecast-report.blade.php` para filtrar dinámicamente la tabla de facturas por ese rango temporal.
  * Añadida micro-animación de escalado y cambio de borde/sombra al seleccionar una tarjeta de antigüedad para mejorar la experiencia de usuario (UX).
  * Incorporado un badge informativo en la cabecera de la tabla que detalla el filtro activo y proporciona un botón interactivo para limpiar el filtro.
  * Agregado el caso de prueba `test_cash_flow_forecast_filters_by_selected_bucket` en `CashFlowForecastReportTest.php` para validar la alternancia de filtros y la exactitud en la cantidad y tipos de facturas devueltas.

## [1.10.178] - 2026-06-26
### Added
- **Módulo de Proyección de Flujo de Caja y Eficiencia de Cobranza (Prioridad 3)**:
  * Registrada la ruta del reporte `/reports/cash-flow-forecast` en `routes/web.php` y el enlace correspondiente en la barra de navegación lateral.
  * Creado el componente Livewire `CashFlowForecastReport` que calcula deudas pendientes de ventas a crédito, clasificación temporal de cartera (Ageing Buckets), índice CEI % y DSO ponderado.
  * Desarrollada la vista interactiva `cash-flow-forecast-report.blade.php` que incluye un gráfico Highcharts (Cobros Reales vs. Vencimientos Proyectados), tarjetas de KPIs interactivas, desglose visual de buckets de antigüedad y cuadrícula detallada de deudas.
  * Implementado el **Modal de Interpretación Analítica (IA)** que proporciona un diagnóstico interactivo de salud financiera, alertas y acciones recomendadas de cobranza según el índice CEI y DSO.
  * Agregado el endpoint `/reports/cash-flow-forecast/pdf` y el método controlador `cashFlowForecastPdf` en `ReportController` para generación y descarga directa de un reporte PDF horizontal (Landscape).
  * Diseñada la plantilla PDF `cash-flow-forecast-report-pdf.blade.php` respetando el formato institucional de reportes.
  * Implementada la suite de pruebas automatizadas `CashFlowForecastReportTest.php` validando el renderizado, control de acceso, lógica matemática exacta de CEI/DSO, buckets temporales y generación de PDF.

## [1.10.177] - 2026-06-25
### Fixed
- **Comportamiento de wire:ignore en Contenedor de Gráfico**:
  * Corregido bug por el cual el gráfico se mantenía oculto debido a que la directiva `wire:ignore` estaba colocada en el contenedor externo, impidiendo que Livewire actualizara dinámicamente la clase de visibilidad `d-none` al generarse el reporte. Se movió `wire:ignore` al contenedor interno (`card-body`), logrando que el gráfico se muestre correctamente al presionar el botón de análisis.

## [1.10.176] - 2026-06-25
### Fixed
- **Mapeo de Parámetros en Gráfico de Auditoría Cambiaria**:
  * Corregida la desestructuración de los parámetros del evento `updateChart` en la vista del reporte, añadiendo soporte para argumentos posicionales en arreglos (destructuración `(event, ...args)`) propio del comportamiento de Alpine y Livewire 3 al despachar eventos. Esto soluciona la ausencia del gráfico en el tablero web tras cargar los datos.

## [1.10.175] - 2026-06-25
### Added
- **Interpretador Analítico y Corrección de Gráficos en Auditoría Cambiaria**:
  * Integrado el botón **"Analizar Resultados (IA)"** y el correspondiente modal de **Interpretador de Resultados Analíticos** en el reporte de Auditoría de Diferencial Cambiario, proporcionando explicaciones automáticas, evaluación de eficacia del cojín y sugerencias financieras redactadas.
  * Corregido el problema de renderizado del gráfico de Highcharts moviendo el contenedor `#exchangeDiffChart` fuera del bloque condicional `@if($showReport)` y agregando la directiva `wire:ignore`, solucionando la condición de carrera durante la hidratación de Livewire 3.
  * Creada la prueba automatizada `test_exchange_diff_report_toggles_interpretation_modal_and_generates_analysis` para validar la alternancia del modal de interpretación y la correcta generación de diagnósticos cambiarios dinámicos.

## [1.10.174] - 2026-06-25
### Added
- **Módulo de Auditoría de Pérdidas y Ganancias por Diferencial Cambiario (Fase 2)**:
  * Creado el componente interactivo Livewire `ExchangeDiffReport` para analizar las pérdidas y ganancias cambiarias por abonos en Bolívares (VES/VED) en acuerdos BCV y USD.
  * Implementadas tarjetas de KPIs para Ventas Facturadas (USD), Cobrado Teórico (USD), Cobrado Real (USD a tasa de mercado Binance), Diferencial Neto (fuga), Cojín facturado por recargos y el Resultado Neto Cambiario final de caja.
  * Integrado gráfico interactivo de Highcharts (Areaspline) que contrasta la evolución diaria de las pérdidas acumuladas frente al cojín de amortización facturado.
  * Diseñada cuadrícula de datos con badges de auditoría según nivel de rentabilidad y desvío de tasa (Cumple, Desviación, Fuga).
  * Añadida exportación a PDF en orientación horizontal (Landscape) con desglose de KPIs y auditoría de cobros.
  * Registrada la nueva ruta `/reports/exchange-diff` y agregada la opción en el menú lateral ("Ventas y Cobros").
  * Creada la suite de pruebas unitarias y de integración `ExchangeDiffReportTest.php` para validar accesos, cálculos matemáticos del diferencial y la descarga del PDF.

## [1.10.173] - 2026-06-25
### Added
- **Catálogo de Sugerencias de Venta Cruzada (Módulo de Rotación)**:
  * Agregado botón **"Descargar Catálogo de Ofertas"** en el pie del modal de interpretación analítica, el cual se muestra únicamente al tener un cliente específico seleccionado.
  * Implementada la generación dinámica en PDF (orientación vertical) de un catálogo impreso personalizado con productos sugeridos que el cliente aún no ha comprado pero que tienen stock disponible, incluyendo su SKU, categoría, stock actual y precio.
  * Creada la prueba unitaria `test_rotation_report_catalog_pdf_generation_endpoint` para validar la descarga del catálogo en formato PDF.

## [1.10.172] - 2026-06-25
### Added
- **Interpretador de Resultados y Leyendas Explicativas (Módulo de Rotación)**:
  * Agregado el botón **"Analizar Resultados (IA)"** que abre un modal interactivo con una interpretación redactada del estado financiero y salud del inventario. Si se filtra por un cliente específico, el sistema analiza dinámicamente su perfil comercial (diversificación, rentabilidad, capital ocioso y sugerencia de venta cruzada).
  * Incorporadas leyendas explicativas (tooltips nativos mediante atributos `title` y cursor `help`) en todas las tarjetas de KPIs principales, gráficos interactivos de Highcharts y en cada una de las cabeceras de la matriz de datos, facilitando enormemente la interpretación de métricas clave (Clase ABC, Cobertura, Velocidad de venta, etc.) para el operador.
  * Creada prueba automatizada `test_rotation_report_toggles_interpretation_modal_and_generates_analysis` en la suite para verificar la visualización y redacción del interpretador.

## [1.10.171] - 2026-06-25
### Changed
- **Buscadores Interactivos en Reporte de Rotación**:
  * Convertidos los selectores estándar de Categoría, Proveedor, Cliente y Etiqueta de Producto en campos de búsqueda interactiva (autocompletado) utilizando **TomSelect** con `wire:ignore`, mejorando sustancialmente la usabilidad y velocidad de filtrado al buscar entre cientos de registros.

## [1.10.170] - 2026-06-25
### Added
- **Filtro de Etiquetas en Reporte de Rotación (Product Matrix)**:
  * Agregado filtro de búsqueda por etiquetas de producto para permitir comparar y analizar productos de la misma categoría o con la misma etiqueta.
  * Añadido el selector de etiqueta en la vista del reporte web (`rotation-report.blade.php`).
  * Integrado el nombre de la etiqueta activa en el reporte PDF Landscape exportado.
  * Expandida la suite de pruebas unitarias en `RotationReportTest.php` con el caso `test_rotation_report_filters_by_tag` para validar la lógica de filtrado y KPIs.

## [1.10.169] - 2026-06-25
### Added
- **Módulo de Análisis de Rotación de Inventario y Rentabilidad (Product Matrix)**:
  * Rediseñada por completo la interfaz del reporte de rotación (`RotationReport`) transformándolo en un tablero analítico de salud del inventario.
  * Añadidas tarjetas de KPIs interactivos que muestran de manera consolidada el Capital Total en Inventario, el Capital Ocioso (stock sin movimiento), la Ganancia Bruta acumulada por ventas y el Margen Promedio porcentual.
  * Integrada visualización interactiva de Highcharts con un gráfico tipo Donut para la distribución por Clasificación ABC y un gráfico de barras horizontales para el Top 10 de productos más rentables en base a su margen en dólares.
  * Implementado el algoritmo de Clasificación ABC por Pareto (80% para Clase A, 15% para Clase B, 5% o sin ventas para Clase C) calculado sobre las ventas USD del rango seleccionado.
  * Añadidas columnas financieras en la cuadrícula de datos web: Clasificación ABC, Valor del Stock (Costo), Ventas Totales en USD, Margen en USD y Porcentaje de Margen.
  * Refactorizado el reporte PDF para cambiar su orientación a Landscape (horizontal) e integrar las nuevas columnas financieras y tarjetas de KPIs globales de inventario.
  * Creada la suite de pruebas unitarias y de integración `RotationReportTest.php` para validar las métricas financieras, clasificaciones ABC de Pareto, cálculo de KPIs globales y descarga del PDF.

## [1.10.168] - 2026-06-25
### Fixed
- **Ajuste de Tasa de Cambio en Ventas de Contado (VES/VED)**:
  * Corregido error por el cual las ventas de contado pagadas en Bolívares (VES/VED) en el punto de venta (POS) utilizaban la tasa Binance base (sin ajuste), en lugar de la tasa ajustada con el recargo establecido (`binance_rate` + `binance_markup_points`). Esto ocasionaba que el monto recibido en Bolívares se sobrevaluara en dólares y permitiera registrar ventas con pagos reales insuficientes.
  * Se modificó `calculateRemainingAndChange()` y el bucle de validación de pagos en `Store()` dentro de `app/Livewire/Sales.php` para sumar de manera uniforme los puntos de margen a la tasa de Binance.
  * Creada la prueba de características `CashSaleAdjustedExchangeRateTest.php` para asegurar que el sistema bloquee pagos insuficientes a tasa base y permita los pagos correctos a tasa con ajuste.

## [1.10.167] - 2026-06-24
### Fixed
- **Actualización en Tiempo Real de Gráficos de Reportes**:
  - Implementado el método de ciclo de vida `updated()` de Livewire en los componentes de Análisis de Ventas, Desempeño de Vendedores, Actividad de Clientes y Eficiencia de Operadores. Esto soluciona la discrepancia por la cual los gráficos de Highcharts y Chart.js quedaban desincronizados al alternar checkboxes y filtros, forzando la recarga y renderizado automático del gráfico sin necesidad de hacer clic manualmente en el botón de analizar.

## [1.10.166] - 2026-06-24
### Added
- **Módulo de Eficiencia y Precisión de Operadores de Facturación (Fase 3)**:
  - Implementado reporte interactivo para analizar y comparar la productividad y la calidad en la transcripción de facturas por parte de los operadores facturadores.
  - Integrado el gráfico interactivo Highcharts multiserie que permite comparar métricas clave (Score de Calidad, Facturas Emitidas, Monto Facturado, Modificadas, Anuladas y Devoluciones) por operador.
  - Implementado el Score de Precisión de Facturación ponderando incidencias: Facturas Anuladas (peso 1.5), Facturas Modificadas (peso 1.0) y Facturas con Devolución (peso 1.2).
  - Diseñada tabla de desglose comparativo detallada de eficiencia por operador que muestra facturas emitidas, monto de ventas, errores detallados, score de calidad, días activos y facturas emitidas por día activo (eficiencia diaria).
  - Añadido modal interactivo con visor de PDF horizontal (Landscape) que expone el resumen ejecutivo de KPIs y el historial de las últimas 100 facturas para auditoría.
  - Creada suite de pruebas en `BillingOperatorsReportTest.php` para validar acceso, cálculos matemáticos del score de precisión, eficiencia de facturación y generación exitosa del PDF.

### Fixed
- **Mapeo de Ventas de Oficina en Reporte de Desempeño de Vendedores**:
  - Corregido error por el cual las ventas del vendedor por defecto (`OFICINA`) no aparecían en el gráfico ni en la tabla al filtrar. Los clientes correspondientes tienen `seller_id = NULL` en la base de datos, lo cual ahora se mapea correctamente al ID del usuario `OFICINA` en las consultas SQL y en los filtros de PHP.

## [1.10.165] - 2026-06-24
### Added
- **Módulo de Desempeño de Vendedores (Foráneos vs. Oficina)**:
  - Implementado reporte interactivo para analizar y comparar la fuerza de ventas externa frente a la facturación de oficina (`OFICINA`).
  - Integrado el gráfico interactivo Highcharts multiserie que permite seleccionar y comparar las curvas de tendencia de múltiples vendedores simultáneamente según agrupaciones diarias, semanales, quincenales, mensuales o anuales.
  - Diseñadas tarjetas de resumen para el total de ventas, comisiones devengadas, márgenes netos de retorno y estado de cartera de crédito pendiente/vencida.
  - Creada tabla detallada de rendimiento por vendedor que expone volumen bruto, facturas procesadas, comisiones, venta neta, margen neto porcentual, clientes activos atendidos, deuda pendiente, deuda vencida y promedio ponderado de días de atraso.
  - Añadida previsualización de PDF en orientación horizontal (Landscape) con estructura ejecutiva completa.
  - Creada suite de pruebas en `SellersPerformanceReportTest.php` para validar el acceso, lógica de KPIs, deudas ponderadas y exportación.

### Fixed
- **Ordenación Cronológica en Reportes de Actividad y Análisis**:
  - Corregida la ordenación alfabética incorrecta de los períodos en el Reporte de Actividad del Cliente y en el Reporte de Análisis de Ventas (tanto en web como en PDF), la cual provocaba que meses como Abril se mostraran antes de Febrero debido al ordenamiento alfabético de los nombres traducidos al español.
  - Implementada la preservación del formato cronológico original (e.g. `YYYY-MM` o `YYYY-MM-DD`) en una propiedad interna `raw_period`, la cual es utilizada para ordenar las etiquetas y los datos del gráfico/tabla antes de la visualización.

## [1.10.164] - 2026-06-24
### Added
- **Módulo de Análisis de Ventas y Crecimiento (Fase 1)**:
  - Implementado reporte interactivo de analítica de ventas con filtros de fechas, selector de métrica (Monto de Ventas, Cantidad de Facturas, Comisiones Devengadas, Ventas Netas) y agrupación temporal (Diario, Semanal, Quincenal, Mensual, Anual).
  - Integrada la visualización dinámica utilizando la librería **Highcharts** con soporte para gráficos interactivos de área suavizada (Areaspline).
  - Creadas tarjetas de KPIs principales con cálculo automático de tasas de crecimiento porcentual interperiodo (comparando el periodo actual contra el anterior del mismo tamaño) y flechas de tendencia (↑/↓).
  - Añadida tabla de desglose comparativo por periodo con cálculo dinámico de crecimiento fila a fila en el navegador.
  - Implementada exportación limpia a PDF con orientación vertical (Portrait), incluyendo resumen de KPIs, tabla comparativa de periodos y listado detallado de facturas emitidas en el periodo seleccionado.
  - Creada suite de pruebas funcionales automatizadas en `SalesAnalysisReportTest.php` para validar el renderizado del componente Livewire, cálculos de agregación/comisiones/crecimiento y descarga exitosa del reporte en PDF.

## [1.10.163] - 2026-06-24
### Added
- **Mejora en Etiquetas de Períodos del Gráfico de Actividad**:
  - Implementado un formato descriptivo para las semanas con la estructura `AÑO-MES-DÍA(lunes)-SEMANA` en el reporte web y PDF (por ejemplo, `2026-MARZO-16-S12`).
  - Modificado el formato de meses para mostrar el nombre del mes en español (por ejemplo, `2026-MARZO` en lugar de `2026-03`).

### Fixed
- **Filtros en PDFs de Clientes**:
  - Corregida la generación de URLs de previsualización en el reporte de clientes para incluir `columns` y `inactivityDays`.
  - Asegurado que los PDFs General, de Seguimiento y de Recuperación respeten los filtros de inactividad de la tabla.
  - Corregidas las pruebas en `CustomerReportTest.php` para validar las nuevas firmas de las URLs generadas.
- **Gráfico de Actividad de Clientes**:
  - Corregida la inicialización del canvas de Chart.js eliminando la condicional `@else` de Blade que impedía su existencia en el DOM en la primera carga.
  - Añadido soporte para ocultar/mostrar elementos mediante clases de Bootstrap (`d-none`), manteniendo el canvas en el DOM desde el inicio.
  - Añadida la directiva `wire:ignore` al contenedor para evitar que las actualizaciones del DOM por Livewire destruyan el gráfico.

## [1.10.162] - 2026-06-23
### Added
- **Reporte de Actividad y Análisis de Compras del Cliente**:
  - Implementado un nuevo reporte interactivo con agrupaciones temporales flexibles (Semanal, Mensual, Trimestral, Anual).
  - Integrada la visualización interactiva de tendencias multi-cliente utilizando la librería Chart.js vía CDN con AlpineJS.
  - Añadido panel lateral con buscador dinámico de clientes mediante checkboxes que persisten los elementos seleccionados.
  - Implementado un modal con iframe para la previsualización del PDF.
  - Creado un PDF comparativo horizontal (Landscape) con tarjetas de KPIs por cliente (Total comprado, Nro. compras, Ticket promedio, Última compra, y listado de productos más comprados), tabla comparativa por periodo y registro detallado de facturas.
  - Añadida sección de los top 5 productos más comprados ("Top Productos") por cada cliente tanto en el reporte web (debajo de cada tarjeta de KPI) como en la tabla de resumen del reporte PDF para un perfil de compra más adaptado.
  - Añadida suite de pruebas automatizadas en `CustomerActivityReportTest.php` para validar la renderización del componente, disparo de eventos del gráfico y descarga del PDF con aserciones para los productos más comprados.

## [1.10.161] - 2026-06-23
### Added
- **Totales en Reportes PDF de Clientes**:
  - Incorporados cuadros de resumen al pie de página en los reportes PDF de Planilla de Seguimiento y Reporte de Recuperación de Clientes Inactivos.
  - Muestra del total acumulado con etiquetas descriptivas en español: "TOTAL CLIENTES EN SEGUIMIENTO" y "TOTAL CLIENTES INACTIVOS PARA RECUPERACIÓN".
  - Implementación de pruebas unitarias específicas para verificar la correcta renderización y el conteo de registros en las vistas HTML/PDF correspondientes.

## [1.10.160] - 2026-06-23
### Added
- **Reporte de Recuperación de Clientes Inactivos (Win-back)**:
  - Implementado un filtro de inactividad comercial por días (>30d, >60d, >90d, >120d) para identificar clientes que han dejado de comprar.
  - Añadidas subconsultas SQL optimizadas en el modelo `Customer` para calcular de manera eficiente la fecha y cantidad de días de la última compra activa, así como el total facturado histórico en USD.
  - Incorporados badges visuales de nivel de riesgo (Bajo, Medio, Alto, Crítico) y columnas dinámicas para la visualización del estatus del cliente.
  - Creado un PDF especializado de "Reporte de Recuperación de Clientes" ordenado de mayor a menor según su valor histórico en USD para priorizar las campañas de telemercadeo o reactivación comercial, incluyendo campos de registro de llamadas.
  - Añadido soporte de test de integración exhaustivo en `CustomerReportTest.php`.

## [1.10.159] - 2026-06-23
### Added
- **Planilla de Seguimiento de Clientes en PDF**:
  - Incorporada una planilla de seguimiento comercial optimizada para la impresión física y trabajo de campo por parte de los vendedores.
  - El diseño incluye el estatus financiero (crédito y billetera) y espacios en blanco/líneas punteadas y checkboxes para registrar en tiempo real la fecha de visita, pedidos tomados, cobros realizados (con referencia) o justificación de no compra y observaciones.
  - Añadido el botón "Planilla Seguimiento PDF" y modal visor de iframe correspondiente.
  - Implementados tests de integración en `CustomerReportTest.php` para verificar el flujo de generación y descarga del PDF.

## [1.10.158] - 2026-06-23
### Added
- **Reporte de Clientes por Vendedor**:
  - Implementado un nuevo reporte en el Centro de Reportes > Ventas y Cobros para filtrar y exportar clientes.
  - Agregado el selector de vendedores mediante una lista de casillas (checkboxes) en el sidebar lateral izquierdo.
  - Implementado un panel de configuración de columnas dinámicas que permite seleccionar y ocultar las columnas a mostrar (Nombre, Identificación, Dirección, Ciudad, Teléfono, Vendedor, Saldo Billetera, Zona, Permite Crédito, Límite, Días, Notificaciones, Estado).
  - Añadido soporte para agrupar los clientes por vendedor con cabeceras de subtotal.
  - Creada la opción de generar una vista previa e impresión de PDF del reporte en formato portrait o landscape (según el número de columnas seleccionadas).
  - Implementada la suite de pruebas de integración completas (`CustomerReportTest.php`).

## [1.10.157] - 2026-06-23
### Added
- **Desactivación y Restauración de Clientes y Productos**:
  - Habilitado el mecanismo de Soft Deletes (Eliminación Lógica) nativo de Laravel para los modelos `Customer` y `Product`.
  - Agregado el selector/switch "Ver Eliminados" en las pantallas principales de Clientes y Productos con un badge de restauración (`Restore`) para recuperar registros desactivados.
  - Removido el bloqueo de eliminación física. Ahora tanto clientes como productos se pueden desactivar de forma lógica sin importar que tengan transacciones o facturas previas asociadas.
  - Actualizadas las relaciones de modelos históricos (`Sale`, `Order`, `SaleReturn`, `DebitNote`, `WhatsappMessage`, `EmailMessage`, `CustomerConfig`, `CreditDiscountRule`, `ProductionOutput`, `ProductionMaterial`) con `->withTrashed()` para garantizar la integridad histórica y evitar valores nulos.
  - Implementadas pruebas de integración completas (`CustomerSoftDeleteTest` y `ProductSoftDeleteTest`).

## [1.10.156] - 2026-06-22
### Added
- **Fábrica de Bolsas (App Móvil - v1.0.5)**: Añadido soporte nativo para escaneo de códigos de barra utilizando la cámara del dispositivo (`mobile_scanner`). Se corrigió el placeholder de búsqueda de "lea código QR..." a "Escriba nombre o lea código de barras...".
- **Fábrica de Bolsas (Android Metadata)**: Corregida la etiqueta de la aplicación en el launcher del dispositivo a "JSPOS Bolsas" (estaba configurado como "JSPOS Soplados").
- **Distribución de APK**: Generado y distribuido el APK optimizado (`JSPOS_Mobile_Bolsas_v1.0.5_ShowUsuario_SuWeb.apk`) en la raíz del proyecto usando el protocolo oficial (ARM64 split).

## [1.10.155] - 2026-06-22
### Added
- **Fábrica de Bolsas (App Móvil - v1.0.4)**: Compilado y distribuido el APK corregido (`JSPOS_Mobile_Bolsas_v1.0.4_ShowUsuario_SuWeb.apk`) en la raíz del proyecto usando el protocolo correcto (ARM64 split). Muestra de forma robusta el nombre del usuario/operario que subió el lote.

### Fixed
- **Fábrica de Bolsas (Módulo Web)**: Corregido el botón de eliminar en el listado de producciones pendientes. Se reemplazó la directiva `wire:confirm` nativa de Livewire (que estaba colisionando con scripts del frontend del tema) por una confirmación inline `onclick` nativa que detiene la propagación y ejecuta la eliminación correctamente.

## [1.10.154] - 2026-06-22
### Added
- **Correo Comprobante al Subir Lote**: Al registrar un levantamiento de producción desde la app móvil, se envía inmediatamente un correo a los destinatarios configurados con una copia PDF del levantamiento original. Sirve como comprobante inmutable para el operador ante posibles ediciones posteriores.




## [1.10.153] - 2026-06-22
### Added
- **Persistencia e Inmutabilidad de Costos**: Añadida columna `cost` a `production_details` para registrar e inmutar el costo histórico de los productos al aprobar planillas de levantamiento, copiándolos a los cargos de inventario correspondientes.
- **Visualización de Costos y Totales**: Agregadas las columnas **Costo** y **Total** en la vista web de detalles de producción y en las plantillas de reporte PDF de producción general y Fábrica de Bolsas.

### Fixed
- **Envío de Correos Consolidados**: Solucionado error en `CargosList` que agrupaba cargos por fecha de creación global diaria y causaba que cargos de producción ajenos o pendientes bloquearan el envío. Ahora se agrupa y valida estrictamente por cada ID de planilla (`production_id`).
- **Asunto de Correo Personalizado**: Se actualizó el asunto predeterminado del correo para que denote que es la planilla de la Fábrica de Bolsas y su lote correspondiente para facilitar la búsqueda en el correo.

## [1.10.152] - 2026-06-19
### Added
- **Reportes de Producción (Marca de Agua PDF)**: Añadido soporte para mostrar una marca de agua en diagonal de fondo en los PDFs de planillas de producción (`PENDIENTE`, `APROBADO` o `PROCESADO`), con estilos de baja opacidad y colores representativos de cada estado.

## [1.10.151] - 2026-06-19
### Fixed
- **Ajustes de Inventario (Edición de Cargos)**: Corregido error de colisión de claves de carrito en `CreateCargo.php` que ocasionaba la pérdida de detalles duplicados del mismo producto al abrir, editar o clonar un Cargo. Se migró la indexación del carrito a claves de fila únicas (`detail_{id}` y `new_{id}`).

## [1.10.150] - 2026-06-19
### Fixed
- **Fábrica de Bolsas (Edición Web)**: Corregido error que causaba la pérdida de bobinas o detalles duplicados del mismo producto al editar/guardar lotes de producción. Se cambió el índice del carrito a una clave de fila única alfanumérica (`detail_{id}` y `new_{id}`), evitando colisiones de claves de producto.

## [1.10.149] - 2026-06-19
### Fixed
- **Administración de Items**: Corregido un problema donde la eliminación o adición de bobinas en el componente hijo `ProductItemsManager` no actualizaba el stock general ni el desglose por depósitos en el formulario del componente padre `Products`.

## [1.10.148] - 2026-06-19
### Added
- **Trazabilidad de Bobinas (Items Disponibles)**: Se agregaron las columnas de Fecha de Elaboración (fabricación) y Operario Fabricante a la tabla de "Items Disponibles" (`product-items-manager.blade.php`), y se eliminó la columna redundante "Original (Kg)".
- **Trazabilidad de Bobinas (Aprobación de Cargos)**: Al aprobar un cargo generado desde una planilla de Levantamiento de Producción, los registros de `ProductItem` resultantes heredan automáticamente la fecha de producción y el operario fabricante del detalle correspondiente.
- **Trazabilidad de Bobinas (Creación Manual)**: Se añadieron campos opcionales de Fecha de Elaboración y Operario Fabricante al formulario de agregar nueva unidad en el administrador de ítems de producto.
- **Fábrica de Bolsas (App Móvil - Distribución)**: Se compiló y generó la versión `1.0.2` de la aplicación móvil (`JSPOS_Mobile_Bolsas_v1.0.2_AppManufactura_SuWeb.apk`) copiada a la raíz del proyecto.

## [1.10.147] - 2026-06-19
### Added
- **Fábrica de Bolsas (App Móvil - Distribución)**: Se compiló y generó la nueva versión de producción del APK (`JSPOS_Mobile_Bolsas_v1.0.1_AppManufactura_SuWeb.apk`) copiada a la raíz del proyecto para facilitar su descarga. Incorpora soporte nativo para validar y persistir la fecha individual de producción por ítem, heredando automáticamente la fecha global elegida al principio.
- **Fábrica de Bolsas (Módulo Web - Livewire)**: Añadidas columnas editables de Fecha de Elaboración y Operario por cada artículo en el carrito web de producción. Los detalles del modal se muestran agrupados visualmente por fecha en español.
- **Fábrica de Bolsas (Base de Datos)**: Añadida columna `production_date` a `production_details` y configurado el casteo correspondiente en Eloquent.

## [1.10.146] - 2026-06-16
### Added
- **Módulo de Levantamiento de Producción para Fábrica de Bolsas**:
  - **API y Base de Datos**: Migraciones para agregar `operator_name` en `production_details` y `production_id` en `cargos`. Implementación del controlador `BagsProductionApiController` con rutas API registradas para la obtención de productos M&F, guardado de producción y consulta de historial con filtros avanzados.
  - **Pruebas de Integración**: Pruebas automatizadas completas en `BagsProductionApiTest` (5/5 aprobadas).
  - **Aprobación de Cargos y Correo Consolidado**: Vinculación de estados en `CargosList` al aprobar cargos de producción. Implementación de envío automático de correo consolidado (`BagsProductionConsolidatedMail`) una vez que todos los cargos subidos el mismo día son aprobados, adjuntando PDFs independientes por cada día de producción (`pdf/bags_production.blade.php`).
  - **Aplicación Móvil de Bolsas (`mobile_bolsas_app`)**: Duplicado del proyecto móvil a partir de `mobile_soplados_app`, renombrando el paquete a `com.suweb.bolsas_mobile` y generando un icono personalizado. Eliminación de flujos de turnos, traspasos y recibos, implementando un flujo directo de levantamiento diario con selector de fecha, lectura QR, carga multi-operador por producto e ingresos de peso individual para bobinas variables.
  - **Distribución de APK**: Compilación del paquete release y copiado a la raíz del proyecto como `JSPOS_Mobile_Bolsas_v1.0.0_AppManufactura_SuWeb.apk`.

## [1.10.145] - 2026-06-16
### Added
- **Notificaciones Automáticas por Correo al Cerrar Turno de Soplados**:
  - Incorporación de soporte para envío automatizado de correos electrónicos detallados al cerrar un turno de soplado (`soplados/shifts/close`).
  - Lógica para compilar estadísticas clave del turno cerrado: cantidad total de botellones/envases soplados (de 1ra y 2da calidad), unidades defectuosas (merma/desecho), rendimiento/eficiencia (Yield), detalle desglosado de envases soplados, materiales consumidos, notas del turno y listado de operadores asociados.
  - Registro de configuraciones independientes para destinatarios (`soplados_email_recipients`), asunto (`soplados_email_subject`) y cuerpo (`soplados_email_body`) en la pestaña de Ajustes > Producción.
  - Carga automática de una plantilla enriquecida por defecto para los correos de soplados si la base de datos está vacía.
  - Creación del Mailable `SopladosShiftReportMail` y de pruebas de integración exhaustivas `SopladosShiftNotificationTest`.

## [1.10.144] - 2026-06-15
### Added
- **Nuevas Variables y Plantilla Detallada para Reportes de Producción**:
  - Incorporación de soporte para nuevas variables dinámicas de correo en la compilación del reporte de producción: `[PRODUCCION_ID]`, `[CANTIDAD_TOTAL]`, `[PESO_TOTAL]`, `[RESUMEN_DETALLES]`, `[NOTA]` y `[EMPRESA]`.
  - El resumen de detalles autogenera un listado tabulado con viñetas del tipo de producto y material (Original/Recuperado), cantidades y pesos en kilogramos.
  - Actualizada la interfaz visual de configuración de la plantilla de correo de producción en los Ajustes para documentar y exponer las nuevas variables.

## [1.10.143] - 2026-06-15
### Fixed
- **Corrección de Cálculo de Saldo Restante en Notificaciones**:
  - Corrección de un error de mezcla de monedas en `SystemNotificationListener.php` que calculaba de forma errónea el saldo restante en los correos y mensajes de WhatsApp.
  - El sistema restaba directamente los montos brutos de los pagos (independientemente de si estaban en USD o bolívares VED/VES) del total en USD de la factura. Ahora, el sistema convierte cada pago a su equivalente en USD utilizando la tasa de cobro registrada, y descuenta también las devoluciones aprobadas, coincidiendo exactamente con los cálculos del PDF del recibo.

## [1.10.142] - 2026-06-15
### Fixed
- **Corrección de Indicador de Tasa en Auditoría de Factura y Planilla**:
  - Corrección de la lógica de renderizado del checkmark (`✔`) en la sección de "Tasas Referenciales del Día del Pago".
  - Ahora el checkmark solo se muestra al lado de una tasa de referencia (Binance Real o con Ajuste) si coincide con la tasa real registrada en el pago (`Tasa de Cobro`), evitando la confusión de marcar como "correcta" una tasa cuando el pago utilizó una tasa manual diferente (por ejemplo, 400 Bs).

## [1.10.141] - 2026-06-15
### Added
- **Cálculo de Diferencial en Tiempo Real en Configuración de Tasas**:
  - Implementación de precalculado de brecha o diferencial cambiario en la sección de "Tasas Globales de Referencia" en la página de configuraciones.
  - Visualización del "Diferencial Real" (brecha Binance Real vs BCV) y del "Diferencial Aplicado" (brecha Binance con Ajuste vs BCV) mediante badges premium con FontAwesome.
  - Vinculación interactiva y reactiva de los inputs de tasa BCV, tasa Binance y Ajuste con `wire:model.live.debounce.300ms` para permitir el cálculo instantáneo de la brecha en tiempo real según el operador escribe.
  - Incorporación de pruebas automatizadas en `GlobalRatesDifferentialTest.php` para asegurar el cálculo y renderizado correcto de los diferenciales en Livewire.

## [1.10.140] - 2026-06-12
### Added
- **Control de Pérdidas y Validación Cambiaria en POS**:
  - Implementación de advertencias y bloqueo en vivo en el modal de pago del POS para ventas a crédito con Acuerdo BCV. Si el diferencial del cliente (`exchange_diff_percent`) no cubre la brecha cambiaria Binance-BCV, el sistema bloquea el guardado de la transacción (tarjeta roja en el modal). Si es suficiente pero está cerca del límite, se muestra una tarjeta amarilla de advertencia.
  - Implementación de validación de contravalor real en Bolívares para ventas de contado. Si la tasa de cambio utilizada en el pago (por error de configuración) no cubre el valor base en dólares reales de la venta al tipo de cambio Binance real, el sistema bloquea la facturación arrojando una alerta.
- **Alineación de Modal de Auditoría de Facturas**:
  - Se rediseñó por completo el modal de detalles de auditoría en el listado de facturas (`/audit/invoices`) para alinearse en estilo, nivel de detalle y usabilidad con el modal premium de la auditoría de planillas de cobro (`/audit/sheet`).
  - Incluye un banner dinámico de rentabilidad, tarjetas paralelas de configuración y cobro, tasas referenciales del día de pago, desglose matemático proporcional y análisis del contravalor real Binance con margen frente a costo base.
  - Añadido un selector de pestañas (pills) reactivo cuando la factura tiene múltiples abonos/pagos, permitiendo conmutar dinámicamente el análisis de cada cobro de forma individual.
- **Bloqueos de Auditoría ante Brecha Cambiaria**:
  - Se bloquea el cambio manual de estado de auditoría (toggle a verde) en el listado de facturas para ventas que tengan acuerdo de pago en USD pero hayan sido cobradas/pagadas a la tasa oficial de BCV.
  - Se bloquea la finalización y cierre de planillas de cobro (Collection Sheet Audit) si la planilla contiene pagos aprobados de facturas con acuerdo de pago en USD liquidados a la tasa de BCV.
- **Pruebas de Integración y Regresión**:
  - Incorporación de pruebas robustas que validan los bloqueos de checkout por diferenciales insuficientes y tasas de bolívares por debajo del valor Binance en `POSPaymentAgreementEnforcementTest.php`.
  - Incorporación de pruebas de bloqueo de auditoría individual, conmutación de pagos múltiples en el modal de detalles, y finalización de planillas en `InvoicesAuditListTest.php` and `CollectionAuditTest.php`.

## [1.10.139] - 2026-06-12
### Added
- **Visualización de Acuerdo de Pago en Reportes**:
  - Se agregó la columna "Acuerdo" (de pago) al Reporte de Ventas General en la vista web y en su exportación PDF, incluyendo un control interactivo (checkbox/toggle) para mostrar u ocultar la columna.
  - Se implementó la visualización de un badge de Acuerdo de Pago (`Bs.` para BCV y `USD` para USD) al lado del número de factura en el PDF del Reporte de Ventas Diarias (`daily-sales-report-new-pdf.blade.php`).
  - Se agregaron pruebas unitarias/de integración correspondientes en `GeneralSalesReportPdfTest.php` para verificar el correcto funcionamiento del toggle y la generación de PDFs.

## [1.10.138] - 2026-06-12
### Changed
- **Selección de Acuerdo de Pago en POS**: Se hizo obligatoria y consciente la selección del "Acuerdo de Pago Acordado" en el POS para las ventas a crédito, previniendo errores accidentales donde se autoseleccionaba una opción por defecto (USD/Zelle).
- **Carga Inmediata de Configuración de Crédito**: Se optimizó la carga de límites y condiciones de crédito de clientes en el componente Livewire para cargarse de forma reactiva inmediata al seleccionar el cliente.

### Fixed
- **Acceso a Atributos del Cliente en POS**: Se corrigió un bug en la validación de crédito del POS donde se accedía incorrectamente al identificador del cliente como propiedad de objeto (`$this->customer->id`) en lugar de clave de arreglo (`$this->customer['id']`), provocando advertencias e impidiendo el registro correcto del crédito.

## [1.10.137] - 2026-06-12
### Added
- **Módulo de Reporte Mensual de Ingresos (Matriz Comparativa Horizontal)**:
  - Se definieron las rutas correspondientes (`reports.monthly.income` y `reports.monthly.income.pdf`) en `routes/web.php`.
  - Se integró el enlace de acceso directo al reporte en el menú lateral (`sidebar.blade.php`).
  - Se desarrolló el componente Livewire `MonthlyIncomeReport` que calcula de lunes a sábado los ingresos consolidados por semanas (Semana 1 a 5) y el total mensual.
  - Se diseñó la interfaz de matriz horizontal estilo Excel.
  - Se implementó la vista horizontal compacta en PDF (`monthly-income-report-pdf.blade.php`) usando DomPDF con marca de agua diagonal `"PRELIMINAR - BORRADOR"` para meses en progreso o con planillas de cobro sin consolidar/auditar.
  - Se crearon las pruebas de integración en `MonthlyIncomeReportTest` logrando validar los cálculos mensuales.

## [1.10.136] - 2026-06-11
### Added
- **Módulo de Reporte Semanal de Ingresos (Replicación de Excel)**:
  - Se definieron las rutas correspondientes (`reports.weekly.income` y `reports.weekly.income.pdf`) en `routes/web.php`.
  - Se integró el enlace de acceso directo al reporte en la sección de reportes de ventas y cobros en el menú de navegación (`sidebar.blade.php`).
  - Se desarrolló el componente Livewire `WeeklyIncomeReport` que calcula de lunes a sábado los ingresos desglosados de contado (USD, COP, VES), cobranzas a través de planillas de cobro y neto de ventas a crédito.
  - Se diseñó la interfaz con la cuadrícula de días y totales al estilo Excel, usando las cabeceras `#5B9BD5` y subtotales `#2F5597`.
  - Se implementó la vista horizontal compacta en PDF (`weekly-income-report-pdf.blade.php`) usando DomPDF con soporte para marca de agua diagonal `"PRELIMINAR - BORRADOR"` en semanas en curso o con planillas sin consolidar.
  - Se crearon pruebas de integración completas en `WeeklyIncomeReportTest` logrando validar con éxito todos los cálculos del reporte.

## [1.10.135] - 2026-06-11
### Added
- **Soporte de Recargo de Base (Base Markup) en Auditoría de Facturas y Cobranzas**:
  - Se actualizó la vista detallada de auditoría de facturas (`invoices-audit-list.blade.php`) para incluir la visualización del nuevo recargo de base de forma independiente y descontarlo adecuadamente del contravalor recuperado.
  - Se modificó el componente `CollectionSheetAudit` y su vista Blade (`collection-sheet-audit.blade.php`) para calcular y mostrar el recargo de base de manera proporcional en los cobros de planillas.
  - Se actualizaron las explicaciones y fórmulas matemáticas detalladas del contravalor en el modal de auditoría de cobranza para reflejar la deducción del recargo de base.
  - Se actualizó `CommissionService` y los reportes (`PaymentRelationshipReport`, `CommissionReport` y `ReportController`) para incluir el recargo de base en los cálculos inversos de la base imponible y el cálculo de comisiones.

### Tests
- **Prueba del Recargo de Base**: Se añadieron pruebas unitarias e integrales en `PriceSequentialCalculationTest` y `HierarchicalCommissionTest` verificando los cálculos secuenciales correctos y la persistencia de las propiedades de recargo en base de datos.

## [1.10.134] - 2026-06-11
### Added
- **Centralización de Recargos en Clientes**: Se unificó la fuente de verdad de las configuraciones de recargos (comisión, flete y diferencial cambiario) a nivel de cliente (`CustomerConfig`). Se removió por completo la lógica de herencia y fallbacks a la configuración del vendedor foráneo (`SellerConfig`), logrando un único lugar de configuración y evitando retroactividades no deseadas ante cambios de vendedor.
- **Migración de Configuraciones Activas**: Se implementó una migración automática que copia las configuraciones vigentes de `SellerConfig` a `CustomerConfig` para todos los clientes que no contaban con tarifas específicas, resguardando las condiciones comerciales activas de cada cliente.

### Changed
- **Lógica del POS y API**: Se actualizaron el modelo `Sale`, el modelo `Order`, el servicio `PriceCalculatorService` y el controlador Livewire `Sales` para calcular todos los recargos leyendo exclusivamente de `CustomerConfig`.
- **Limpieza de Interfaz**: Se removieron los inputs de recargos (`commission_percent`, `freight_percent`, `exchange_diff_percent`, `current_batch` y `agreement`) en la pestaña de comisiones del Vendedor (Tab 4) en la creación/edición de usuarios, y se limpiaron los campos relacionados en el componente Livewire `Users`. Se actualizaron las vistas del POS (`sales.blade.php`, `payCash.blade.php`, `items-list.blade.php`, `items-grid.blade.php`) para remover leyendas de origen "Vendedor/Global".

### Fixed
- **Corrección Retroactiva de Fletes Históricos**: Se integró en la migración una rutina inteligente que detecta facturas históricas afectadas (donde el total guardado en base de datos incluía flete pero el campo `freight_amount` y `applied_freight_percent` se almacenaron como 0.00) y las corrige actualizando sus cabeceras, fletes detallados en artículos y diferenciales cambiarios correspondientes.
- **Actualización de Pruebas Unitarias y de Integración**: Se adaptaron los tests en `PriceSequentialCalculationTest` y `HierarchicalCommissionTest` para interactuar únicamente con `CustomerConfig` en lugar de `SellerConfig`, logrando el 100% de éxito en la ejecución de la suite de PHPUnit.

## [1.10.133] - 2026-06-11
### Fixed
- **Guardado y Resolución de Flete**: Se corrigió un problema donde los montos y porcentajes del flete configurados en el cliente no se guardaban en la base de datos (quedando en $0.00 / 0%) cuando el cajero/oficina desmarcaba manualmente "Aplicar Solo Flete" pero mantenía "Aplicar Comisiones" activo. Ahora, el flete se resuelve y almacena si cualquiera de los dos switches está activo.
- **Lógica de Órdenes**: Se actualizó el guardado de órdenes (`storeOrder`) para persistir la bandera `apply_freight` como `applyCommissions || applyFreight`, asegurando que al recargar la orden para edición o facturación no se pierda la configuración de flete.
- **Estructura de Vistas**: Se corrigió un error en `items-grid.blade.php` donde se intentaba leer de forma insegura el nombre del rol del usuario (`roles[0]->name`), lo que causaba un fallo si el usuario actual no tenía roles asignados. Se cambió por una sintaxis segura utilizando opcionales.

### Tests
- **Prueba de Resolución de Flete**: Se agregó la prueba unitaria/de integración `test_freight_resolved_when_only_apply_commissions_is_true` en `HierarchicalCommissionTest.php` para validar que el flete se resuelva y se guarde correctamente tanto en órdenes como en ventas.
- **Aislamiento de Pruebas**: Se solucionó un problema de restauración de base de datos en Livewire (`EloquentModelSynth`) derivado de la persistencia de caché estática en `ConfigurationService` entre ejecuciones de PHPUnit, limpiando la caché en el `setUp()` del test.

## [1.10.132] - 2026-06-10
### Fixed
- **Cálculo Individual de Recargos en Clientes**: Se corrigió un error en los métodos de resolución de recargos (`resolved_commission_percent`, `resolved_freight_percent` y `resolved_exchange_diff_percent`) en los modelos `Sale` y `Order`. Anteriormente, el sistema utilizaba un flag grupal (`$customerHasConfig`) que anulaba la herencia de tasas individuales (como el flete del vendedor) si el cliente tenía cualquier otra tarifa configurada (ej. solo comisión). Ahora la resolución y fallback al vendedor se realiza de manera independiente por cada columna.
- **Sincronización en Interfaz Web (POS)**: Se actualizó la barra lateral de información de tarifas del POS (`sales.blade.php`) para reflejar los porcentajes de recargo con el nuevo comportamiento de fallback individualizado, garantizando la total consistencia con el cálculo del backend y los PDFs de facturación.

### Tests
- **Pruebas de Fallback de Recargos**: Se agregó la prueba unitaria `test_individual_percentage_fallback` en `HierarchicalCommissionTest.php` para validar el comportamiento del fallback individualizado en ventas y pedidos.

## [1.10.131] - 2026-06-09
### Added
- **Agrupamiento en Reporte de Ventas General**: Se agregó la funcionalidad para agrupar las ventas por Fecha, Cliente, Operador (Cajero/Oficina), Vendedor o Chofer/Ruta en la vista web del reporte de ventas general.
- **Columnas Opcionales/Desactivables**: Se implementaron selectores interactivos para mostrar u ocultar las columnas de "Operador" y "Vendedor" tanto en la interfaz web como en el PDF para mayor claridad en pantallas y reportes impresos.
- **Visualización Agrupada en Web**: La tabla en Livewire ahora renderiza sub-tablas con cabeceras que indican el criterio de agrupación y filas de subtotales (Base, recargos y Total) para cada grupo.

### Changed
- **Estructura del PDF**: Se adaptó el generador de PDF en `ReportController` y la vista `general-sales-report-pdf.blade.php` para mantener la misma coherencia de agrupamiento y visibilidad de columnas seleccionada en la vista web.

### Fixed
- **Caracteres Especiales en DomPDF**: Se corrigió un problema donde los emojis de carpetas (📂) causaban que DomPDF renderizara signos de interrogación (`?`) en el PDF de reporte de ventas general. Se reemplazó por un marcador visual unicode compatible.
- **Manejo de Relaciones Nulas**: Se incorporaron validaciones y operadores null-safe en PHP al agrupar por campos de relaciones (vendedor, chofer/ruta) previniendo errores de tipo `null given` cuando un registro de venta carece de vendedor o chofer asociado.

## [1.10.130] - 2026-06-09
### Added
- **Previsualización PDF del Reporte de Ventas General**: Se agregó un botón "Vista Previa PDF" en el panel de opciones del Reporte de Ventas General que abre un modal fullscreen con el PDF embebido en un iframe, idéntico en UX al Reporte de Ventas Diarias.
- **Plantilla PDF**: Nueva plantilla `general-sales-report-pdf.blade.php` en orientación landscape con encabezado de empresa, período, filtros aplicados, tabla de transacciones con desglose de recargos (Base, %, Comisión, Flete, Dif., Total, Crédito, Artículos, Estatus, Tipo, Fecha) con porcentajes individuales configurados y fila de totales.
- **Ruta y Controlador**: Nueva ruta `reports/sales/pdf` y método `generalSalesPdf()` en `ReportController` que replica la misma lógica de filtrado y cálculo de recargos del componente Livewire.
- **Resumen en PDF**: Bloque de resumen con totales de facturas, artículos, base USD, ventas USD, crédito pendiente y desglose contado/crédito.

### Tests
- Se agregaron 4 pruebas de feature en `GeneralSalesReportPdfTest`: respuesta HTTP 200, content-type PDF, filtros (usuario, vendedor, cliente, tipo, chofer) y manejo de reporte sin ventas.

## [1.10.129] - 2026-06-08
### Fixed
- **Porcentaje Efectivo de Recargos en Reporte de Ventas General**: Se corrigió la columna `%` del reporte de ventas general para que calcule y muestre el porcentaje efectivo compuesto para ventas secuenciales (`(1 + (Comisión% + Flete%)/100) × (1 + Dif%/100) − 1`) en lugar de la suma simple incorrecta. Para ventas anteriores a la fecha de corte se mantiene el cálculo aditivo original.
- **Porcentaje Configurado por Recargo**: Las columnas de Comisión, Flete y Dif. del reporte ahora muestran el porcentaje de configuración aplicado debajo del monto (`(X.X%)`), permitiendo auditar exactamente con qué tasa fue calculado cada recargo.

### Changed
- **Diferencial Secuencial**: Para ventas secuenciales, el monto de diferencial en el reporte se recalcula correctamente como `(Base + Comisión + Flete) × Dif%` en lugar de `Base × Dif%`, alineando el desglose con el total real de la factura.

### Tests
- Se añadió la prueba `test_sales_report_renders_correct_surcharge_percentages` al test `SequentialCutOffSettingTest` validando que el componente Livewire `SalesReport` renderiza correctamente el 59.0% aditivo para ventas históricas y el 56.6% compuesto para ventas secuenciales, junto con los porcentajes individuales de configuración.

## [1.10.128] - 2026-06-08
### Fixed
- **Representación de Base en USD**: Se corrigió el cálculo de la base y recargos físicos (`base_amount`, etc.) para ventas en monedas secundarias (VED y COP). Anteriormente, la migración acumulaba el total usando `regular_price` (que está en moneda local), guardando la base en bolívares/pesos en vez de dólares. Se actualizó la migración original para usar `price_usd * quantity` y se programó una nueva migración correctora (`fix_surcharges_currency_mismatch_in_sales`) que convierte en lotes (`chunkById`) todos los montos de recargos históricos erróneos dividiéndolos por la tasa de cambio de la venta.
- Se agregó una salvaguarda visual en el Reporte de Ventas General para autoconvertir la visualización a dólares si el valor de base física es mayor al total en dólares (debido a que se guardó en moneda local).

## [1.10.127] - 2026-06-08
### Changed
- **Exclusión de Reporte de Ventas Diarias**: Se revirtieron los cambios en la tabla del Reporte de Ventas Diarias para mantener su diseño original de desglose de pagos por moneda, limitando las columnas de desglose de recargos físicos (`Base`, `%`, `Comisión`, `Flete`, `Dif.`, `Total`, `Crédito`) exclusivamente al **Reporte de Ventas General**.

## [1.10.126] - 2026-06-08
### Changed
- **Columnas de Desglose de Recargos en Reportes de Ventas**: Se reemplazaron las columnas de desglose de pagos por moneda (`Pagado USD`, `Pagado VED`, `Pagado COP`, `Pagado VED`) en la tabla de reportes de ventas (Reporte de Ventas Diarias y Reporte de Ventas General) por columnas que muestran el desglose de recargos físicos: `Base`, `%` (porcentaje acumulado de recargos), `Comisión`, `Flete`, `Dif.` (diferencial de cambio), `Total` y `Crédito (USD)`.
- Se programó un bloque de cálculo inverso retroactivo si el campo de base físico (`base_amount`) en la base de datos está en cero para ventas antiguas, respetando la fecha de corte configurada.

## [1.10.125] - 2026-06-08
### Fixed
- **Visualización de Opciones en Tabla de Órdenes**: Se agregó `data-boundary="viewport"` al botón desplegable de opciones en la tabla de órdenes de [process-order.blade.php](file:///c:/laragon/www/jspos-sales/resources/views/livewire/pos/partials/process-order.blade.php). Esto evita que el menú de acciones (Ver Detalles, Editar Nota, Historial, etc.) se recorte o quede inaccesible debido al desbordamiento y scroll horizontal de la tabla responsiva.

## [1.10.124] - 2026-06-08
### Added
- **Campos Físicos de Desglose de Recargos en Ventas y Pedidos**: Se agregaron las columnas `base_amount`, `commission_amount`, `freight_amount` y `exchange_diff_amount` a las tablas `sales` y `orders` para almacenar de forma exacta y en caliente los desgloses en USD. Esto soluciona problemas de redondeo por cálculo inverso y optimiza drásticamente el rendimiento permitiendo realizar sumatorias directas en SQL.
- **Meta de Ruta para Choferes y Panel de Órdenes**: Se agregó la columna `route_goal` en usuarios y se expuso en la interfaz administrativa para roles tipo Chofer. En el panel de órdenes se incluyó un filtro por chofer, una barra de progreso visual de cumplimiento de meta y una tarjeta de totales generales desglosados en una grilla de alta calidad.
- **Fecha de Corte Dinámica para Cálculo de Recargos**: Se agregó el campo `sequential_cut_off_date` en la tabla `configurations` y se expuso un selector `datetime-local` en la pestaña General de Configuración del Sistema. Esto permite definir dinámicamente desde cuándo entra en vigencia la fórmula de recargos secuencial por cliente, manteniendo coherencia retroactiva con el historial anterior (fórmula aditiva).
- **Lógica de Migración y Backfill Automático**: Se programó en la migración de base de datos un script chunked (en lotes de 100 registros) que calcula y rellena retroactivamente las nuevas columnas físicas de base y recargos para todas las órdenes y ventas históricas preexistentes en la base de datos sin requerir intervención del usuario.
- **Suite de Pruebas**: Se incorporaron pruebas unitarias y de feature automatizadas (`tests/Feature/OrderRouteGoalTest.php` y `tests/Feature/SequentialCutOffSettingTest.php`) para validar el guardado de metas, sumatorias en Livewire, persistencia de desgloses y transiciones dinámicas de fecha.

### Changed
- **Formatos de Impresión Ticket y PDF**: Se reemplazaron todas las comparaciones de fecha de corte fijas de `'2026-06-03 00:00:00'` por la fecha dinámica de la configuración.

## [1.10.122] - 2026-06-08
### Added
- **Compartido de Portafolios de Clientes (Vendedores Foráneos)**: Se implementó un sistema de asignación de carteras de clientes compartidas entre vendedores. A través de la nueva pestaña **Cartera Compartida** en la edición de usuarios (Panel Web), los administradores pueden asociar carteras de otros vendedores a un vendedor. El sistema filtra de manera dinámica en la API (`/api/customers`, `/api/sales/pending`, `/api/seller/dashboard`) y en la Web (clientes, autocompletado y estados de cuenta) utilizando los IDs compartidos, asegurando que ambos vendedores tengan visibilidad y puedan registrar pedidos/pagos de manera unificada y transparente sin necesidad de actualizar la APK.
- **Suite de Pruebas**: Se agregó el archivo `tests/Feature/SellerPortfolioSharingTest.php` para validar el comportamiento del compartido bajo condiciones normales y de asignación activa.

## [1.10.121] - 2026-06-06
### Fixed
- **Conciliación de Arqueo Detallado de Caja y Relación de Cobros**: Se modificaron las consultas de cobros por abonos a crédito en `cashCountPdf` y `cashCountDetailedPdf` en `ReportController.php`. Anteriormente, se filtraban por la fecha de creación física del pago (`created_at`), lo que provocaba que abonos creados en días anteriores en estado pendiente pero aprobados en el día del arqueo no aparecieran en el reporte de caja diaria (a pesar de pertenecer a la planilla del día y haber sido validados y cobrados ese día). Ahora, se recuperan los cobros vinculados a las planillas de cobranza (`collection_sheet_id`) abiertas durante el rango de fechas seleccionado, garantizando una coincidencia exacta y mostrando las fechas reales de transferencia en la columna **F. Voucher** para facilitar la auditoría bancaria.

## [1.10.120] - 2026-05-29
### Added
- **Nomenclatura TP y PGD en Código de Facturas**: Se agregaron sufijos en MAYÚSCULAS al final del código comercial impreso en el pie de página de facturas (`PdfInvoiceTrait.php`) y pedidos (`PdfOrderInvoiceTrait.php`). El sufijo `TP` indica la política de pago autorizada (`TP$0BNC`, `TPBSBCV` o `TPCOP`), y el sufijo `PGD` resume los métodos de pago reales usados (`PGD$0`, `PGDBNC`, `PGDBCV`, `PGDCOP` o su combinación), omitiéndose completamente en facturas a crédito pendientes para facilitar auditorías instantáneas en caja.

### Fixed
- **Resolución Jerárquica del Vendedor**: Se corrigió el error en los PDFs de facturas y pedidos que impedía cargar los fletes, comisiones y diferenciales del vendedor cuando el cliente no tenía configuración comercial propia. El sistema ahora resuelve al vendedor correcto (`$customer->seller` o `$sale->sellerConfig`) en lugar de caer en el cajero operador (`$sale->user`), quien no posee perfil comercial.
- **Unificación de Prioridad Comercial de Cliente (Tarjeta UI y PDFs)**: Se sincronizó la interfaz del POS (`sales.blade.php`) y los motores PDF para alinearse con la regla del backend: si el cliente tiene al menos una tarifa configurada superior al 0% (flete, comisión o diferencial), se usan exclusivamente las tarifas del cliente para todo, eliminando visualizaciones mezcladas de cliente y vendedor.
- **Corrección de Excepción SQL (Incorrect decimal value: '')**: Se corrigió un error crítico de SQLSTATE en la creación de historiales de clientes (`Customers.php`) y vendedores (`Users.php`) al guardar campos comerciales vacíos. Livewire vincula los campos borrados como cadenas vacías `""` (las cuales pasaban el operador `??`); se implementó una validación con `is_numeric` para que los inputs vacíos se graben de manera segura como `0`.

## [1.10.119] - 2026-05-29
### Fixed
- **Conciliación de Bancos en Arqueo Detallado PDF (Reference Leak)**: Se corrigió un error crítico de desbordamiento de memoria por fuga de referencia PHP (`$currenciesInBank` y `$items`) en `ReportController.php`. Este error causaba que, al generar el reporte PDF del Informe Detallado de Caja, la sección de `BANCO DE VENEZUELA` duplicara exactamente las transacciones, referencias y montos de `BANCO PROVINCIAL`, ocultando al mismo tiempo el pago real legítimo del Banco de Venezuela por **29,443.31 VED**. Se implementó la llamada segura a `unset($currenciesInBank)` y `unset($items)` inmediatamente después de finalizar cada ciclo de ordenación para desligar los punteros de memoria, resolviendo la duplicación de datos de forma impecable y garantizando la total precisión financiera del reporte.

## [1.10.118] - 2026-05-29
### Fixed
- **Impresión de Fechas en Reportes y Facturas PDF (Reimpresión)**: Se corrigió un error crítico en los constructores de PDFs (Ventas Contado/Crédito, Órdenes de Pedido Pendientes/Procesadas, Ajustes de Almacén - Cargos y Descargos, y Órdenes de Compra) que causaba que al reimprimir documentos históricos aparecieran con la fecha actual del sistema. Se configuraron explícitamente los constructores para inyectar la fecha original del registro (`created_at`) al generador de `Jhosagid\Invoices\Invoice`, previniendo que caiga en la fecha de ejecución por defecto.
- **Suite de Pruebas**: Se incorporó el archivo de pruebas `tests/Feature/PdfInvoiceDateTest.php` para validar y certificar de manera automatizada que los 5 principales tipos de comprobantes renderizan correctamente la fecha de emisión original.

## [1.10.117] - 2026-05-28
### Fixed
- **Relación de Cobros (Agrupación de Pagos en Efectivo)**: Se corrigió un bug del sistema en la generación del PDF de Relación de Cobros (`collection-relationship-new-pdf.blade.php`). Anteriormente, al agrupar los pagos en efectivo de una venta, si existía **cualquier** pago anulado en esa misma venta, todo el grupo de efectivo se marcaba erróneamente como anulado `[ANULADO]` (tachando la fila entera y forzando a `0` el monto de ingreso), lo que provocaba que los pagos en efectivo válidos y aprobados posteriores quedaran ocultos o no sumados en el reporte. Ahora se agrupan los pagos en efectivo tanto por la venta como por su **estado/status**, separándolos en filas independientes en el PDF para garantizar la total precisión de los totales de ingreso.

## [1.10.116] - 2026-05-28
### Fixed
- **Botonera de Pagos y Cobros (Comillas en Nombres)**: Se corrigió un error crítico que impedía que abrieran los modales de registro de abonos y cuentas por cobrar/pagar cuando el nombre del cliente o proveedor contenía comillas simples (ej. `E-70. D' SANTIAGO C.A`). Se refactorizó la comunicación de Livewire para resolver dinámicamente los nombres desde los modelos de base de datos en el backend, eliminando por completo cualquier interpolación de caracteres conflictivos en el JavaScript de las vistas Blade.


## [1.10.115] - 2026-05-28

### Added
- **Filtros Rápidos Interactivos en Tarjetas**: Las tarjetas del panel superior de aprobaciones ahora actúan como filtros rápidos al hacer clic sobre ellas. Al pulsar una tarjeta (ej. `Rechazadas`), la tabla de abajo se actualiza instantáneamente con esos registros, aplicando además un borde y sombra de color de alta calidad sobre la tarjeta activa para una experiencia de usuario sumamente fluida.

## [1.10.114] - 2026-05-28
### Added
- **Tablero Remoto con 5 Tarjetas Métricas**: Se amplió y rediseñó el resumen superior del panel de aprobaciones en `/consultation/approvals`, incorporando 5 tarjetas horizontales premium: `Pendientes`, `Aprobadas (Caja)`, `Cobradas Hoy` (estado `'used'`), `Rechazadas` (control y auditoría), y `Tasa Promedio Aprobada` para brindar un control total del día al supervisor.

## [1.10.113] - 2026-05-28
### Fixed
- **Visibilidad del Menú Padre de Finanzas**: Se corrigió el condicional de permisos (`@canany`) del menú principal **FINANZAS Y AUDITORÍA** en `sidebar.blade.php`, agregando el permiso `payments.approve_custom_rate` para que el menú de aprobaciones sea visible para supervisores que no tengan otros permisos financieros heredados.

## [1.10.112] - 2026-05-28
### Added
- **Migración de Base de Datos para el Permiso**: Se incorporó una migración de base de datos (`2026_05_28_105435_add_payments_approve_custom_rate_permission.php`) para registrar el permiso `payments.approve_custom_rate` y asignarlo automáticamente al rol `Super Admin`.
- **Traducción del Permiso**: Se agregó la traducción al español del permiso bajo la llave `payments_approve_custom_rate` como "Aprobar Tasa Especial" en `lang/es/permissions.php` para que se visualice y administre correctamente agrupado en el módulo de Asignación de Roles y Permisos.

## [1.10.111] - 2026-05-28
### Added
- **Auto-Aprobación de Tasas**: Integración de un flujo que detecta si el cajero posee el rol `Super Admin` o el permiso `payments.approve_custom_rate`, permitiéndole auto-aprobar al instante la tasa manual propuesta en la misma vista de pago mediante un botón destacado verde "Auto-Aprobar Tasa".
- **Tablero Remoto de Supervisor**: Creación de un panel web en `/consultation/approvals` ("Aprobación de Tasas" en la barra lateral bajo "Auditoría Pagos") para que los supervisores autorizados consulten y autoricen en tiempo real las solicitudes de tasa especial con polling de 8 segundos.
- **Autorización por Código OTP de 6 Dígitos (Offline)**: Generación automática de un código numérico único de 6 dígitos enviado por correo a los supervisores. Los operadores pueden ingresar este código directamente en la caja para autorizar la tasa especial sin que el supervisor deba acceder al panel web.
- **Notificación Inteligente por Correo**: Despacho global automatizado de solicitudes únicamente a usuarios con rol `Super Admin` o permiso `payments.approve_custom_rate` (excluyendo a administradores generales) mediante la plantilla premium `ExchangeRateApprovalRequested` destacando el código OTP de 6 dígitos.

### Changed
- **Formulario de Tasa Especial Simplificado**: Se eliminó por completo el dropdown selector de supervisor en sitio, logrando una interfaz de solicitud remota mucho más limpia y ágil.

## [1.10.110] - 2026-05-27
### Added
- **Restricción de Tasas de Cambio**: Se bloqueó el uso de la tasa oficial BCV para liquidar abonos a facturas de crédito denominadas en divisas puras (aquellas configuradas con diferencial del 0%), exigiendo utilizar únicamente tasas Binance (paralelas).
- **Selector de Tasa Inteligente y Reactivo**: Reemplazo de los inputs libres de tasa en efectivo y bancos por dropdowns estrictos basados en el historial del día del voucher. Se añadió el listener reactivo para que al cambiar la fecha del banco, las tasas cargadas se actualicen dinámicamente según ese día.
- **Bucle de Aprobación de Supervisor**: Flujo para que el operador solicite tasas personalizadas ingresando una justificación obligatoria. Permite aprobación remota (Livewire poll cada 5 seg) o local ingresando email y contraseña del supervisor en el modal en sitio, generando un token UUID de un solo uso.
- **Indicadores de Estado de Factura**: Incorporación de distintivos visuales de color debajo de la tasa de cambio (`bg-warning` y `bg-success`) indicando explícitamente si se trata de una factura en divisas pura (Tasa BCV bloqueada) o factura con diferencial (Tasa BCV permitida) para dar máxima seguridad al operador.
- **Botón Despachar en Traspasos**: Se agregó el botón `🚚 Despachar` en la lista web de traspasos pendientes. Esto resuelve el bloqueo de traspasos de almacenes sin aplicación móvil (como ZONA), permitiendo descontar stock del origen en la web y habilitando su inmediata recepción/aprobación.

## [1.10.109] - 2026-05-26
### Changed
- **Campos y Columnas de Zelle**: Se removieron las columnas `Procedencia` y `Tipo de Pago` del bloque de Zelle, y se reemplazaron con el campo `Quien Envía` (nombre del remitente titular de la cuenta Zelle).
- **Desglose de Montos en Zelle**: Se separó el flujo de dinero en dos columnas explícitas en USD: `Monto Zelle` (monto total original del voucher/recibo) y `Monto Usado` (monto consumido del Zelle para este pago particular), facilitando las búsquedas y auditorías de saldos reutilizables.
- **Simplificación de Bancos**: Se eliminó la columna `Tipo de Pago` de las tablas bancarias en el reporte detallado para limpiar el diseño visual y dar mayor espacio a los datos clave como cliente y referencias.

## [1.10.108] - 2026-05-26
### Fixed
- **Caracteres de Emojis en DomPDF**: Se eliminaron los emojis de todos los títulos y subtítulos del reporte detallado de Arqueo de Caja para evitar el signo de interrogación `?` generado por la falta de soporte de fuentes en la renderización PDF.
- **Normalización y Coincidencia de Bancos**: Se implementó una lógica de coincidencia y normalización case-insensitive y libre de caracteres especiales para asociar de manera infalible las transacciones con las cuentas bancarias de la base de datos, garantizando que los últimos 6 dígitos del número de cuenta se impriman siempre en el reporte.

## [1.10.107] - 2026-05-26
### Changed
- **Botones de Arqueo de Caja**: Se restauró la visualización simultánea de todos los botones de acción (`Imprimir Corte`, `Ver PDF` y `Previsualizar`) lado a lado en lugar de ocultar el reporte de PDF estándar cuando se activa el reporte detallado.
- **Visualización de Cuenta Bancaria**: Se agregó soporte para buscar y mostrar los últimos 6 dígitos del número de cuenta de los bancos configurados (ej: `BANCO (*123456)`) en las cabeceras de bancos del reporte detallado de Arqueo de Caja, permitiendo segregar e identificar saldos de forma inequívoca cuando existen múltiples cuentas de una misma entidad.

## [1.10.106] - 2026-05-26
### Added
- **Arqueo de Caja (Reporte PDF Detallado - Conciliación Bancaria)**: Se implementó un nuevo reporte PDF de Arqueo Detallado (`cash-count-detailed-pdf.blade.php`) que complementa al reporte resumido existente, diseñado con la estética de la Relación de Cobros.
- **Switches de Configuración UI (Livewire)**:
  - `Ver Reporte Detallado`: Alterna entre el PDF resumido y el nuevo detallado.
  - `Mostrar Resumen de Efectivo`: Permite incluir u ocultar el desglose de efectivo (CASH) para evitar ruido en la conciliación.
  - `Unificar Ventas y Créditos`: Permite consolidar bancos/Zelle en un único bloque cronológico o mantenerlos separados.
- **Auditoría e Inyección de Transacciones**:
  - Clasificación automática del tipo de cobro a crédito (`Abono Parcial`, `Cancelación Deuda`, `Pago Completo`).
  - Extracción y ordenamiento por fecha real física del voucher (bouche/capture) y visualización del número de factura asociada a cada pago.
- **Botón Premium**: Añadido el botón de ojo azul `Previsualizar` en el arqueo para abrir el visualizador de corte de caja detallado.

## [1.10.105] - 2026-05-26
### Added
- **Reportes de Auditoría de Pagos (Fechas de Voucher/Bouche)**: Se implementó la visualización de la fecha real física de pago (fecha del voucher o capture física seleccionada por el usuario al registrar el cobro) en lugar de la fecha de registro en el sistema en las columnas de **Descripción** de dos reportes PDF: **Relación de Cobros** (`collection-relationship-new-pdf.blade.php`) y **Reporte de Ventas Diarias** (`daily-sales-report-new-pdf.blade.php`).
- **Lógica de Fechas Condicional por Método de Pago**:
  - Para pagos en **Efectivo (CASH)**: Se muestra la fecha de registro del pago en el sistema (`F. Registro: DD/MM/AAAA`).
  - Para pagos por **Zelle**: Se extrae y muestra la fecha del capture de Zelle (`F. Voucher: DD/MM/AAAA`).
  - Para pagos por **Banco/Depósito**: Se extrae y muestra la fecha física del voucher registrada en el formulario (`F. Voucher: DD/MM/AAAA`).

## [1.10.104] - 2026-05-25
### Added
- **Reporte de Ajustes e Impresión (Marca de Agua)**: Se implementó una marca de agua diagonal translúcida con la palabra `"PENDIENTE"` en los PDFs de Cargos (Ajustes de Entrada), Descargos (Ajustes de Salida) y Compras (Órdenes de Compra) únicamente cuando la transacción se encuentra en estado pendiente.
- **Auditoría e Inyección de Estado**: Se adaptó el flujo de datos para inyectar dinámicamente el estado (`status`) de los registros en los metadatos de impresión de los controladores `CargoController`, `DescargoController` y `PurchaseController`.

## [1.10.103] - 2026-05-22
### Added
- **Auditoría de Pagos (Zelle)**: Se implementó una nueva herramienta de generación de reportes consolidados en PDF ("PDF de Capturas Filtradas"). Esta función permite exportar un único archivo que compila todas las transacciones de Zelle filtradas (según rango de fechas, estado y remitente), mostrando de forma ordenada la información detallada del pago y el comprobante o captura de pantalla correspondiente en tamaño completo al lado de cada registro.

## [1.10.102] - 2026-05-22
### Added
- **Productos (Visibilidad y Ventas)**: Se implementó un nuevo interruptor (Switch/Toggle) en la pestaña "Generales" del formulario de creación y edición de productos ("Mostrar en Área de Ventas y Listas de Precios"). Esto permite a los administradores desactivar (OFF) la visibilidad de cualquier producto.
- **Punto de Venta (Web POS)**: Se actualizó el buscador del POS web para filtrar y omitir automáticamente cualquier producto que tenga la visibilidad desactivada (`show_in_sales = false`).
- **Aplicaciones Móviles (Preventa y Clientes VIP)**: Se restringieron los endpoints de la API móvil de vendedores (`Api/ProductController`) y de clientes VIP (`Api/Vip/ProductController`) para que solo muestren y busquen productos con visibilidad activa, previniendo que aparezcan en el catálogo o inventario de la app.
- **Generador de Listas de Precios**: Se actualizó la consulta en `PriceListGenerator` para excluir de forma automática los productos que tengan el switch en OFF, de modo que no se muestren en la cuadrícula de previsualización ni en los PDFs generados para clientes.

## [1.10.101] - 2026-05-22
### Fixed
- **Logística (Traspasos - Web)**: Se corrigió un error en el buscador de productos del formulario web de creación de traspasos (`Transfers.php`). Anteriormente, la consulta utilizaba un filtro rígido `like '%término%'` que no encontraba productos si el nombre contenía espacios consecutivos en la base de datos (como `"ENVASE PET 500ML  200UND"`, el cual tiene dos espacios). Se refactorizó el buscador para utilizar el método global de búsqueda inteligente del modelo `Product::search()`, logrando compatibilidad total con búsquedas predictivas por token, tolerando variaciones de espacios y ordenando por relevancia.

## [1.10.100] - 2026-05-22
### Added
- **Soplados (App Móvil)**: Se incrementó la versión a `1.16.1+9` y se implementó un sistema de notificaciones de error visuales robusto (SnackBar) para la carga de inventario y recibos pendientes. Si el servidor devuelve un error (como `403` por dispositivo no autorizado, `401` por sesión expirada o fallos de conexión de red), la aplicación ya no fallará en silencio dejando la interfaz vacía, sino que alertará de forma inmediata al operador con el código y descripción exacta del problema. Esto permite diagnosticar de manera instantánea fallas de red, problemas de autorización de dispositivos o configuraciones incorrectas de URL.
- **Soplados (App Móvil - Distribución)**: Se compiló y generó la nueva versión de producción del APK optimizada con soporte completo de 64 bits (`JSPOS_Mobile_Soplados_v1.16.1_AppManufactura_SuWeb.apk`) copiada a la raíz del proyecto para facilitar su descarga e instalación inmediata.

## [1.10.99] - 2026-05-22
### Added
- **Soplados (Logística / Traspasos)**: Se implementó la regla de validación para traspasos de inventario (Fase 3). Ahora, cualquier traspaso registrado desde el almacén de Soplados hacia la Tienda Principal (Almacén General) se valida estrictamente en el backend. Si se intenta traspasar una cantidad fraccionada (con decimales) para cualquier producto, el sistema bloqueará la transacción arrojando una alerta indicando que solo se permiten bultos o unidades completas (enteras), garantizando que las unidades sueltas remanentes se queden siempre bajo el control de la planta de producción.

## [1.10.98] - 2026-05-22
### Added
- **Soplados (App Móvil)**: Se implementó la interfaz de doble entrada para productos de embalaje (bultos) en el modal de registro de producción. Ahora la app móvil detecta automáticamente si el producto requiere empaque basado en su equivalencia (si es > 1 unidad, ej: 200). Muestra dos campos numéricos independientes ("Bultos" y "Unidades Sueltas"), calcula el decimal exacto en tiempo real y lo envía de forma nativa al backend para realizar el descuento correcto de preformas.
- **Soplados (App Móvil)**: Se mejoró la tarjeta visual del listado de producción agregada para mostrar de manera clara el desglose ("X.XXXX bultos (Y uds)") para los productos empacados, elevando la experiencia de usuario (UX) de los operadores.

## [1.10.97] - 2026-05-22
### Fixed
- **Soplados**: Se solucionó un error fatal de ejecución (`Attempt to read property "name" on null`) en el listado de recetas (fórmulas) de Soplados (`formulas.blade.php`). El error ocurría al intentar mostrar recetas con productos o ingredientes que habían sido eliminados (soft-deleted). Se integró `withTrashed()` en las relaciones del modelo `ProductionFormula` y validaciones null-safe (`??`) en la plantilla Blade.

## [1.10.96] - 2026-05-22
### Fixed
- **Dashboard**: Se corrigió un error fatal de renderizado (`Attempt to read property "childNodes" on null`) causado por incompatibilidad de las directivas `@script` de Livewire 3 con el DOM del Dashboard. Se refactorizó la inicialización utilizando el evento global `livewire:navigated` para garantizar que las gráficas se pinten sin romper la estructura HTML del componente.

## [1.10.95] - 2026-05-22
### Fixed
- **Dashboard**: Se ajustó la integración de la librería de gráficas Highcharts utilizando las directivas `@assets` y `@script` propias de Livewire 3 para asegurar compatibilidad absoluta con el DOM virtual y garantizar el renderizado correcto de las gráficas bajo cualquier flujo de navegación.

## [1.10.94] - 2026-05-22
### Fixed
- **Dashboard**: Se resolvió definitivamente el problema de las gráficas en blanco. El conflicto ocurría durante la navegación rápida (SPA) donde el evento de inicio de Livewire ya se había disparado previamente, dejando las gráficas sin ejecutar. Ahora las gráficas se auto-ejecutan directamente al momento de renderizar el componente.

## [1.10.93] - 2026-05-21
### Fixed
- **Dashboard**: Se corrigió un error que provocaba que las gráficas de "Ventas vs Ganancias" y "Top Vendedores" no se renderizaran (quedaran en blanco) al cargar el Dashboard, debido a un conflicto de sincronización en la carga de la librería de gráficas Highcharts.

## [1.10.92] - 2026-05-21
### Changed
- **Impresión de Etiquetas**: Se incrementó el tamaño de la fuente de los textos informativos ("Operador" y "Fecha") y se aplicó formato de texto en negrita al mes y año preimpresos para mejorar su legibilidad al momento de imprimir.

## [1.10.91] - 2026-05-21
### Added
- **Impresión de Etiquetas**: Se actualizó el formato de la etiqueta de productos generada en PDF. Ahora la fecha preimprime automáticamente el mes y el año actual (`____/mm/yyyy`), dejando únicamente la línea en blanco para el día.

## [1.10.90] - 2026-05-21
### Fixed
- **Historial de Pagos**: Se solucionó un error crítico (`Call to undefined relationship [currency]`) que provocaba que el sistema colapsara al intentar cargar la vista del historial de pagos o facturas cobradas (especialmente al registrar abonos o pagos en monedas como bolívares). Se añadió la relación faltante en el modelo `SalePaymentDetail`.

## [1.10.89] - 2026-05-21
### Fixed
- **Configuración de Venta (Comisión, Flete, Diferencial)**: Se corrigió la lógica de asignación jerárquica de comisiones en el sistema de Ventas. Ahora, si el cliente tiene configurado *cualquiera* de los tres valores (comisión, flete o diferencial) mayor a cero, el sistema usará en bloque la configuración completa del cliente y anulará la del vendedor, evitando mezclas de porcentajes entre ambos perfiles.

## [1.10.88] - 2026-05-21
### Added
- **Reporte de Relación de Despacho**: Se actualizó la vista del PDF del reporte para mostrar explícitamente el total de la columna "Base" en las filas de "Subtotal Vendedor" y "TOTAL CHOFER", permitiendo una auditoría más rápida de los montos base.

## [1.10.87] - 2026-05-20
### Fixed
- **Despliegue y Actualización Automática**: Se integró `composer install` dentro del middleware `AutoMigrate` para asegurar que los clientes que actualizan por sistema reciban automáticamente las nuevas dependencias sin usar la consola.
- **Reporte de Auditoría**: Se corrigió un error en el componente Livewire `AuditReport` donde la clase `Activity` no estaba importada correctamente, provocando fallos en pantallas de clientes.

## [1.10.86] - 2026-05-20
### Added
- **Auditoría de Stock (Etiquetado de Origen)**: Se implementó un nuevo clasificador visual en el reporte de Auditoría para diferenciar automáticamente si un cambio de stock provino de una transacción operativa o de una alteración directa.
    - **EDICIÓN MANUAL**: Aparecerá en color rojo cuando un usuario modifique el inventario, el costo o el precio forzando el valor desde el formulario de edición directa del producto.
    - **SISTEMA**: Aparecerá en color verde cuando el stock se modifique como resultado de una operación formal (Ventas, Compras, Cargos, Descargos, Traspasos, etc.), delegando la revisión detallada (como cliente o nro. de documento) al reporte de Kardex.

## [1.10.85] - 2026-05-20
### Added
- **Auditoría de Sistema**: Nuevo módulo "Auditoría de Stock" (`reports/audit`) para rastrear inserciones, actualizaciones y modificaciones de stock en los productos y depósitos.
- **Permisos Granulares**: Se agregaron nuevos permisos para proteger vistas y acciones:
    - `reports.audit`: Acceso al módulo de Auditoría.
    - `products.edit.inventory`: Permite modificar la pestaña de Inventario (Stock y Alertas).
    - `products.edit.categories`: Permite modificar la pestaña de Categorización.
    - `products.edit.price_rules`: Permite modificar la pestaña de Reglas de Precio.
- **Visualización sin Edición**: Ahora las pestañas protegidas dentro de la edición del producto siempre son visibles, pero el formulario se bloquea (`disabled`) si el usuario no tiene el permiso correspondiente.
### Fixed
- **Edición de Producto**: Se corrigió el bug que causaba que el sistema obligara al usuario a seleccionar nuevamente la Categoría al editar un producto debido a una falta de asignación en el componente Livewire.

## [1.10.84] - 2026-05-18
### Fixed
- **Reporte de Movimientos de Kardex**:
    - **Sincronización por Venta Anulada (Reingreso)**: Implementación de un movimiento de reingreso (Entrada) en la fecha exacta en la que se anula o elimina una factura, asegurando consistencia matemática completa entre el Kardex calculado y el stock físico de almacenes.
    - **Filtro de Devoluciones**: Se restringió la consulta de devoluciones en el reporte para incluir únicamente las Notas de Crédito aprobadas (`approved`), previniendo desbalances por devoluciones pendientes o rechazadas.
- **Aprobación de Devoluciones**:
    - **Sincronización de Stock Global**: Se corrigieron los flujos de aprobación en los componentes de Devoluciones, Historial de Pagos y Reporte Histórico para incrementar correctamente el stock global del producto (`products.stock_qty`) al mismo tiempo que el stock físico de cada almacén.

## [1.10.83] - 2026-05-12
### Added
- **Reporte de Inventario / Stock Optimizado**:
    - **Multi-Depósito Dinámico**: Ahora permite seleccionar uno o varios depósitos para comparar el stock lado a lado en la tabla.
    - **Búsqueda Inteligente**: Implementada búsqueda por tokens (palabras sueltas). Ejemplo: "azul botellon" encontrará "BOTELLON AZUL".
    - **Selección Manual para Impresión**: Añadidos checkboxes para seleccionar productos específicos. La selección es acumulativa y persiste al cambiar de búsqueda.
    - **Vista de Seleccionados**: Nuevo interruptor "Ver Solo Seleccionados" para depurar la lista antes de generar el PDF.
    - **PDF Dinámico**: El reporte impreso ahora refleja exactamente la selección de depósitos y productos. Cambia automáticamente a orientación horizontal si se seleccionan más de 2 depósitos.
### Fixed
- **Módulo de Cargos**: Corregido error `TypeError: string * string` en PHP 8.2 al realizar cálculos de costos y cantidades en el almacén. Se implementó tipado numérico estricto (float casting) para prevenir crashes.

## [1.10.82] - 2026-05-11
### Added
- **Módulo de Historial de Producción**: 
    - **Web**: Nueva columna de "Estadísticas / Rendimiento" en la tabla de reportes, mostrando Yield, Buenos y Merma por cada registro.
    - **App**: Nuevo botón de "Historial de Producción" en el dashboard móvil con filtros por fecha y vista detallada de cada registro (incluyendo insumos y productos resultantes).
    - **Backend**: Endpoint de historial optimizado con cálculos de rendimiento integrados.

## [1.10.81] - 2026-05-11
### Fixed
- **Estadísticas del Dashboard**: Corregida la lógica de cálculo en el dashboard móvil. Ahora el rendimiento (Yield), los productos buenos y la merma se calculan correctamente basándose en los registros de producción del turno actual.

## [1.10.80] - 2026-05-11
### Fixed
- **Visibilidad de Productos en Producción**: Se implementó el auto-etiquetado de productos al configurar recetas. Ahora, cualquier producto con fórmula aparecerá automáticamente en la App sin necesidad de etiquetas manuales.
- **Deducción de Insumos**: Verificada y estabilizada la lógica de descuento de inventario al registrar producción.
- **Habilitación de Botón Registrar**: El botón de la App ya no aparecerá deshabilitado si existen recetas configuradas en el panel.

## [1.10.79] - 2026-05-11
### Added
- **Recepción Parcial en App**: Ahora el operador puede editar la cantidad recibida de cada insumo y agregar un motivo de rechazo/faltante si no llegó la cantidad completa.
- **Gestión de Stock Precisa**: El stock solo se carga a planta por la cantidad real confirmada en la App, registrando el resto como cantidad rechazada en el sistema.

## [1.10.78] - 2026-05-11
### Fixed
- **Rutas Web Soplados**: Eliminada restricción de módulo que impedía al administrador ver el panel de "Fórmulas" y "Turnos".
- **Clasificación por Palabras Clave**: Añadido fallback para clasificar como Insumo si el nombre contiene (PREFORMA, TAPA, etc.), incluso sin fórmulas configuradas.
- **Mensajería Coherente**: Actualizados mensajes de error en la App para apuntar correctamente a la ruta del panel web y explicar qué falta.
- **Permisos Admin**: Verificado y asegurado que los roles Admin/Supervisor tengan acceso total al módulo de Soplados.

## [1.10.77] - 2026-05-11
### Fixed
- **Sincronización de Almacén**: Corregido error que vinculaba la App de Soplados a la Tienda Principal por falta de configuración. Ahora apunta correctamente a "Planta Soplados".
- **Categorización de Productos**: Las preformas ahora se clasifican correctamente como **Insumo/Materia Prima** y no como Producto Terminado.
- **Alertas de Recepción**: Añadida notificación visual (badge) y banner de alerta cuando hay traspasos pendientes de recibir en planta (ej. las 10,000 preformas).

## [1.10.76] - 2026-05-11
### Added
- **App Soplados (Inventario de Planta)**: 
    - Nuevo módulo de **Inventario Real**: Los operadores pueden ver el stock actual de insumos (preformas, tapas) y productos terminados en su almacén.
    - Sistema de **Recepción de Insumos**: Flujo para confirmar la llegada de materia prima desde el almacén central a la planta.
    - **Dashboard de Rendimiento**: Visualización en tiempo real de unidades buenas, merma y porcentaje de rendimiento (Yield %) directamente en la App.
- **Configuración Multi-Fábrica**: 
    - Implementada configuración dinámica para separar el Almacén de Planta del Almacén de Insumos/Materias Primas.
    - Soporte para etiquetas (`soplados`) que permiten filtrar productos de forma granular sin afectar las categorías del sistema.

## [1.10.75] - 2026-05-08
### Added
- **API Soplados (Logística)**: 
    - Implementados endpoints de conteo de traspasos para alimentar notificaciones en tiempo real en la App.
    - Sincronización de almacenes en turnos: Añadida columna `warehouse_id` a la tabla `shifts` y automatización en la apertura de turnos vía API.
- **Mejoras en Registro de Producción**: El sistema ahora prioriza el almacén vinculado al turno activo para garantizar la trazabilidad del inventario.
- **Robustez en Traspasos**: El motor de búsqueda de traspasos ahora es insensible a mayúsculas/minúsculas para los estados (`pending`, `partial`, etc.) y cuenta con un fallback de almacén para perfiles de usuario incompletos.

## [1.10.74] - 2026-05-07
### Fixed
- **Flexibilidad en Edición de Ventas**: Restaurada la capacidad de editar todo tipo de facturas (Contado y Crédito), permitiendo cambios en la modalidad de pago y ajustes totales, manteniendo el flujo de pago original mediante modal.
- **Validación de Stock en Edición**: Sigue activa la mejora que impide bloqueos por falta de stock físico al editar ítems que ya pertenecen a la factura actual.
- **Restauración de Metadata de Pagos**: Corregido el error `zelle_sender` al editar ventas con pagos electrónicos, asegurando que toda la información de auditoría se preserve durante el proceso.
- **Corrección de Error en Movimientos de Caja**: Solucionado el error `Data truncated for column 'type'` mediante la expansión de la columna en la base de datos.

## [1.10.73] - 2026-05-07
### Fixed
- **Pagos Parciales en Notas de Débito**: Corregido error que marcaba las Notas de Débito como pagadas al recibir cualquier abono parcial. Se implementó una validación de saldo (`checkSettlement`) que mantiene la nota en estado pendiente hasta que la deuda sea liquidada en su totalidad.

## [1.10.72] - 2026-05-07
### Improved
- **Lógica de Pagos en Notas de Débito**: Mejorada la asociación de pagos para Notas de Débito vinculadas a facturas. Ahora, si una nota de débito tiene una factura asociada, el pago se vincula automáticamente a ambas, permitiendo que el abono contribuya a la liquidación de la deuda de la factura original. Las notas de débito manuales (sin factura) siguen procesándose de forma independiente con `sale_id` nulo.

## [1.10.71] - 2026-05-07
### Fixed
- **Error de Integridad en Pagos**: Corregido el error `sale_id cannot be null` al registrar pagos para Notas de Débito manuales. Se hizo la columna `sale_id` opcional en la base de datos y se protegieron los componentes Livewire contra valores nulos.

## [1.10.70] - 2026-05-06
### Added
- **Automatización de Permisos**: Configurado el `UpdateService` para que los nuevos permisos se creen y asignen automáticamente a los roles administrativos al instalar la actualización. Ya no es necesario ejecutar seeders manualmente en producción.
- **Integración en Seeders Base**: Incorporados los permisos granulares de configuración en `PermissionSeeder` y `RoleSeeder` para garantizar la consistencia del sistema.

## [1.10.69] - 2026-05-06
### Changed
- **Traducciones de Permisos**: Añadidas etiquetas en español para los nuevos permisos granulares (`Editar Configuración Comercial` y `Editar Configuración de Crédito`) en el módulo de Asignación de Permisos.

## [1.10.68] - 2026-05-06
### Added
- **Permisos Granulares**: Implementados nuevos permisos para controlar el acceso a configuraciones críticas.
- **Seguridad en Clientes**: Añadidos permisos `customers.edit_commercial_config` y `customers.edit_credit_config` para restringir quién puede modificar comisiones y reglas de crédito en el formulario de clientes.
- **Seguridad en Vendedores**: Añadidos permisos `users.edit_commercial_config` y `users.edit_credit_config` para proteger la configuración comercial propia de cada vendedor.
- **Seeder de Permisos**: Nuevo seeder `AddGranularConfigPermissionsSeeder` para la creación automática de estos permisos y su asignación al rol administrativo.

## [1.10.67] - 2026-05-05
### Added
- **Acuerdos Comerciales**: Implementado sistema de documentación de acuerdos para Clientes y Vendedores Foráneos.
- **Historial de Acuerdos**: Los acuerdos se guardan como parte del historial de configuración, permitiendo auditar cambios en los términos pactados.
- **Integración POS**: Visualización automática de los acuerdos comerciales vigentes del cliente y vendedor en la barra lateral del Punto de Venta para informar al operador.
- **UI de Historial**: Actualización de los paneles de consulta de historial (Clientes/Vendedores) para incluir el campo de Acuerdo y el número de Lote.

## [1.10.66] - 2026-05-05
### Fixed
- **Persistencia de Productos**: Corregido bug que impedía guardar la configuración de Flete y Reglas de Precio en la edición de productos. Se unificó la lógica de detección de módulos para usar la configuración global en lugar de la sesión, evitando reseteos accidentales de campos avanzados.

## [1.10.65] - 2026-05-05
### Fixed
- **Lógica de Precios (Flete y Comisiones)**: Eliminada la restricción de moneda para el cálculo de Extras Comerciales. El sistema ahora aplica correctamente el flete y las comisiones en todas las monedas configuradas (incluyendo VED/Bolívares), garantizando la rentabilidad en ventas multimoneda.

## [1.10.64] - 2026-05-04
### Fixed
- **UI de Tendencias**: Implementada persistencia del estado de colapso mediante `wire:ignore.self`. El panel de sugerencias ahora mantiene su estado (abierto/cerrado) correctamente frente a las actualizaciones de Livewire.
- **UX**: Mejora en el diseño y transiciones del botón de colapso para una experiencia más fluida.

## [1.10.63] - 2026-05-04
### Added
- **Buying Trends (Sugerencias Inteligentes)**: Implementada la carga automática de sugerencias de productos basadas en el historial del cliente (últimos 90 días) al seleccionarlo en el POS.
- **UI de Tendencias**: Reubicación del panel de sugerencias a la parte superior de la barra lateral (debajo del buscador de clientes) para garantizar visibilidad inmediata sin necesidad de scroll.

### Fixed
- **Persistencia de Entorno**: Corregido bug crítico donde la cancelación de una venta (`cancelSale`) reseteaba el ID del almacén y permisos de usuario, impidiendo que las sugerencias volvieran a cargar tras el reset.
- **Estabilidad de Propiedades**: Asegurada la inicialización de la propiedad `trends` como colección para evitar errores de renderizado en el primer inicio.
- **Aislamiento de Pruebas**: Configuración de `phpunit.xml` para utilizar una base de datos de pruebas dedicada (`jspos_test`), protegiendo la integridad de los datos de desarrollo.

## [1.10.62] - 2026-05-04
### Fixed
- **Lógica de Precios**: Restringida la aplicación de Reglas de Precio (Tiers/Mayoreo) únicamente a transacciones en USD y COP. Para otras monedas, el sistema ahora revierte automáticamente al precio base del producto.
- **Recalculación de Carrito**: Implementada la actualización automática de precios en el carrito al cambiar la moneda de facturación en el POS.

## [1.10.61] - 2026-05-04
### Fixed
- **UI de Productos**: Mejorado el diseño del botón de "Importar" y la separación de los botones de acción en la cabecera del módulo de productos.

## [1.10.60] - 2026-05-04
### Changed
- **Diseño de Interfaz**: Rediseñado el interruptor "Ver Eliminados" con un estilo de cápsula (Pill) y mejores márgenes para evitar amontonamiento.

## [1.10.59] - 2026-05-04
### Security
- **Restauración de Privacidad**: Re-activado el filtro de seguridad que oculta las cuentas de `Super Admin` para los usuarios con rango `Admin` estándar o inferior.

## [1.10.57] - 2026-05-02
### Fixed
- **Visibilidad de Super Admin**: Corregido componente `AsignarPermisos` para que las cuentas de `Super Admin` sean visibles incluso si se entra como un `Admin` estándar.
- **Robustez de Seeders**: Asegurada la creación de cuentas de soporte técnico por defecto.

## [1.10.56] - 2026-05-02
### Fixed
- **Instalación Automática**: Incluido `UserSeeder` en el `MasterDataSeeder` para garantizar que las cuentas de desarrollador se creen siempre por defecto en instalaciones limpias.

## [1.10.55] - 2026-05-02
### Fixed
- **Roles y Permisos**: Corregida la asignación del rol `Super Admin` para las cuentas de desarrollador (`jhosagid7@gmail.com` y `jhosagid77@gmail.com`).
- **Sincronización de Roles**: Asegurado que el rol `Super Admin` tenga todos los permisos sincronizados en el `RoleSeeder`.

## [1.10.54] - 2026-05-02
### Fixed
- **Integridad de Datos en Seeders**: Corregido error en `BankSeeder` al insertar registros sin los campos obligatorios `account_number`, `cedula` y `phone`.

## [1.10.53] - 2026-05-02
### Fixed
- **Error 500 Post-Instalación**: Resolución de `ParseError` crítico causado por desbalanceo de directivas Blade (`@canany`) en el sidebar.
- **Integridad de Vistas**: Limpieza y recompilación automática de la caché de vistas para asegurar la estabilidad inmediata tras la instalación.

## [1.10.52] - 2026-05-02
### Added
- **Instalador Híbrido Profesional**: Nueva lógica de detección automática de método de instalación (Git vs Manual) y verificación de dependencias proactiva.
- **Arquitectura de Datos Maestros**: Implementación de `MasterDataSeeder` para despliegues limpios de producción, eliminando la necesidad de datos transaccionales de prueba.
- **Manual de Instalación**: Inclusión de `INSTALLATION_GUIDE.md` con documentación técnica paso a paso para usuarios y desarrolladores.
### Changed
- **Lógica de Migración**: Automatización de generación de `APP_KEY` y optimización de la secuencia de despliegue en el instalador visual.

## [1.10.51] - 2026-05-01
### Added
- **Reorganización UX/UI del Menú**: Rediseño integral de la navegación principal, agrupando más de 17 opciones raíz en 8 módulos lógicos jerárquicos para reducir la carga cognitiva.
- **Módulos Colapsables**: Implementación de encabezados de módulo interactivos que inician cerrados por defecto, optimizando el espacio vertical y mejorando la limpieza visual del sistema.
### Changed
- **Nomenclatura Profesional**: Estandarización de términos técnicos (ej: "Cargos/Descargos" a "Entradas/Salidas de Stock", "Consultas" a "Auditoría de Pagos").
- **Jerarquía Visual**: Mejora en la distinción de niveles de navegación mediante el uso de iconos diferenciados para sub-menús de nivel 2 y 3.

## [1.10.50] - 2026-05-01
### Added
- **Estandarización de Notas de Débito**: Rediseño total del PDF de Notas de Débito para lograr paridad 1:1 con el formato de Notas de Crédito (Márgenes, proporciones, fuentes y estética).
- **Anulación de Incrementos**: Implementada la capacidad de anular Notas de Débito directamente desde el historial de pagos, con recálculo automático de saldos de factura y trazabilidad de motivos.
### Changed
- **Simplificación de Documentos**: Se eliminaron los avisos legales de límites de reclamo (8 días) en todos los formatos de impresión (Facturas Pagas, Notas de Crédito y Notas de Débito) para un diseño más limpio y profesional.
### Fixed
- **Layout de PDF**: Corregidos problemas de superposición de texto y desbordamiento de páginas en el formato de Notas de Débito.

## [1.10.49] - 2026-05-01
### Changed
- **Buscador Inteligente**: Se unificó la búsqueda en el Gestor de Notas de Crédito. Ahora permite buscar por Nombre de Cliente, Número de Nota o Factura desde un solo campo general.
- **Rango de Fecha**: Se aumentó el rango de carga inicial a 90 días para facilitar la visibilidad de movimientos recientes.
- **Auditoría Detallada**: Ahora se muestra quién solicitó la devolución y quién la aprobó/rechazó directamente en el listado.
### Fixed
- **Enlaces de Documentos**: Se corrigieron los enlaces para visualizar el PDF de la Nota de Crédito y la Factura de referencia desde el gestor.
- **Livewire 3**: Se resolvió el error de renderizado de elementos raíz múltiples que bloqueaba el módulo.

## [1.10.48] - 2026-05-01
### Added
- **Nuevo Módulo: Gestor de Notas de Crédito**: Implementado un listado centralizado en la sección de Reportes para supervisar todas las devoluciones del sistema.
- **Funcionalidades de Gestión**:
    - Filtros por fecha, cliente y estado (Pendiente, Aprobado, Rechazado).
    - Capacidad de aprobación/rechazo masivo desde una sola vista.
    - Acceso rápido a la factura original vinculada a la nota de crédito.
    - Resumen de totales retornados según los filtros aplicados.

## [1.10.47] - 2026-05-01
### Added
- **Gestión de Devoluciones**: Se habilitó la capacidad de **Aprobar** o **Rechazar** Notas de Crédito pendientes directamente desde el Historial de Pagos de cada factura.
- **Flujo de Trabajo**: Al aprobar una Nota de Crédito, el sistema ahora actualiza automáticamente el saldo de la deuda, restaura el stock al almacén correspondiente y libera los ítems (bobinas) asociados.

## [1.10.46] - 2026-05-01
### Fixed
- **Reporte Cuentas por Cobrar**: Se sincronizó la lógica de cálculo de saldo para que solo descuente Notas de Crédito aprobadas, evitando saldos falsos de $0.00 cuando hay devoluciones pendientes.
- **Historial de Pagos**: Se incluyeron los "Pagos Iniciales" y las "Notas de Crédito Pendientes" en el desglose de movimientos para total transparencia hacia el cliente.
- **UI**: Se agregó un indicador visual (badge) en el reporte de cuentas por cobrar para alertar sobre Notas de Crédito que esperan aprobación.

## [1.10.45] - 2026-05-01
### Changed
- **Búsqueda Ultra-Inteligente**: Refinada la búsqueda para que al escribir solo números (ej. "2560") el sistema ignore absolutamente cualquier letra (incluyendo la "X") y encuentre las dimensiones exactas. Ahora "2560" encontrará siempre productos de "25X60".

## [1.10.44] - 2026-04-29
### Changed
- **Búsqueda Inteligente (Fuzzy)**: El sistema ahora reconoce dimensiones de productos incluso si se omiten los separadores (ej. buscar "2560" ahora encontrará productos con "25X60" en el nombre o SKU).
- **Unificación de Búsqueda**: Centralizada la lógica de búsqueda de productos para garantizar el mismo comportamiento inteligente tanto en el Gestor de Productos como en el Módulo de Ventas.

## [1.10.43] - 2026-04-29
### Added
- **Indicadores de Estado Online**: Implementada visualización en tiempo real (puntos verdes latientes) para dispositivos activos en el Gestor de Dispositivos.
- **Monitoreo Global**: Añadido contador de dispositivos en línea en la barra de navegación para una supervisión rápida.
### Changed
- **Precisión de Actividad**: Reducido el intervalo de actualización de dispositivos a 2 minutos para garantizar que el estado "En Línea" sea veraz.
- **Ventana de Conectividad**: Ajustado el margen de inactividad a 10 minutos para mejorar la estabilidad visual del indicador.
### Fixed
- **Tooltips Informativos**: Añadida información detallada (User Agent y tiempo exacto) al pasar el mouse sobre los dispositivos.
- **Estabilidad de Sesión**: Implementado silenciador para el error "Session Expired" (419) que ocurría aleatoriamente al escribir rápido en los buscadores.
- **Optimización de Búsqueda**: Incrementado el tiempo de espera (debounce) en los buscadores a 500ms para reducir la carga del servidor y mejorar la estabilidad en redes locales.

## [1.10.41] - 2026-04-29
### Fixed
- **Limpieza Automática de Dispositivos**: Integrada la purga de registros duplicados en el proceso de migración automática para limpiar el panel de gestión sin intervención manual.

## [1.10.40] - 2026-04-29
### Fixed
- **Duplicidad de Dispositivos**: Implementada deduplicación por huella digital (IP + User Agent) para reconocer dispositivos móviles sin token persistente.
- **Optimización de Base de Datos**: El sistema ya no crea registros de dispositivos innecesarios para peticiones API en modo "Abierto" si no se proporciona un token.
- **Identidad Móvil**: Se añadió el `device_uuid` a la respuesta del login (Vendedores y VIP) para facilitar la persistencia en las aplicaciones móviles.

## [1.10.39] - 2026-04-28
### Fixed
- **Duplicidad Definitiva en Apps**: Auditoría completa e inyección del header `X-Device-Token` en el 100% de las llamadas al API en las aplicaciones de Vendedores y VIP.
- **Persistencia de Identidad**: Mejora en la reutilización del token desde SharedPreferences para evitar registros redundantes en el panel administrativo.

## [1.10.38] - 2026-04-27
### Fixed
- **Creación de Usuarios**: Corregido error SQL (Column cannot be null) al crear usuarios nuevos debido a la falta de valores por defecto en campos de bloqueo por horario y metas mensuales.

## [1.10.37] - 2026-04-27
### Fixed
- **Duplicidad en Apps**: Corregido error que generaba múltiples registros de dispositivo para las Apps móviles al no respetar el ID enviado por el cliente.

## [1.10.36] - 2026-04-27
### Added
- **Soporte para Apps Móviles**: Integración de APKs de Vendedores y VIP en el módulo de gestión de dispositivos.
- **Auditoría de Identidad Móvil**: Registro automático de celulares con bloqueo remoto opcional mediante el Modo Restringido.
- **API Security Layer**: Middleware adaptado para responder con JSON (403) en terminales móviles no autorizados.

## [1.10.35] - 2026-04-27
### Fixed
- **Limpieza Automática de Dispositivos**: Implementada la purga automática de la tabla de autorizaciones durante el proceso de actualización. Esto obliga a todos los PCs a re-identificarse bajo la nueva lógica de UUID estable.
- **Safety Bypass para Administradores**: Se añadió lógica al middleware para que los usuarios con rol Admin o Super Admin sean auto-aprobados al instante. Esto evita el riesgo de que el administrador se quede bloqueado fuera del sistema si tiene activo el "Modo Restringido" tras la limpieza.
- **Estabilización de Inventario**: Refuerzo preventivo en las migraciones de SoftDeletes para asegurar la integridad de datos en el despliegue masivo.

## [1.10.34] - 2026-04-27
### Fixed
- **Restauración de Visibilidad de IPs**: Rehabilitada la configuración de TrustProxies para asegurar que las IPs reales de los equipos sean visibles en el panel de gestión de dispositivos, manteniendo la estabilidad de identidad de la versión anterior.

## [1.10.33] - 2026-04-27
### Fixed
- **Reversión a Lógica Estable**: Restaurado el sistema de autorización de dispositivos a su estado original más estable. Se ha vuelto a habilitar el cifrado nativo de cookies de Laravel y se ha eliminado la lógica de huella digital (IP+UA) que causaba colisiones de identidad en redes locales. Se mantiene el respaldo en sesión para mayor durabilidad.

## [1.10.32] - 2026-04-25
### Fixed
- **Identificación por Huella Digital**: Añadido sistema de reconocimiento de dispositivos basado en IP y User-Agent (Fingerprint) como capa de seguridad final. Esto evita la creación duplicada de dispositivos cuando se pierden las cookies y asegura que las restricciones de acceso (bloqueos) sean efectivas y persistentes.
- **Detección de IP**: Ajustada la configuración de TrustProxies para mejorar la visibilidad de IPs reales en redes locales.

## [1.10.31] - 2026-04-25
### Fixed
- **Unificación de Identidad**: Vinculada la lógica de detección de dispositivos en el componente visual y el trait de impresión con el sistema de respaldo en sesión. Esto asegura que la etiqueta "ESTE DISPOSITIVO" sea visible de forma consistente tanto en desarrollo como en producción.

## [1.10.30] - 2026-04-25
### Fixed
- **Doble Persistencia de Identidad**: Implementado sistema de respaldo en Sesión (Server-side) para la identificación de dispositivos. Esto resuelve problemas en redes locales donde los navegadores rechazan cookies persistentes. Ahora, si la cookie falla, el sistema recupera el ID de la sesión activa y restaura la configuración automáticamente.

## [1.10.29] - 2026-04-25
### Fixed
- **Estabilidad de Sesión de Dispositivo**: Corregido problema donde los servidores de producción no mantenían la identidad del dispositivo (cookie). Se desactivó el cifrado para la cookie `device_token` y se ajustaron los parámetros de seguridad para permitir su uso en servidores locales (HTTP) sin HTTPS.

## [1.10.28] - 2026-04-25
### Fixed
- **Migración Robusta**: Corregido error en la actualización de base de datos donde el sistema intentaba crear la columna `deleted_at` si esta ya existía. Ahora la migración es segura y omite el paso si la columna se detecta previamente.

## [1.10.27] - 2026-04-25
### Added
- **Identificación de Dispositivos**: Añadida etiqueta visual "ESTE DISPOSITIVO" en la tabla de gestión para ayudar a los usuarios a configurar el navegador actual correctamente.
- **Persistencia de Impresoras**: Mejorada la lógica de sincronización al guardar configuraciones de impresoras de red para evitar que los valores vuelvan a estado predeterminado.

## [1.10.26] - 2026-04-25
### Added
- **SoftDeletes para Productos**: Implementado el sistema de borrado suave nativo de Laravel. Esto permite ocultar productos del POS y buscadores sin romper la integridad de los registros de ventas históricos.
- **Comando Artisan**: Creado `php artisan products:cleanup` para realizar purgas masivas de inventario (basadas en stock o precio) de forma segura mediante SoftDeletes.

### Fixed
- **Restauración de Stock (Bobinas)**: Corregida la lógica al eliminar ventas o procesar devoluciones para productos con peso separado (bobinas/reels). Ahora, el sistema libera automáticamente la bobina específica (ProductItem) y la devuelve al estado "Disponible".
- **Relaciones con Productos**: Actualizados todos los modelos de detalle (Ventas, Compras, Órdenes, Producción, etc.) para incluir `->withTrashed()`, garantizando que las facturas antiguas sigan mostrando el nombre del producto aunque este haya sido "eliminado".

## [1.10.25] - 2026-04-25
### Fixed
- **Estabilidad de UI Global (Header)**: Corregido un crash crítico ("Attempt to read property 'name' on null") en el menú superior que bloqueaba el acceso de los usuarios. El error ocurría cuando el motor de notificaciones intentaba renderizar alertas de compras, créditos o comisiones asociadas a proveedores o clientes eliminados.
- **Sincronización de Licencias**: Eliminado un bypass de desarrollo ("DEV BYPASS") atascado para UUIDs específicos que causaba que la interfaz visual siempre mostrara "30 días restantes" a pesar de activar códigos temporales superiores.
- **Caché del Sistema**: Implementada una limpieza inmediata de caché (`Cache::forget`) al activar o renovar permisos, forzando la visualización en tiempo real de los módulos habilitados sin tener que esperar a su expiración natural.

### App Móvil (VIP 1.1.5)
- **Autorización de Cuentas (MyPurchases)**: Subsanado un error clave donde la sección de "Compras" no enviaba el Access Token (`token` vs `api_token`) causando denegaciones 401 por parte de la API.
- **Precisión Monetaria**: El total de compra ahora se mapea correctamente a la variable del servidor (`total`) en lugar de arrojar pantallas en $0.00 debido a inconsistencias del modelo.

## [1.10.24] - 2026-04-22
### Added
- **Mobile VIP App**: Finalizada la primera versión compilable de la App móvil exclusiva para clientes VIP con interfaz dorada (ícono de carrito VIP), drawer responsivo inferior y lectura de versión dinámica.
- **REST Client**: Integración de todos los endpoints de autenticación y compras de la API VIP en `rest-client.http` para pruebas directas en el editor.
- **Backend VIP**: Validación estructural reforzada usando `$request->user()->id` para garantizar seguridad y correcta adjudicación automática de comisiones y logística para ventas a clientes VIP.

## [1.10.23] - 2026-04-21
- **Base de Datos**: Añadida prevención de duplicidad en `add_performance_indexes_to_dashboard_tables.php` mediante la comprobación de existencia antes de crear los índices de rendimiento.

### Fixed
- **API de Pagos**: Estandarización de cálculos temporales en `PaymentController@pendingSales` con `startOfDay()` para calcular vencimientos precisos (Azul/Naranja/Rojo).

## [1.10.22] - 2026-04-18
### Fixed
- **Inventario Variable**: La opción de ventas por peso/separado (bobinas) se ha hecho globalmente visible en el formulario de creación/edición de productos independientemente de la configuración de módulos avanzados.

### Added
- **Auditoría e Inventario**: Se añadió un comando de consola (`stock:zero`) que permite poner en cero todo el inventario de la empresa. Éste procesa inteligentemente las bobinas, borrando únicamente las "disponibles" para permitir un conteo limpio desde cero.

## [1.10.21] - 2026-04-15
### Fixed
- **Sincronización Maestra**: Unificada la lógica de consulta con el panel web (Commissions.php). Ahora el App es un espejo exacto del Módulo de Ventas de la administración.
- **Auditoría**: Filtros de fecha reajustados para basarse en la creación de facturas, eliminando discrepancias en los montos acumulados.

## [1.10.19] - 2026-04-15
### Fixed
- **Auditoría Financiera**: Sincronización 1:1 entre los totales del Dashboard y el listado de detalles de comisiones.
- **UX**: Añadidos subtotales (Pendiente vs Pagado) en la cabecera de la pantalla de detalles para validación rápida.
- **UI Móvil**: Diferenciación clara entre fecha de venta (pendientes) y fecha de cobro (historial pagado).

## [1.10.18] - 2026-04-15
### Added
- **Inteligencia de Cobranza**: Implementación de Tiers dinámicos en la Cartera. Los vendedores ahora ven su comisión proyectada en USD y alertas de tiempo para no perder el porcentaje de cobranza (incentivo financiero).
- **API**: Nuevo motor de cálculo de comisiones en tiempo real basado en la configuración individual de cada factura.

## [1.10.17] - 2026-04-15
### Fixed
- **Lógica de Comisiones**: Restablecido el filtro por mes para las comisiones pendientes. Ahora el saldo vuelve a reflejar únicamente el desempeño del mes actual (vuelve a los valores reales de ~37).
- **Consistencia**: Unificada la lógica de Pendientes entre el Dashboard y la vista de Detalle.

## [1.10.16] - 2026-04-15
### Fixed
- **UI Móvil**: Corrección de texto cortado (overflow) en motivos de Notas de Crédito y abonos.
- **Lógica de Comisiones**: Ahora se muestran TODAS las comisiones pendientes acumuladas, sin restricción de mes.
- **UX**: Mejora visual en pestañas vacías con iconos y mensajes descriptivos.

## [1.10.15] - 2026-04-15
### Added
- **Base de Datos**: Blindaje de migración de índices para evitar errores de duplicidad en producción.
- **Mobile App v1.1.24**: Implementación del Dashboard Profesional (Re-lanzamiento corregido).
    - **Pantalla de Detalle de Comisiones**: Visualización detallada de facturas ganadas y pagadas.
    - **Pantalla de Cartera y Envejecimiento**: Listado de deudas con colores (Azul/Naranja/Rojo) y días de atraso.
    - **Pantalla de Auditoría de Pagos**: Historial global con estados y motivos de rechazo.
    - **Menú de Acceso**: Botón de "AUDITORÍA" e interactividad completa en el Dashboard de Rendimiento.

## [1.10.14] - 2026-04-15

## [1.10.13] - 2026-04-15
### Added
- **API: Desglose de Comisiones**: Nuevo endpoint para visualizar el detalle de facturas ganadas y cobradas.
- **API: Reporte de Cartera Detallado**: Nuevo endpoint con lógica de Aging (Semáforo de vencimiento: Azul/Naranja/Rojo).
- **API: Auditoría de Cobranza**: Nuevo historial global de pagos subidos por el vendedor con visualización de motivos de rechazo.
- **DB: Optimización de Rendimiento**: Índices agregados a tablas de ventas y pagos para acelerar la carga del Dashboard en móviles.

## [1.10.12] - 2026-04-15
### Fixed
- **Dashboard: Sincronización Total de Indicadores**: Se refactorizó toda la lógica del Dashboard móvil para ser un espejo exacto del sistema web.
    - **Cobranza**: Ahora solo suma pagos con estado `approved` o `settled`, excluyendo pagos pendientes de revisión.
    - **Ventas del Mes**: Ahora incluye el filtro de `is_foreign_sale` y excluye facturas anuladas o devueltas, igual que el reporte de ventas web.
    - **Cartera (Deuda)**: Se implementó el cálculo matemático estricto del reporte de Cuentas por Cobrar (Venta - Pagos Aprobados - Anticipos - Devoluciones).
    - **Sostenibilidad**: Se verificó la compatibilidad con el middleware de auto-migración para cambios en la base de datos.

## [1.10.11] - 2026-04-15
### Fixed
- **API: Sincronización de Comisiones**: Se alineó la lógica del Dashboard con el módulo web "Gestión de Comisiones". 
    - Se agregaron filtros de `is_foreign_sale`.
    - Se excluyeron facturas devueltas, anuladas o canceladas.
    - Se restringió la visualización a ventas del mes actual para coincidir con la vista web por defecto.
    - Esto resuelve la discrepancia de montos que reportaba el vendedor.

## [1.10.10] - 2026-04-15
### Fixed
- **API: Lógica de Comisiones**: Corregido el cálculo de "Comisiones por Cobrar". Ahora solo suma comisiones de facturas que el cliente ya pagó totalmente, pero que la empresa aún no ha liquidado al vendedor. Esto evita montos inflados por ventas a crédito.

## [1.10.9] - 2026-04-15
### Added
- **Mobile: Dashboard Profesional de Comisiones**: Refactorización completa de métricas para mayor transparencia.
    - **Comis. por Cobrar**: Saldo que la empresa debe al vendedor (ganado pero pendiente de pago).
    - **Comis. Pagadas**: Historial de comisiones ya recibidas por el vendedor este mes.
- **API: Inteligencia de Cobranza**: Ahora el dashboard muestra la **Cobranza Real del Mes** (dinero ingresado de cualquier factura) y la **Cartera Total** (deuda pendiente de todos sus clientes).
- **UI: Terminología**: Cambio de "Pedidos" a "Ventas" y aclaraciones en indicadores.

## [1.10.8] - 2026-04-15
### Fixed
- **Web: Hotfix de Reportes**: Corregido error "Undefined property: issuer_name" al visualizar detalles de venta o historial de pagos de registros antiguos o creados en oficina. Se implementó validación robusta para campos opcionales del móvil.

## [1.10.7] - 2026-04-15
### Added
- **API: Atribución por Cliente**: Las métricas de rendimiento ahora se basan en el vendedor asignado al Cliente (Customer), permitiendo que las ventas facturadas en oficina se atribuyan correctamente al vendedor foráneo.
- **Mobile: Desglose de Ventas**: Nueva visualización en el Dashboard que separa "Ventas Cobradas" de "Ventas por Cobrar".
- **Dashboard: Lógica de Comisiones**: Refinada para mostrar comisiones solo sobre facturas liquidadas (cobradas), mientras que el progreso de meta cuenta el total facturado.

## [1.10.6] - 2026-04-15
### Added
- **Mobile: Dashboard de Rendimiento**: Nueva pantalla "Mi Rendimiento" con métricas de ventas mensuales, progreso de meta y comisiones acumuladas.
- **Web: Metas Mensuales**: Campo añadido en la ficha de usuario para configurar objetivos de ventas (USD) por vendedor.
- **Backend: API Dashboard**: Endpoint de alto rendimiento para el cálculo de KPIs financieros del vendedor.

## [1.10.5] - 2026-04-15
### Added
- **Mobile: Carga desde Archivo**: Añadida la posibilidad de seleccionar comprobantes desde la galería del dispositivo (útil para vaucher digitales).

## [1.10.4] - 2026-04-15
### Fixed
- **Web: Hotfix**: Corregido error de sintaxis ("unexpected token if") en el modal de historial que bloqueaba el módulo de ventas.

## [1.10.3] - 2026-04-15
### Fixed
- **Web: Modal Historial**: Sincronizado el modal de historial de pagos (`historypays.blade.php`) para mostrar detalles de pagos móviles (Bancos, Emisores, Comprobantes).
- **Web: Detalle de Venta**: Sincronizada la vista de detalles y el mapeo de campos directos (`bank_image`, `issuer_name`) para pagos móviles.

## [1.10.1] - 2026-04-15
### Fixed
- **Mobile: Banco/Plataforma**: Corregido bug visual donde aparecía "(null)" en el selector de bancos.
- **Mobile: Sincronización**: Corregido el enlace automático de moneda al seleccionar un banco.

## [1.10.0] - 2026-04-15
### Added
- **Mobile: Auditoría de Pagos Móviles (Recibos)**: Implementación de visor de imágenes de alta gama (`CachedNetworkImage`) para comprobantes de pago y fotos de evidencia.
- **Mobile: Transparencia Financiera**: El historial de pagos ahora detalla explícitamente descuentos (Pronto Pago, Divisa), motivos de devolución y conciliación neta de deuda.
- **Mobile: Validación de Referencias Foráneas**: Los vendedores pueden usar su número de cédula como referencia si el vaucher no tiene una visible, omitiendo la validación de duplicados (paridad con Web).
- **Mobile: Sincronización de Carga de Pagos**:
    - Selector dinámico de Bancos/Plataformas con auto-configuración de moneda y tasa.
    - Campos obligatorios inteligentes según el método (Zelle, VED, COP).
    - Evidencia fotográfica obligatoria para pagos en efectivo y bancos específicos.
    - Labels dinámicos adaptados a la moneda (ej. "Últimos 5 dígitos" para VED).
- **Mobile: Fixed**: Corregido bug donde el banco mostraba "(null)" en lugar de la moneda activa.
- **API Auditoría**: Actualizada la lógica de carga para soportar el bypass de referencias foráneas basándose en el ID del vendedor.

## [1.9.102] - 2026-04-14
### Fixed
- **Sistema de Actualización**: Aumentado el timeout de descarga de 5 a 10 minutos y agregado reintento automático (2 intentos) para conexiones lentas o inestables con GitHub CDN.

## [1.9.101] - 2026-04-14
### Maintenance
- **Limpieza de Repositorio**: Eliminados los binarios APK del historial de Git. El ZIP de actualización ahora es significativamente más liviano, evitando timeouts en servidores con conexión lenta.

## [1.9.100] - 2026-04-14
### Added
- **Auditoría de Historial de Órdenes (`orders.view_history`)**: Nuevo permiso para controlar el acceso al registro de auditoría completo de pedidos/órdenes. Se asigna automáticamente a los roles Admin y Super Admin.
- **Auditoría de Historial de Ventas (`sales.view_history`)**: Completada la integración del permiso en la tabla de asignación con etiquetas descriptivas en español.
- **Migraciones de Permisos**: Ejecutadas automáticamente durante el proceso de actualización del sistema para garantizar que todos los clientes reciban los nuevos permisos sin intervención manual.

### Fixed
- **Traducciones de Permisos**: Añadidas las etiquetas en español (`lang/es/permissions.php`) para `sales.view_history` y `orders.view_history`, que faltaban en la vista de Asignación de Permisos.

## [1.9.99] - 2026-04-14
### Fixed
- **API Clientes**: Unificada la l\u00f3gica de c\u00e1lculo de deuda (USD) con la vista detallada para evitar discrepancias y facturas "ocultas" en el listado general.

## [1.9.98] - 2026-04-14
### Fixed
- **API Cr\u00e9ditos**: Parche de robustez para el c\u00e1lculo de vencimientos (fallback a d\u00edas de cr\u00e9dito del cliente y carga de relaci\u00f3n).

## [1.9.97] - 2026-04-14
### Added
- **API Cr\u00e9ditos**: Inclusi\u00f3n de metadatos de trazabilidad en ventas pendientes (fecha de emisi\u00f3n, vencimiento y c\u00e1lculo de mora).

## [1.9.96] - 2026-04-14
### Fixed
- **API Seguridad**: Unificación de criterios de visibilidad para roles de Admin y Super Admin en el módulo de clientes.
- **API Performance**: Implementación de filtrado SQL para deudores y morosos, eliminando el límite de 100 resultados previos al filtrado.

## [1.9.95] - 2026-04-14
### Added
- **API Auditoría**: Nuevos campos `issuer_name`, `zelle_image` y `bank_image` en la tabla de pagos para soporte de auditoría extendida.
- **Backend/DB**: Ejecución de migración `add_extended_fields_to_payments_table` para garantizar integridad en registros de divisas.
- **API Optimization**: Endpoint de historial de pagos enriquecido con tasas de cambio y metadatos del emisor.

All notable changes to this project will be documented in this file.

## [1.9.94] - 2026-04-13
### Added
- **Mobile API**: New Payment Upload module.
- `GET /api/payments/form-data`: Returns available banks and currencies.
- `GET /api/sales/pending`: List outstanding credit sales for a customer with exact net debt calculation.
- `POST /api/payments/upload`: Secure endpoint for pre-payment submissions (Zelle, Bank, Cash).
- Image support for Zelle and Bank receipts via API.

## [1.9.93] - 2026-04-13
### Fixed
- **Depuración de Reservas**: Corregida la lógica de inventario reservado para excluir pedidos en estado `processed` y `deleted`. Esto libera miles de unidades de stock "fantasma" que estaban bloqueadas en la App móvil.

## [1.9.92] - 2026-04-13
### Changed
- **Limpieza de Diagnóstico**: Eliminadas las herramientas de auditoría temporal (archivos y registros de logs expandidos) tras confirmar la estabilidad de la conexión móvil.
- **Estabilidad General**: Sistema operativo al 100% en entornos de Desarrollo y Producción.

## [1.9.91] - 2026-04-13
### Added
- **Auditoría de IP en Login**: Se incluyó el registro de la dirección IP de origen en los logs de autenticación (`Log::info`) para diferenciar y diagnosticar fallos de conexión entre WiFi local y redes externas.

## [1.9.90] - 2026-04-13
### Fixed
- **Autorreparación de Base de Datos (Sanctum)**: Implementada la creación automática de la tabla `personal_access_tokens` en el proceso de actualización. Esto resuelve el error 500 que impedía el acceso móvil en servidores con esquemas incompletos.
- **Sincronización de Esquema de Correo**: Adición automática de la columna `sent_at` faltante en la tabla de mensajes.

## [1.9.89] - 2026-04-13
### Added
- **Telemetría de Autenticación**: Se añadió registro de logs específicos (`Log::info`) para auditar intentos de login desde la App y diagnosticar fallos de credenciales en producción.

## [1.9.88] - 2026-04-13
### Fixed
- **Restauración de API (Sintaxis)**: Se corrigió un error de sintaxis en el controlador de autenticación que causaba bloqueos del servidor y errores de "TimeoutException" al conectar desde la App.

## [1.9.87] - 2026-04-13
### Changed
- **Unificación de Motor de Login**: El acceso de la App ahora utiliza el mismo motor que el panel web (`Auth::attempt`), garantizando que si el usuario funciona por web, también funcione por App.

## [1.9.86] - 2026-04-13
### Changed
- **Unificación de Motor de Login**: Se cambió el procedimiento de autenticación de la API para usar el mismo motor que el panel web (`Auth::attempt`). Esto garantiza que si el usuario puede entrar por la web, también pueda entrar por la App.

## [1.9.86] - 2026-04-13
### Added
- **Parche de Robustez en Login**: Implementación de limpieza automática (trim) y conversión a minúsculas para correos en la API, evitando fallos de "Credenciales incorrectas" por errores de teclado móvil.

### Fixed
- **Sincronización de Stock en Producción**: Integración del comando de reparación de almacenes en el sistema de actualizaciones, asegurando que el inventario se normalice inmediatamente tras la actualización del cliente.

## [1.9.85] - 2026-04-13
### Added
- **Sincronización de Permisos Automática**: Se integró la ejecución del `PermissionSeeder` dentro del proceso de actualización del sistema para garantizar que el cliente siempre tenga acceso a las nuevas funciones sin comandos manuales.

### Fixed
- **Integridad de Inventario en Tiempo Real**: Se corrigió el error que permitía la doble venta de productos con stock limitado. Ahora el sistema reserva inventario de CUALQUIER pedido activo (Borrador, En Oficina o Pendiente).
- **Persistencia de Almacén**: Reparado el bug en el modelo `OrderDetail` que ignoraba el ID del almacén al guardar pedidos móviles, lo que causaba discrepancias en el stock disponible.
- **Detección de Stock por Defecto**: Los vendedores sin almacén asignado ahora consultan automáticamente el stock de la "Tienda Principal/Oficina" (Almacén 1), evitando que los productos aparezcan erróneamente como agotados.


## [1.9.84] - 2026-04-11
### Fixed
- **Previsualización de Reportes**: Corregido bug que impedía la apertura del modal de previsualización en el reporte de Relación de Cobros por Cliente.
- **UX/Feedback Visual**: Añadidos estados de carga (`wire:loading`) y desactivación de botones durante la generación de PDFs para evitar solicitudes duplicadas.
- **Estabilidad de PDFs**: Refactorizada la generación de PDFs agrupados para eliminar errores de variables indefinidas y mejorar el rendimiento de renderizado en iframes.

## [1.9.83] - 2026-04-10
### Added
- **Relación de Cobros por Cliente**: Módulo dedicado para auditoría de pagos con filtros avanzados por rango de clientes (ID), rango de facturas y fechas.
- **Reporte PDF de Alta Gama**: Nueva plantilla de impresión agrupada por cliente, con subtotales automáticos, resumen de divisas y liquidación de canales de ingreso.
- **Permisos y Navegación**: Integrado en la barra lateral bajo la sección de REPORTES y protegido con el nuevo permiso `reports.customer_payment_relationship`.

## [1.9.82] - 2026-04-10
### Changed
- **Renombres de Módulo de Cobros**: Se actualizó la terminología de "Relación de Pagos" a "Relación de Cobros" en todo el sistema, incluyendo menús y encabezados de reportes.
- **Desglose de Efectivo Multimoneda**: Se implementó una lógica de agrupación de pagos en los reportes (Relación de Cobros y Ventas Diarias) que consolida múltiples pagos de efectivo en diferentes monedas en una sola línea descriptiva detallando Tasa, Monto y Equivalente USD.
- **Estandarización de Precisión**: Se ajustaron los reportes financieros para trabajar con 4 decimales en tasas y montos dolarizados, garantizando exactitud matemática.

## [1.9.81] - 2026-04-10
### Added
- **Auditoría de Edición de Ventas**: Se implementó un sistema de historial de cambios ("Caja Negra") que captura el estado anterior y posterior de cada factura editada, incluyendo productos y totales.
- **Icono Dinámico de Historial**: Se añadió un ícono de reloj azul en el reporte de ventas que solo aparece si la factura ha sido modificada.
- **Permisos de Auditoría**: Nuevo permiso `sales.view_history` para controlar quién puede ver los registros de cambios.
- **Configuración de Bloqueo HH:MM:SS**: El tiempo límite para editar ventas ahora permite precisión de segundos, configurable desde los ajustes del sistema.

### Fixed
- **Captura de Datos en Auditoría**: Se corrigió el formato de almacenamiento de los logs para asegurar que los nombres de productos y precios se visualicen correctamente en el modal de historial.

## [1.9.80] - 2026-04-09
### Added
- **Asignación de Chofer Retroactiva**: Se añadió una nueva funcionalidad en el Listado de Ventas que permite asignar o cambiar el chofer de una factura ya existente mediante un botón dedicado.
- **Selección Manual en Reporte de Despacho**: Se implementó un sistema de casillas de verificación en el Reporte de Despacho para permitir la generación de reportes y hojas de liquidación personalizados con facturas específicas.

### Fixed
- **Seguridad en Logística**: Se corrigió el bug donde facturas anuladas, devueltas o eliminadas aparecían en el Reporte de Despacho. Ahora el sistema las excluye automáticamente para garantizar la precisión de la carga.

## [1.9.79] - 2026-04-09
### Fixed
- **Despliegue de Permisos Automático**: Se añadió una migración dedicada para asegurar que los nuevos permisos del Estado de Cuenta se registren automáticamente en la base datos durante el proceso de actualización del sistema.

## [1.9.78] - 2026-04-09
### Added
- **Permisos Granulares para Estado de Cuenta**: Se implementó un sistema de seguridad de tres niveles (`index`, `view_all`, `view_own`) para el módulo de Estado de Cuenta Global.
- **Privacidad para Vendedores**: Los usuarios con el permiso `view_own` (asignado por defecto a Vendedores y Vendedores Foráneos) ahora solo pueden buscar y visualizar los estados de cuenta de sus propios clientes asignados.
- **Validación de Seguridad en PDF**: Se añadió una capa de protección en el controlador de reportes que impide el acceso a documentos PDF de clientes no autorizados, incluso mediante manipulación manual de la URL.

### Improved
- **Módulo de Asignación**: Se estandarizaron las etiquetas de los permisos dentro de la gestión de roles para facilitar la administración por parte del usuario.

## [1.9.77] - 2026-04-09
### Added
- **Previsualización Premium de Estado de Cuenta**: Se integró un nuevo botón "Previsualizar" con icono de ojo que abre un visualizador PDF (iframe), permitiendo revisar el estado de cuenta global antes de imprimirlo.
- **Diseño de Reporte de Alta Gama**: Implementación de la plantilla `customer-statement-detailed-pdf` basada en el estándar de Relación de Cobros, incluyendo cabecera profesional, trazabilidad de saldo y resumen multimoneda.
- **Trazabilidad de Facturas en Historial**: Ahora cada pago y devolución en el estado de cuenta indica explícitamente a qué número de factura (`[FACT. #xxx]`) está asociado, facilitando la auditoría de clientes con múltiples deudas.

### Fixed
- **Sincronización de Buscador TomSelect**: Se corrigió el error donde el texto del buscador de clientes permanecía visible después de limpiar la selección. El componente ahora emite un evento de limpieza que resetea el input visual instantáneamente.
- **Resumen Completo en PDF**: Se añadió el desglose de "Total Devoluciones" en la tabla de sumatoria del reporte PDF, garantizando paridad total con los totales mostrados en pantalla.
- **Búsqueda por Factura en Ledger**: Se optimizó el filtro del estado de cuenta para permitir localizar movimientos de pago o de crédito buscando directamente por el número de la factura afectada.

## [1.9.76] - 2026-04-08
### Added
- **Dashboard Personalizado para Vendedores Foráneos**: Los vendedores con rol foráneo ahora visualizan sus propias estadísticas financieras y de desempeño de forma automática.
- **KPIs de Comisiones Pagadas**: Implementación de un nuevo widget en el Dashboard que totaliza las comisiones liquidadas del mes actual.

### Fixed
- **Precisión en Gráficas de Rentabilidad**: Ajuste de los filtros de SQL para incluir a vendedores foráneos en las gráficas de "Top Profit" y "Top Products".
- **Visibilidad de Configuración Bancaria**: Corrección de la lógica de interfaz que ocultaba las pestañas de Bancos, Crédito y Comisiones a los vendedores con permisos foráneos.

### Changed
- **Acceso a Reportes Propios**: Se flexibilizaron los permisos de visualización para permitir que los vendedores consulten sus propios reportes financieros sin comprometer la seguridad global.

## [1.9.75] - 2026-04-08
### Added
- **Diseño Premium de Monto Base**: Implementación de un banner de alto contraste con estética "Dark Mode" para el totalizador neto de órdenes filtradas.
- **Tipografía Adaptativa**: El monto base ahora utiliza tamaños de fuente calculados dinámicamente (`calc`) para una visualización imponente en desktop y fluida en dispositivos móviles.

### Fixed
- **Desbloqueo de Dashboard Administrativo**: Corrección crítica en el motor de redirecciones que bloqueaba el acceso al Dashboard estadístico a usuarios con múltiples roles (Admin + Chofer).
- **Deduplicación de Vendedores**: Refinamiento del scope de búsqueda para eliminar nombres de usuario duplicados en el Punto de Venta (POS).
- **Consistencia de Permisos**: Sustitución total de roles "hardcodeados" por permisos técnicos específicos (`system.is_seller`), garantizando la estabilidad del sistema ante cambios de estructura.

### Changed
- **Responsividad Total (Mobile-First)**: El totalizador de órdenes ahora se apila inteligentemente en pantallas pequeñas para evitar desbordamientos laterales.
- **Búsqueda Inteligente de Órdenes**: Se independizó la búsqueda de pedidos filtrados de la lógica de búsqueda de productos del carrito, mejorando el flujo de trabajo del cajero.

## [1.9.74] - 2026-04-07
### Fixed
- **Resolución Final Arqueo (Closure Scope)**: Corrección del error `Undefined variable $dFrom` en el closure de cálculo de ventas netas de `CashCount.php`. Se garantizó que las fechas de filtrado estén disponibles en todos los bloques de cálculo internos hoy mismo.

## [1.9.73] - 2026-04-07
### Fixed
- **Error Crítico en Arqueo (Corte de Caja)**: Corrección del bug `Undefined variable $dFrom` en el componente `CashCount.php`. Se restauró el flujo de datos para el filtrado de devoluciones, permitiendo procesar arqueos sin interrupciones.

## [1.9.72] - 2026-04-07
### Changed
- **Flujo Continuo Inteligente (Cero Huecos)**: Eliminación de los saltos de página obligatorios por categoría. Ahora las categorías fluyen de forma ininterrumpida, permitiendo que los productos de una nueva sección rellenen los espacios vacíos de la anterior, optimizando al máximo el uso del papel.
- **Distribución Orgánica (9/12)**: El layout ahora se adapta dinámicamente: las hojas donde comienza una categoría acomodan naturalmente ~9 productos, mientras que las de transición alcanzan los 12, manteniendo imágenes de alto impacto de 150px.
- **Jerarquía Visual Protegida**: Implementación de reglas de CSS para evitar títulos huérfanos y filas cortadas, garantizando una lectura profesional en el nuevo modelo continuo hoy mismo.

## [1.9.71] - 2026-04-07
### Changed
- **Distribución Uniforme Definitiva (9/12)**: Consolidación del diseño de cuadrículas con 9 productos para páginas con título y 12 productos para páginas sin título. Se restauró el tamaño de imagen a 150px para un mayor impacto visual, manteniendo una altura de tarjeta compacta de 220px que elimina espacios muertos.
- **Uniformidad Visual**: Refinamiento de márgenes y espaciados internos para que todo el catálogo mantenga un ritmo profesional y equilibrado.

## [1.9.65] - 2026-04-07
### Performance
- **Caché de Imágenes (Base64)**: Implementación de un sistema de caché de capas (1 día para el logo, 7 días para productos) que almacena la codificación Base64 de las imágenes. Esto elimina la necesidad de re-procesar fotos en cada carga, acelerando drásticamente el tiempo de generación del PDF a partir del segundo uso.
- **Optimización de Consultas (Catálogo)**: Se refactorizó la extracción de datos para filtrar solo productos con estatus `available` y omitir categorías vacías, reduciendo la carga de memoria y el tiempo de respuesta del servidor.
- **DomPDF Acceleration**: Activación de `isFontSubsettingEnabled` y optimización de opciones del motor para un renderizado más fluido de estructuras complejas.

## [1.9.68] - 2026-04-07
### Changed
- **Optimización de Impacto Visual (Catálogo)**: Rediseño final de las tarjetas del catálogo. Se amplió el área de imagen a 140px para dar mayor protagonismo al producto y se redujo la altura total de la tarjeta a 210px eliminando espacios en blanco innecesarios.
- **Dureza de Cuadrícula (12 Cards)**: Ajuste de márgenes y paddings para asegurar que el diseño de alta densidad (12 productos por página) se mantenga equilibrado y profesional, con una tipografía compacta de alta legibilidad.

## [1.9.67] - 2026-04-07
### Changed
- **Alta Densidad de Cuadrícula (9/12)**: Implementación de un diseño ultra-compacto que permite visualizar 9 productos en páginas con título (3x3) y hasta 12 productos en páginas sin título (3x4). Esto aprovecha al máximo el espacio del papel, ideal para inventarios extensos.
- **Rediseño Compacto (Card UI)**: Ajuste de la altura mínima de las tarjetas (a 240px) y del contenedor de imágenes (a 110px) para garantizar que los elementos encajen perfectamente en las nuevas cuadrículas sin desbordar las páginas.
- **Tipografía Optimizada**: Reducción sutil de tamaños de fuente en nombres y precios para mantener el equilibrio visual en la alta densidad.

## [1.9.66] - 2026-04-07
### Changed
- **Maquetación Dinámica (6/9 Cards)**: Rediseño del flujo de cuadrícula en el PDF. Ahora, las páginas que inician una categoría muestran el título y un máximo de 6 productos para un diseño más aireado y elegante. Las páginas siguientes de la misma categoría se expanden a una cuadrícula de 3x3 (9 productos) para maximizar el aprovechamiento del papel sin perder la legibilidad.
- **Optimización de Saltos de Página**: Mejora en la lógica de segmentación de colecciones para asegurar transiciones limpias entre los distintos modos de visualización (6 vs 9 productos).

## [1.9.65] - 2026-04-07
### Added
- **Configuración de Catálogo (Precios)**: Se integró una nueva pestaña de "Catálogo" en la configuración del sistema que permite a los usuarios elegir si mostrar u ocultar los precios de venta y los precios base (USD) en el PDF generado. Esta opción es ideal para crear catálogos de marketing sin revelar precios de forma predeterminada.
- **Interruptores de Visibilidad**: Implementación de controles tipo 'toggle' en el panel de Ajustes para una gestión intuitiva de la visualización de precios en el PDF.

### Changed
- **Plantilla PDF Inteligente**: Mejora de la lógica del generador PDF para responder dinámicamente a la configuración de visibilidad, optimizando el espacio visual cuando los precios están ocultos.

## [1.9.63] - 2026-04-07
### Fixed
- **Motor de Imágenes Catálogo (Base64)**: Se implementó la codificación de imágenes en Base64 directamente en el controlador para garantizar que el logo y las fotos de productos se visualicen siempre en el PDF, independientemente de la configuración de enlaces simbólicos del servidor.
- **Estabilización de Layout (PDF)**: Eliminación de páginas en blanco redundantes mediante el ajuste de la lógica de saltos de página (`page-break`) y la remoción de atributos de altura que causaban desbordamiento.
- **Alineación de Cuadrícula**: Se estableció una altura mínima fija para las tarjetas de producto, asegurando una presentación simétrica y profesional incluso con nombres de productos de distinta longitud.

## [1.9.62] - 2026-04-07
### Added
- **Módulo de Catálogo Premium**: Implementación de un generador de catálogos profesionales en PDF con diseño de alta gama, incluyendo portadas personalizadas, organización por categorías y cuadrícula de productos con imágenes y precios.
- **Acceso Sidebar**: Integración del acceso directo "Catálogo PDF" con icono descriptivo en el menú lateral para una navegación intuitiva.

### Fixed
- **Estabilización DomPDF**: Corrección de un error de tipo en la asignación de configuraciones del motor de PDF que impedía el renderizado correcto de imágenes.

## [1.9.61] - 2026-04-07
### Added
- **Soporte de Edición en Descargos**: Se implementó la funcionalidad para modificar descargos en estado "**Pendiente**", unificando el flujo de trabajo con el módulo de Cargos.
- **Ruta de Edición**: Añadida la ruta `descargos/{descargo}/edit` en el archivo `web.php` para carga dinámica del componente `CreateDescargo`.

### Fixed
- **Error Multiple Root Elements (Descargos)**: Reestructuración total de la vista `create-descargo.blade.php` para cumplir con el requisito de raíz única de Livewire.
- **Estabilización de Propiedades**: Corrección de errores `PropertyNotFound` (`$comments`) y `UndefinedVariable` (`$cart`) en los módulos de Cargos y Descargos.
- **Defensa de Nulidad (ReportController)**: Se parcheó el error `format() on null` en el reporte diario de ventas mediante validación segura de fechas.
- **Sincronización en Compras**: Eliminación completa de crasheos por configuración nula (`config->vat`) en el componente de compras.

## [1.9.58] - 2026-04-07
### Fixed
- **Previsualización Vacía**: Se corrigió el error donde el reporte PDF (Previsualizar) salía "Sin datos" o en cero cuando no se seleccionaba una fecha. Ahora, si no hay filtro de fecha activo, el PDF muestra todo el historial de la pantalla actual.
- **Filtros de Fecha Opcionales**: Se sincronizó la lógica entre la web y el PDF: el "cerrojo" de inmutabilidad solo se activa si el usuario especifica un rango de fechas. Si no hay rango, se muestra el estado completo de las facturas (Totalmente pagas, etc.).

## [1.9.57] - 2026-04-07
### Fixed
- **Error format() on null**: Se corrigió el crash que ocurría al intentar generar un PDF sin seleccionar primero una fecha en los filtros. El sistema ahora usa la fecha actual por defecto.
- **Inmutabilidad de Créditos (F622)**: Se rediseñó el cálculo de deudas en los reportes. Ahora se basa en el **Balance Histórico** (Total - Pagos del Día) en lugar del estado actual de la factura. Esto garantiza que las facturas a crédito aparezcan como tales en reportes viejos, incluso si ya fueron pagadas después.
- **Sello de Inmutabilidad en PDF**: Se replicaron los filtros de relación (Pagos, Vueltos, Devoluciones) en el controlador del PDF (`ReportController`) para que los reportes impresos coincidan exactamente con lo que el usuario ve en pantalla.

### Added
- **Montos en Bs. (VED) en PDF**: Ahora el desglose de pagos en el reporte diario muestra el equivalente en Bolívares `[Bs. X.XX]` basándose en la tasa de cambio registrada en la transacción.

## [1.9.56] - 2026-04-07
### Fixed
- **Inmutabilidad Total de Reportes Financieros**: Se aplicó un "cerrojo" de fecha a todos los movimientos de una venta (Pagos, Vueltos y Devoluciones).
    - **Pagos Retroactivos**: Se corrigió el error donde los abonos a crédito realizados en fechas futuras afectaban los reportes históricos. Los reportes ahora solo muestran el flujo de dinero que ocurrió **exactamente** en el periodo consultado.
    - **Sincronización de Arqueo**: El Arqueo de Caja (`CashCount`) ahora filtra rigurosamente los `SalePaymentDetail` por fecha de creación, garantizando que el "Total a Entregar" sea una foto fiel del día.

## [1.9.55] - 2026-04-07
### Added
- **Optimización de Módulos de Inventario**: Aplicación de patrones de alto rendimiento en los módulos de **Cargos**, **Descargos** y **Compras**.
    - **Caché de Permisos**: Se eliminaron cientos de consultas redundantes a la base de datos mediante el pre-cálculo de autorizaciones en el método `mount()`.
    - **Integración de ConfigurationService**: Acceso optimizado a la configuración del IVA y el sistema, reduciendo el overhead en el renderizado de tablas de productos.
    - **Refactorización de Purchases.php**: Eliminación completa de `Configuration::first()` en el ciclo de `render()`, mejorando drásticamente la fluidez al interactuar con el carrito.

## [1.9.54] - 2026-04-07
### Added
- **Inmutabilidad de Reportes Financieros**: Se implementó una lógica de "Snapshot Temporal" en los reportes de Ventas Diarias, Arqueo de Caja y Reportes de Ventas.
    - **Inmutabilidad Histórica**: Los reportes generados en el pasado ahora son inmutables. El pago de un crédito futuro o una devolución posterior no alterarán los totales de días cerrados anteriormente.
    - **Cálculo de Créditos Estático**: La suma de créditos en el reporte se basa en el estado de la deuda al momento de la creación de la venta, garantizando la consistencia de la auditoría.
    - **Filtrado Temporal de Devoluciones**: Las devoluciones ahora solo afectan los reportes del día en que se procesaron, evitando que reduzcan retrospectivamente las ventas netas de periodos pasados.

### Fixed
- **Consistencia en Arqueo de Caja**: Se unificó la lógica de cálculo entre la vista de Livewire y el PDF del Arqueo para evitar discrepancias en los totales de moneda extranjera y pagos recibidos.

## [1.9.53] - 2026-04-07
### Added
- **Optimización Crítica de POS**: Reducción drástica del tiempo de carga inicial (~8s a <1s) mediante:
    - **Caché de Permisos**: Los permisos y configuraciones de módulos se calculan ahora una sola vez al cargar (`mount`), eliminando cientos de llamadas redundantes durante el renderizado.
    - **Servicio de Configuración Centralizado**: Se implementó una capa de caché para los ajustes del sistema, evitando consultas repetitivas a la tabla `configurations`.
    - **Middleware de Auto-Migración Reforzado**: Se sustituyó el motor de caché por archivos de bandera persistentes (`storage/framework/migrated_*.log`), eliminando la sobrecarga de Artisan en cada petición GET.
    - **Caché de Licencia**: La verificación de validez de la licencia se redujo de cada petición a una frecuencia de 1 hora mediante `Cache::remember`.
- **Estandarización de Componentes**: Preparación de la arquitectura para unificar el rendimiento en los módulos de Inventario y Compras bajo el mismo patrón de alta velocidad.

### Fixed
- **N+1 en Listado de Productos**: Se eliminaron las consultas recurrentes a `Auth::user()->can()` y `config()` dentro de los bucles de Blade, mejorando la respuesta visual del Punto de Venta.
- **Redundancia en AppServiceProvider**: Optimización del arranque global de la aplicación (Boot) para evitar el acceso directo a la base de datos en peticiones concurrentes.

## [1.9.51] - 2026-04-07
### Added
- **Estandarización de Clonación (Shortcuts)**: Se unificó el motor de clonación en todos los módulos (Ventas, Compras, Cargos y Descargos). Ahora el sistema reconoce sinónimos en español como `ENTRADA:`, `SALIDA:`, `COMPRA:`, `AJUSTE:` y `OC:` tanto en el escáner como en el buscador manual, facilitando la carga rápida de mercancía.
- **Leyenda de Comandos**: Se añadió una tabla de referencia en **Configuración -> Móvil** que detalla todos los códigos de clonación disponibles para guía del usuario.
- **Buscador de Clientes (Generador de Precios)**: Se implementó un buscador inteligente (TomSelect) en el Generador de Listas de Precios. Los usuarios ahora pueden buscar clientes por nombre, RIF o dirección en lugar de desplazarse por una lista estática, mejorando drásticamente la usabilidad con bases de datos grandes.
- **Configuración de Bloque de Pagos (PDF)**: Se añadió una opción en la configuración de la Lista de Precios para mostrar u ocultar el bloque informativo de pagos (Vencimiento, Mora y cabecera BCV) en el PDF generado.
- **Filtrado por Vendedor en Búsqueda**: El buscador de clientes ahora se sincroniza automáticamente con el vendedor seleccionado (modo Administrador), mostrando únicamente los clientes pertenecientes a la cartera del asesor elegido.

### Fixed
- **Integridad de Datos en Clonación**: Se resolvió un error técnico (`TypeError: json_decode`) en el módulo de compras que ocurría al intentar clonar documentos con metadatos complejos.

## [1.9.50] - 2026-04-06

## [1.9.49] - 2026-04-06
### Added
- **Justificación Obligatoria de Anulaciones**: Se implementó un sistema de auditoría que requiere obligatoriamente un motivo para cada anulación o solicitud de borrado.
- **Unificación de Flujo de Borrado**: Se integró un único aviso (SweetAlert) para todos los roles (administradores y operadores), eliminando la posibilidad de "borrados silenciosos".
- **Transparencia en Reportes**: El motivo de anulación ahora es visible directamente en la tabla del reporte de ventas y en el modal de detalles, incluyendo quién solicitó y quién aprobó la acción.

## [1.9.48] - 2026-04-06
### Added
- **Garantía Correlativa Sin Saltos**: Se blindó el motor de folios para que use un contador transaccional (`lockForUpdate`). Esto garantiza que **nunca** existan huecos en la numeración (1, 2, 3, 4...), incluso si una transacción falla, manteniendo siempre la concordancia 1:1 con el ID.
- **Auto-Calibración en Actualización**: Nueva migración de base de datos que alinea automáticamente los contadores de configuración con el ID más alto de las tablas, facilitando la transición automática para todos los clientes.

## [1.9.47] - 2026-04-06
### Added
- Feature: Ventas y Órdenes ahora tienen un Folio (invoice_number) armonizado 1:1 con el ID de la base de datos de forma automática.
- Migración de base de datos integrada para sincronizar números de folio en registros históricos sin intervención manual.

### Fixed
- **Armonización de Folio vs ID**: Se eliminó el desfase entre el número de venta ("#731") y el folio ("F00000724"). Ahora ambos coincidirán permanentemente (ej: ID 731 = Folio F00000731).
- **Consistencia en PDF/Reportes**: Se actualizó el motor de reportes y generación de PDFs para que utilicen el folio formateado directamente.
- **Búsqueda Avanzada**: Se optimizó la búsqueda en los reportes de ventas para que localice registros por ID o Folio indistintamente y con mayor precisión.
- **Detalle de Venta**: El título del modal de detalles ahora refleja fielmente el folio de facturación.


### Added
- **Optimización de QR de Clonación**: Rediseño del código QR de clonación (SALE/ORD) con tamaño reducido (2x2) y centrado perfecto en el área de disclaimer. Se eliminó el texto redundante para un acabado más limpio y profesional.

### Fixed
- **Precisión Financiera (Subtotal)**: Se corrigió la discrepancia de redondeo en el ticket térmico; ahora el Subtotal y el IVA mantienen sus decimales y coinciden exactamente con el Total facturado.
- **Protección contra Scanner (Atajos)**: Se reforzó el bloqueo del atajo `Shift+D` durante el escaneo de órdenes (ORD) para evitar que se abran modales de cliente de forma accidental.
- **Diseño de PDFs (Limpieza)**: Se eliminó el campo duplicado de "Total Amount" que aparecía en las cabeceras de los archivos PDF generados.

## [1.9.45] - 2026-04-06
- **Integridad de Clonación (Scanner)**: Se optimizó el motor de detección de códigos de barra para aceptar el prefijo `ORD:` o `SALE:` con mayor precisión, garantizando que el punto de venta cargue los documentos clonados independientemente de la velocidad del escáner.

## [1.9.44] - 2026-04-06
### Corregido
- **Persistencia de Órdenes (Pedidos Fantasma)**: Se eliminó el error que mantenía los pedidos en estado "Pendiente" después de facturarlos. Ahora la orden desaparece automáticamente del modal al concretarse la venta, gracias a una nueva lógica de cierre atómico transaccional.
- **Flujo de Venta Atómica**: Se sincronizó la creación de la factura con el cierre de la orden primaria, eliminando la necesidad de que el cajero borre pedidos manualmente para liberar el stock.

## [1.9.43] - 2026-04-06
### Corregido
- **Integridad de Inventario (Crédito)**: Se resolvió la discrepancia donde los productos variables vendidos a crédito seguían apareciendo como "Reservados". Ahora, toda Factura (contado o crédito) marca los ítems como "Vendidos" inmediatamente, reflejando su salida física del almacén.
- **Restauración de Stock (Cancelaciones)**: Se reparó un error crítico en el motor de anulaciones que impedía devolver las cantidades al stock disponible tras cancelar una venta. El sistema ahora garantiza la bidireccionalidad total de los inventarios (Maestro y Depósitos) en procesos de anulación.

## [1.9.42] - 2026-04-06
### Added
- **Gestión Bancaria (Titulares)**: Se añadió el campo "Titular de la Cuenta" a la estructura de bancos. Ahora se muestra el nombre oficial del dueño de la cuenta en todos los reportes para mayor seguridad en transferencias.
- **Diseño Premium Responsivo**: Rediseño total del selector de bancos en el formulario de vendedores con cards interactivas, estados dinámicos y soporte **Mobile-First** obligatorio.
- **Identidad Local (Pago Móvil)**: Se renombró la etiqueta "Teléfono" a "**Pago Móvil**" en todos los formularios de banco y plantillas PDF para una mejor guía de cobranza.

### Fixed
- **Blindaje de PDFs (Estabilidad)**: Implementado sistema de validación `file_exists` para logos de empresa. El sistema ahora realiza un fallback automático al logo por defecto si el archivo configurado no existe, eliminando errores 500 (pantalla en blanco) al abrir órdenes.
- **Optimización de Datos (Eager Loading)**: Se corrigió la carga de relaciones en el motor de PDF para asegurar que los bancos del vendedor aparezcan siempre en órdenes pendientes y procesadas.

## [1.9.41] - 2026-04-06
### Corregido
- **Creación de Usuarios**: Se corrigió un error crítico de base de datos (SQLSTATE 23000) que impedía la creación de usuarios con roles que no son de venta (como el rol de "Driver"). El sistema ahora inicializa automáticamente los campos de configuración de crédito del vendedor con valores por defecto en lugar de nulos, garantizando la integridad de la base de datos en todas las operaciones de guardado.

## [1.9.40] - 2026-04-01
### Corregido
- **Relación de Pagos:** Se corrigió bug donde pagos aprobados hoy pero subidos ayer aparecían en el reporte de ayer. Ahora al aprobar se mueven a la planilla de hoy.
- **Sincronización Financiera:** Se unificó la lógica de aprobación entre Cuentas por Cobrar y Pagos Parciales.
- **Integridad de Caja:** Se añadió el registro de movimiento en caja para aprobaciones administrativas (faltaba en reportes AR).
- **Planillas de Recaudación:** El total de la planilla (`total_amount`) ahora solo se incrementa al aprobar el pago, garantizando consistencia con los reportes.

## [1.9.39] - 2026-04-01
### Added
- **Automatización de Oficina:** Se implementó un "Override" de automatización para usuarios con rol de oficina. Ahora, al realizar operaciones administrativas, el sistema ignora las restricciones de fletes y comisiones foráneas, permitiendo una gestión centralizada sin bloqueos de permisos.

### Fixed
- **Búsqueda Visual:** Corregido el error de renderizado en el buscador de productos que impedía la selección rápida mediante teclado en dispositivos táctiles.
- **Automatización de Precios:** Corregida la jerarquía de precios en el POS para que los incrementos (comisión, flete y diferencial) se activen automáticamente al seleccionar un cliente.
- **Vendedores Foráneos:** Eliminada la restricción de visibilidad de fletes que afectaba a usuarios con permisos limitados.
- **Persistencia de Precios:** Mejorada la hidratación del componente para mantener los precios inflados tras recargar la página.

## [1.9.38] - 2026-04-01
### Fixed
- **Búsqueda Visual:** Corregido el error de renderizado en el buscador de productos que impedía la selección rápida mediante teclado en dispositivos táctiles.
- **Automatización de Precios:** Corregida la jerarquía de precios en el POS para que los incrementos (comisión, flete y diferencial) se activen automáticamente al seleccionar un cliente.
- **Vendedores Foráneos:** Eliminada la restricción de visibilidad de fletes que afectaba a usuarios con permisos limitados.
- **Persistencia de Precios:** Mejorada la hidratación del componente para mantener los precios inflados tras recargar la página.

## [1.9.36] - 2026-04-01
- **HOTFIX: Visibilidad de Vendedores Foráneos**: Se corrigió el error que impedía visualizar y asignar vendedores con el rol `Vendedor foraneo` al crear o editar clientes. Ahora la lista desplegable unifica a todos los asesores comerciales independientemente de su categoría (Local o Foráneo).

## [1.9.35] - 2026-04-01
- **Optimización de Comisiones (Fecha de Entrega)**: Se implementó una nueva lógica de cálculo para los días transcurridos. Ahora el sistema prioriza la fecha de entrega real (`delivered_at`) registrada por el chofer. En caso de no existir, se otorga automáticamente 1 día de gracia sobre la fecha de factura, protegiendo al vendedor ante retrasos administrativos.
- **Seguridad en Comisiones (Estados de Venta)**: Se blindó el módulo de comisiones y sus reportes PDF para excluir automáticamente facturas con estados no válidos (`returned`, `voided`, `cancelled`, `anulated`), evitando pagos sobre ventas no concretadas.
- **Privacidad y Permisos de Comisiones**: Se implementaron los nuevos permisos `commissions.view_all` y `commissions.view_own`. Ahora los vendedores foráneos solo pueden ver sus propias comisiones en las notificaciones, el dashboard y el módulo principal, mientras que los administradores conservan la visión global.
- **Automatización de Actualización**: Se incluyó una migración de base de datos que registra automáticamente los nuevos permisos durante el proceso de actualización, garantizando que la funcionalidad esté lista sin comandos manuales.
- **Precisión en Pagos de Cuentas por Cobrar**: Se habilitó el campo "Fecha de Pago" como obligatorio para todos los pagos en efectivo (independientemente de la moneda), permitiendo registrar la fecha real del recibo físico para una auditoría financiera exacta.

## [1.9.34] - 2026-03-31
- **Integridad Financiera (Deuda de Clientes)**: Se corrigió un error crítico en el Punto de Venta (POS) donde las facturas marcadas como `returned` (devueltas) o `voided` (anuladas) seguían sumando al saldo total del cliente. Ahora el sistema las excluye automáticamente para garantizar que la deuda mostrada sea 100% real y vigente.
- **Sincronización Web-PDF (Cuentas por Cobrar)**: Se unificaron los filtros de exclusión de estatus en todo el ecosistema de reportes. Las facturas anuladas, devueltas o canceladas ahora desaparecen consistentemente tanto del tablero interactivo como del reporte impreso.
- **Blindaje de Cobros**: Se eliminó la visibilidad del botón "Pagar" para facturas inactivas en el reporte de Cuentas por Cobrar, evitando intentos de cobro sobre documentos ya procesados comercialmente.
- **Precisión en Totales de Reporte**: Se refactorizó el motor de cálculo del reporte de Cuentas por Cobrar para que los totales de cabecera (Costo, Venta, Ganancia, Deuda) reflejen la suma global de TODOS los resultados filtrados, corrigiendo el bug anterior que solo calculaba basándose en los registros de la página actual.

## [1.9.33] - 2026-03-31
- **Mejora de Experiencia de Usuario (Catálogo)**: Se añadió un retraso (debounce) de 500ms en los campos de Costo, Incremento, Margen y Precio de Venta. Esto evita que el sistema borre o sobreescriba lo que el usuario está escribiendo antes de terminar el ingreso de datos, permitiendo una edición de precios más fluida y sin interrupciones.

## [1.9.32] - 2026-03-31
- **Reinicio Automático de Base de Datos**: Se ha forzado esta nueva versión para garantizar que el sistema ejecute obligatoriamente cualquier migración pendiente (ID de bobinas, metadatos) en los clientes que se quedaron en un estado intermedio.
- **Limpieza de Caché Interna**: Se añadió una directiva de limpieza de caché automática durante el proceso de actualización para asegurar que los componentes de Livewire reflejen los cambios inmediatamente.

## [1.9.31] - 2026-03-31
- **Corrección Crítica (Auto-Update SQL)**: Se implementó un middleware de **Auto-Migración** que detecta cambios en el esquema de la base de datos tras una actualización de código. Esto resuelve el error `Unknown column 'metadata'` de forma totalmente transparente para el cliente sin necesidad de comandos manuales.
- **Persistencia de Bobinas**: Se añadieron las columnas `metadata` faltantes en las tablas `sale_details` y `order_details` para garantizar el registro exacto de las bobinas físicas en cada pedido y venta.

## [1.9.30] - 2026-03-31
- **Corrección Crítica (Auto-Update SQL)**: Se implementó un middleware de **Auto-Migración** que detecta cambios en el esquema de la base de datos tras una actualización de código. Esto resuelve el error `Unknown column 'metadata'` de forma totalmente transparente para el cliente sin necesidad de comandos manuales.
- **Persistencia de Bobinas**: Se añadieron las columnas `metadata` faltantes en las tablas `sale_details` y `order_details` para garantizar el registro exacto de las bobinas físicas en cada pedido y venta.

## [1.9.29] - 2026-03-31
- **Individual en Carrito (Ventas/Compras)**: Se implementó una lógica de eliminación basada en índices y UIDs únicos en el resumen de venta, permitiendo que múltiples bobinas o productos iguales coexistan como filas independientes y puedan ser eliminados uno a uno sin afectar al resto de la familia de productos.
- **Trazabilidad de Inventario**: Al eliminar un ítem o cancelar la venta/compra, el estado de los productos variables (`ProductItem`) se restaura automáticamente a "**Disponible**" en la base de datos, garantizando una integridad absoluta del stock físico en todo momento.
- **Estabilidad de Interfaz**: Se resolvieron errores de reactividad de Livewire (`PropertyNotFoundException` y desajustes de índices) en el resumen de venta, facilitando un flujo de trabajo fluido y sin interrupciones técnicas.
- **Unificación de Modales**: Se estandarizaron los identificadores de modales en el módulo de compras para resolver conflictos de DOM al añadir productos por peso/unidad (`variableItemModal`).

## [1.9.28] - 2026-03-30
- **Transparencia en Pagos Zelle**: Se eliminó la etiqueta "Desconocido" en todos los canales de reporte (Arqueo en Vivo, PDF de Venta Diaria, PDF de Arqueo y Ticket Térmico). Ahora el sistema carga forzosamente el nombre del remitente y la referencia bancaria para cada transacción Zelle, garantizando la trazabilidad financiera total solicitada por la gerencia.
- **Segregación de NC Antiguas**: Se implementó una lógica que detecta si una Nota de Crédito pertenece a una factura de días anteriores. Estas NC aparecerán etiquetadas como "**Venta Antigua**" y no afectarán el arqueo de ventas de hoy, resolviendo la discrepancia de los $36.71 detectada en la auditoría técnica.
- **Impacto en Arqueo**: Las NC de días anteriores marcadas como "Reducción de Deuda" o "Billetera" ya no restan de la responsabilidad física del cajero hoy, manteniendo el arqueo perfectamente sincronizado con el dinero real en caja.
- **Etiquetas en PDF**: Se añadió la columna "Afecta Caja" en la tabla de NC del reporte diario para total transparencia para el cliente.

## [1.9.27] - 2026-03-30
- **Transparencia en Facturas**: Se añadió el desglose de "EFECTIVO USD" en la descripción del reporte PDF para facturas mixtas, asegurando que la suma de pagos coincida visualmente con la columna de Dólares.
- **Sincronización de Totales**: Se repararon los acumuladores de pie de página (Neto, Dólares, Créditos, VED, COP) para garantizar que coincidan exactamente con la suma de las transacciones individuales.

## [1.9.26] - 2026-03-30

### Fixed
- **Reporte de Ventas Diarias (Matemática)**: Corregido un bug crítico donde el "Total Contado" del encabezado se sobreescribía con el "Total Bruto", causando reportes de ingresos inflados que no reflejaban la realidad de la caja.
- **Reporte de Ventas Diarias (Layout)**: Renombrada la columna "Contado" a "Dólares" y ajustada su lógica para que solo muestre el componente pagado en USD/Divisas. Ahora, si se paga en Bolívares o Pesos, el monto aparece en sus respectivas columnas y se muestra como 0 en la columna de Dólares (tal como lo solicitó el cliente).
- **Reporte de Ventas Diarias (Redundancia)**: Eliminada la columna "Divisas" del final de la tabla para un diseño más limpio y evitar duplicidad de información.
- **Claridad de Totales**: Se mejoraron las etiquetas del bloque de resumen superior ("Total Cobrado Eq. USD", "Total Neto Facturado", "Total Ingresos Caja") para una mejor interpretación administrativa por parte de los supervisores.

## [1.9.25] - 2026-03-30

### Fixed
- **Reporte de Ventas Diarias (PDF)**: Corregido el error de layout donde facturas con múltiples pagos (como la factura 629) causaban que las columnas se desplazaran horizontalmente. Ahora los detalles de pago se muestran en líneas separadas dentro de la columna de descripción, respetando el ancho de la tabla y facilitando la impresión.

## [1.9.24] - 2026-03-30

### Fixed
- **Conciliación de Arqueo de Caja**: Se unificó la lógica financiera entre el Dashboard (Livewire), el Reporte PDF y el Ticket Térmico. Ahora los tres canales de reporte reflejan un "Total a Entregar" consistente y matemáticamente exacto.
- **Segregación de Billetera Virtual**: Se implementó la visualización clara de los movimientos de billetera (Custodia Hoy y Consumo de Saldo Anterior) en todos los reportes de arqueo.
- **Precisión en Ventas del Día**: Ahora el arqueo reporta el flujo de caja **NETO** (Ventas menos Devoluciones), eliminando la inflación artificial de ingresos cuando se generan Notas de Crédito que se quedan en custodia.
- **Ticket Térmico Profesional**: Se rediseñó el ticket de corte (`PrintTrait`) para detallar los pagos por Banco y Zelle (incluyendo emisor/referencia) y mostrar el desglose de movimientos de billetera.

## [1.9.23] - 2026-03-30

### Added
- **Previsualización de Reporte Cuentas por Cobrar**: Se implementó un nuevo botón "Previsualizar" con icono de ojo que abre un modal con un visualizador PDF (iframe), permitiendo revisar el reporte antes de descargarlo.
- **Rediseño Profesional de Reporte AR**: Se reestructuró totalmente la plantilla PDF (`accounts-receivable-pdf`) siguiendo un diseño de alta gama:
    - Agrupación por cliente con bloques de información (Código, Dirección, Teléfono, etc.).
    - Detalle de transacciones (Operación, Emisión, Vencimiento, Días de Mora, No. Doc, Descripción y Monto).
    - Desglose matemático exacto: La línea de "Factura" muestra el saldo previo y las "N/C" se restan visualmente para que el total por cliente sea intuitivo y cuadre a simple vista.
- **Optimización de Paginación**: Se corrigió un error de DomPDF que generaba grandes espacios en blanco al inicio de página. Ahora el reporte corta y fluye naturalmente entre hojas.
- **Control de Acceso Dinámico**: El reporte PDF ahora respeta estrictamente los permisos del usuario; si el operador no tiene permiso de "Ver todo", el reporte solo incluirá sus propios movimientos.

## [1.9.22] - 2026-03-28

### Fixed
- **Anulación de Pagos (Cuentas por Cobrar)**: Se corrigió un error crítico de base de datos (SQL 1265 - Data truncated) que ocurría al intentar anular un pago, causado por un valor de estado inválido.
- **Sincronización de Caja**: Ahora, al anular o eliminar un pago desde el reporte de Cuentas por Cobrar, el monto se resta automáticamente de la Hoja de Recaudación (Collection Sheet) para mantener la integridad del cuadre de caja.
- **Estado de Factura**: Se optimizó la lógica de liquidación para que las facturas regresen automáticamente al estado "Pendiente" si, tras una anulación de pago, el saldo deja de estar cubierto, permitiendo una trazabilidad exacta de la deuda.

## [1.9.21] - 2026-03-28

### Added
- **Persistencia de Depósito en Compras**: Se añadió el campo `warehouse_id` a la tabla de `purchases` para mantener un registro histórico de en qué almacén entró la mercancía comprada.
- **Trazabilidad en Kardex**: El reporte de Movimientos de Producto (Kardex) ahora muestra el nombre real del depósito para las nuevas compras.

### Fixed
- **Filtro de Depósito en Kardex**: Se corrigió el filtrado por almacén que no se estaba aplicando correctamente en las consultas SQL del reporte.
- **Filtro de Devoluciones**: Se integró el filtrado por depósito en los movimientos de Notas de Crédito/Devoluciones dentro del Kardex.
- **Interfaz de PDF**: Se reparó el botón "Cerrar" del modal de previsualización de PDF que no respondía a la interacción del usuario.

## [1.9.20] - 2026-03-28

### Fixed
- **Automatización de Notificaciones (Ventas y Abonos)**: Se corrigió la lógica de envío automático para que los Abonos (Recibos de Pago) se despachen instantáneamente por correo electrónico, al igual que las ventas.
- **Generación de PDF de Pagos**: Se implementó una nueva plantilla de PDF específica para los recibos de pago (`payment-history-pdf`), asegurando que las notificaciones de abono adjunten el documento correcto y no la factura de venta original.
- **Sincronización de Preferencias del Cliente**: Se optimizó el motor de notificaciones para que respete estrictamente los interruptores de "Notificar Ventas" y "Notificar Abonos" de cada cliente. Ahora, si se desactiva una opción, el sistema deja el mensaje "En Cola" en lugar de enviarlo automáticamente, manteniendo la sincronización entre lo que se ve en la configuración y el comportamiento del servidor.
- **Estabilidad del Worker**: Se implementó la recarga forzada del modelo `Customer` desde la base de datos en cada tarea de notificación, eliminando problemas de datos obsoletos o nulos en el servidor de segundo plano.

## [1.9.19] - 2026-03-27

### Added
- **Edición de Cargos**: Añadida la opción para modificar los Cargos "Pendientes". Los usuarios pueden actualizar las cantidades, eliminar productos, cambiar el motivo y ajustar el detalle antes de aplicar el impacto definitivamente al inventario.

### Changed
- **Procesos en Segundo Plano Eficientes (Colas)**: Para evitar cuelgues al crear Cargos o procesos largos, se implementó que la generación de recibos PDF, envíos de correo del comprobante y notificaciones vía WhatsApp API ahora corran 100% en cola de procesos (background Database Jobs) usando el Worker del sistema configurado mediante NSSM. Se migró el entorno y el sistema es ahora considerablemente más fluido.


## [1.9.18] - 2026-03-26

### Changed
- **Requisición (Déficit)**: Simplificación de la columna "Déficit (A Comprar)". Se eliminó el texto redundante y ahora sólo se muestran los íconos de colores junto con los números precisos para mejorar el minimalismo visual y evitar distractores en pantalla.


## [1.9.17] - 2026-03-26

### Changed
- **Mejoras Visuales en Requisición**: Se rediseñó la columna "Déficit (A Comprar)" en la tabla de Sugerencias de Compras. Ahora muestra textos explícitos con código de colores en lugar de números matemáticos: Verde ("Óptimo") para stock en cero déficit, Rojo ("Faltan X") cuando la mercancía realmente falta, y Azul ("Sobran X") cuando el inventario cruza holgadamente el máximo sugerido. Ninguna cantidad negativa causará confusión nuevamente.


## [1.9.16] - 2026-03-26

### Added
- **Métricas Financieras en Catálogo**: Se agregó la columna de "Costo" ($) y la de "Inc. / Margen" (%) a la tabla principal de productos. Esto permite a los usuarios previsualizar el costo real, el incremento sobre el costo y el margen de ganancia de venta sin necesidad de entrar al modo de edición.


## [1.9.15] - 2026-03-26

### Added
- **Actualización Masiva de Precios**: Nueva sección en la Configuración del Sistema que permite ajustar (aumentar o descontar) el Costo de Compra o el Precio de Venta mediante un porcentaje. Cuenta con filtros obligatorios por Categoría y/o Proveedor y panel de confirmación irreversible.
- **Sincronización Bidireccional de Precios**: Se agregó un nuevo campo en el catálogo de productos para el "Porcentaje de Incremento sobre Costo". Ahora el sistema sincroniza automáticamente en tres vías: Margen de Ganancia, Porcentaje de Incremento y Precio de Venta.

## [1.9.14] - 2026-03-26

### Fixed
- **Historial de Pagos**: Reescritura del archivo `historypays.blade.php` para restaurar los botones y funcionalidades perdidas (Aprobar, Rechazar, Anular, Imprimir Recibo) manteniendo la corrección de la estructura "Multiple Root Elements" de Livewire.

## [1.9.13] - 2026-03-26

### Fixed
- **Estatus Cuentas por Cobrar**: Corregida lógica central donde las facturas no cambiaban a "Pagado" al saldar la deuda restante usando devoluciones (Notas de Crédito).
- **Corrección Retroactiva Automática**: Incluida migración que escanea facturas históricas a crédito en estado "Pendiente" y las marca como pagadas si su saldo fue cubierto por devoluciones, generando sus respectivas comisiones.

## [1.9.12] - 2026-03-26

### Added
- **Módulo de Reporte de Inventario (Stock)**: Nueva interfaz profesional inspirada en el reporte de despacho para la gestión de existencias.
- **Configuración de Columnas Dinámicas**: Capacidad de activar/desactivar campos como SKU, Nombre, Categoría, Proveedor, Costo, Precio y Valuaciones.
- **Campo de Conteo Físico**: Opción para incluir una columna vacía en el PDF diseñada para inventarios manuales con lápiz/bolígrafo.
- **Columna de Utilidad (UT. %)**: Visualización del margen de ganancia por producto basado en el costo y precio de venta actual.
- **Firmas Personalizables**: Selección dinámica de hasta 4 líneas de firma (Elaborado, Autorizado, Gerencia, Auditoría/Almacén) al pie del reporte.
- **Acceso Directo en Sidebar**: Integración del nuevo reporte en la sección de "REPORTES" del menú principal.

### Changed
- **Plantilla de Orden de Compra**: Optimización del diseño del PDF de compras, incluyendo la columna "Nuevo Costo" y mejora en la disposición de la información del proveedor.

## [1.9.11] - 2026-03-26
### Added
- Feature for Purchase Order PDF generation with an empty "Nuevo Costo" column for manual entry.
- `PurchaseController` for handling purchase report requests.
- Custom PDF template `invoice-purchase-order.blade.php` for professional purchase orders.
- "Print" action button in the "Procesar Ordenes de Compra" modal.

## [1.9.10] - 2026-03-26

### Fixed
- **Solución Definitiva a 'Multiple Root Elements'**: Reescrita la estructura de `historypays.blade.php` para garantizar un balance perfecto de etiquetas div, permitiendo que el componente `purchase-partial-payment` se monte correctamente en Livewire sin excepciones estructurales.

## [1.9.9] - 2026-03-26

### Fixed
- **Excepción de Raíz Livewire**: Corregido el error de "Multiple Root Elements" en `historypays.blade.php` al envolver los estilos y el contenido en un único div. Esto permite que el modal de Abonos funcione sin errores.

## [1.9.8] - 2026-03-26

### Fixed
- **Balance de Etiquetas HTML**: Corregida la falta de cierre de divs en `historypays.blade.php` que causaba la rotura del diseño responsivo en Ventas y Compras, obligando a los componentes a apilarse verticalmente.

## [1.9.7] - 2026-03-26

### Fixed
- **Estructura de Rejilla - Compras**: Implementación de `container-fluid` y corrección de etiquetas Livewire para garantizar que el sidebar se mantenga a la derecha y no se desplace debajo de la tabla.

## [1.9.6] - 2026-03-26

### Fixed
- **Layout Responsivo - Compras**: Cambio de breakpoints (lg a md) para evitar que el resumen de compra se baje en pantallas medianas o portátiles.

## [1.9.5] - 2026-03-26

### Added
- **Rediseño Premium - Compras**: Interfaz totalmente renovada con sidebar de resumen fijo, diseño de tarjetas redondeadas, gradientes modernos y mejora en la legibilidad de la tabla de items.

## [1.9.4] - 2026-03-26

### Fixed
- **Estructura Blade de Pagos**: Corregida la presencia de etiquetas de cierre div redundantes en `historypays.blade.php` que causaban errores de "Multiple root elements" en el módulo de compras y abonos.

## [1.9.3] - 2026-03-26

### Fixed
- **Estabilidad del Módulo de Compras (Crucial)**: Corregido un error estructural de "Multiple root elements" en la vista Blade que impedía el acceso al módulo de compras.
- **Null-Safety en Inicialización**: Añadida protección nula al cargar la configuración por defecto del almacén en el módulo de compras, previniendo cuelgues al iniciar.
- **Ambigüedad en Consultas de Reportes**: Resueltos errores de "Column 'created_at' is ambiguous" en los reportes mediante el uso de prefijos de tabla explícitos.
- **Gestión de Proveedores en Órdenes**: Corregida la visualización y filtrado de órdenes de compra que no tienen un proveedor asociado (común en órdenes automáticas desde Requisición).

### Changed
- **Reporte de Billetera (Ingresos vs Uso)**: El reporte de Arqueo de Caja ahora desglosa correctamente el uso de la billetera virtual, diferenciando entre ingresos del día (devoluciones) y pagos realizados con saldos anteriores.

## [1.9.2] - 2026-03-25

### Added
- **Precisión Financiera de 4 Decimales (Soporte Global)**: Implementación de arquitectura completa para soportar 4 decimales en toda la cadena de suministro y ventas. 
    - **Base de Datos**: Migración masiva de columnas de costo y precio de `decimal(15, 2)` a `decimal(15, 4)` en productos, órdenes, ventas, compras e inventario.
    - **Configuración Dinámica**: El sistema ahora lee `getDecimalPlaces()` de forma centralizada para aplicar el redondeo configurado en todos los cálculos y traits.
    - **Visualización en PDF**: Actualización de plantillas de reportes de Ventas Diarias y Cuadre de Caja para mostrar montos con 4 decimales.

### Changed
- **Motor de Interfaz (UX)**: Mejorada la visualización de precios en el POS para mostrar ceros a la derecha (ej. `12.4300`) mediante `number_format`, garantizando claridad en productos con precios de alta precisión.
- **Validación de Entradas**: Las funciones globales de JavaScript (`justNumber`, `validarInputNumber`) ahora permiten hasta 4 decimales durante el tipeo manual.
- **Módulos POS y Compras**: Se añadió el atributo `step="0.0001"` a todos los campos de entrada de costos y precios para permitir ajustes granulares sin restricciones del navegador.

### Fixed
- **Redondeo en Inventario**: Corregida la visualización de valorización de stock que estaba forzada a 2 decimales en la vista de inventario.

## [1.9.1] - 2026-03-24

### Added
- **Flujo de Aprobación de Descargos**: Migración completa del motor de flujo de trabajo para salidas de inventario (Descargos / Ajustes de Reducción). Ahora las salidas se registran como `Pendientes` y requieren autorización para descontar definitivamente el stock.
- **Notificaciones de WhatsApp para Descargos**: Integración con el motor de WhatsApp para enviar alertas automáticas de nuevas salidas a los supervisores, incluyendo el PDF del ajuste adjunto.
- **Configuración Independiente de Plantillas**: Nueva sección en los Ajustes de WhatsApp para personalizar los mensajes de Descargos de forma separada a los Cargos, con soporte para variables como `[DESCARGO_ID]`. (Permite habilitar/deshabilitar por separado).
- **Estados de Carga (UX)**: Se implementaron indicadores visuales de "PROCESANDO..." y bloqueo de botones al guardar Cargos y Descargos para evitar duplicidad de registros y mejorar la respuesta visual (especialmente durante la generación de PDF).

### Fixed
- **Integridad de Stock en Anulaciones**: Corregido error crítico donde la anulación (void) de un ajuste ya aprobado no revertía el inventario. Ahora, anular un Cargo DECRECE el stock y anular un Descargo lo INCREMENTA, incluyendo la recreación/eliminación de ítems variables (bobinas) para mantener la trazabilidad exacta.
- **Consistencia de Base de Datos**: Actualización de la columna `status` en la tabla de Cargos de ENUM a STRING para evitar el error "Data truncated" al usar los nuevos estados de flujo de trabajo (`rejected`, `voided`).
- **Visualización de Auditoría**: Mejora integral de las vistas de detalle para mostrar quién aprobó, rechazó o anuló cada ajuste, junto con la fecha y el motivo obligatorio de la acción.

## [1.9.0] - 2026-03-24

### Added
- **Adjuntos de PDF en Notificaciones**: Implementación de generación dinámica de documentos PDF para cada Cargo (Ajuste). Los archivos se adjuntan automáticamente tanto al **Correo Electrónico** como al mensaje de **WhatsApp** enviado a los aprobadores.
- **Reporte Detallado de Cargo**: Nueva plantilla profesional de comprobante interno de ajuste de inventario, incluyendo motivo, almacén, items cuantificados y costos valorados.
- **Botón de Descarga Manual**: Se añadió el ícono de descarga de PDF directamente en el listado de cargos para un acceso rápido a los documentos históricos.

## [1.8.99] - 2026-03-24

### Added
- **Flujo de Aprobación de Cargos**: Nueva arquitectura de flujo de trabajo para ajustes manuales de inventario (Cargos). Los ajustes ahora se registran como `Pendientes` y no modifican el stock hasta ser formalmente aprobados por un usuario autorizado.
- **Notificaciones Multi-canal (Avisos de Ajuste)**: Integración automática de notificaciones por **Email y WhatsApp** que se envían a todos los supervisores/administradores al momento de registrar un nuevo ajuste.
- **Gestión de Plantillas**: Nueva sección en la configuración del sistema para activar/desactivar y personalizar los mensajes de notificación de ajustes, incluyendo soporte para variables dinámicas.
- **Justificación Obligatoria**: Se implementó el requerimiento de motivo (textarea) para todas las acciones de **rechazo** y **anulación** de cargos, reforzando la trazabilidad de la auditoría.
- **Permisos de Flujo de Trabajo**: Nuevos permisos granulares `adjustments.approve_cargo`, `adjustments.reject_cargo` y `adjustments.delete_cargo` para un control total del ciclo de vida del inventario.

### Changed
- **Optimización de Productos Variables**: Los detalles de bobinas y items de báscula ahora se almacenan de forma temporal en formato JSON durante la fase pendiente, creándose los registros definitivos de `ProductItem` únicamente tras la aprobación final.

## [1.8.98] - 2026-03-24

### Added
- **Módulo Inicial de Cargos (Ajustes)**: Implementación de la fase 1 del sistema de ajustes de inventario manual con soporte para productos variables (bobinas) y búsqueda optimizada.

### Fixed
- **Restauración de Stock (Integridad)**: Corregido error crítico donde la eliminación de una venta no devolvía el stock al depósito de origen. Ahora se restaura correctamente tanto el stock global como el específico por almacén, incluyendo componentes de productos dinámicos.
- **Redondeo en Ticket (POS)**: Se ajustó el motor de cálculo en el recibo de venta para mostrar el subtotal con 2 decimales exactos, eliminando la discrepancia visual con el total cobrado.

## [1.8.92] - 2026-03-24
 
## [1.8.90] - 2026-03-24

### Added
- **Anulación de Pagos (Cuentas por Cobrar)**: Nueva funcionalidad para anular pagos aprobados sin eliminarlos del historial, permitiendo mantener una auditoría completa.
- **Motivo de Anulación**: Se integró un campo obligatorio para registrar el motivo de la anulación del pago, visible tanto en el historial del sistema como en los reportes PDF.
- **Permisos Granulares**: Se implementaron dos nuevos niveles de seguridad: `payments.void_today` (para anular pagos del mismo día) y `payments.void_anytime` (para anular cualquier fecha).
- **Reversión Automática**: El sistema ahora restaura automáticamente los saldos en los registros de Zelle y Banco vinculados al anular un pago, y devuelve la factura al estado "Crédito" si estaba totalmente pagada.

### Fixed
- **Integridad de Base de Datos**: Se actualizó la columna `status` en la tabla de pagos para soportar el nuevo estado `voided`, evitando errores de truncado de datos.
- **Traducciones**: Se agregaron etiquetas amigables en español para los nuevos permisos en el módulo de asignación.

## [1.8.89] - 2026-03-24

### Fixed
- **Módulo de Productos**: Se corrigió el error de persistencia de datos donde al editar múltiples productos consecutivamente, la información del producto anterior permanecía en los campos. Ahora el sistema realiza una limpieza total del estado al cancelar o cambiar de producto.

## [1.8.88] - 2026-03-24

### Added
- **Corte de Caja (Reporte Detallado)**: Rediseño completo del ticket térmico para ser más explícito. Ahora separa claramente las ventas del día de los abonos de créditos recibidos, ambos desglosados por método de pago y moneda.
- **Exportación a PDF (Corte de Caja)**: Nueva funcionalidad para generar un informe oficial en formato A4 (PDF) con tablas detalladas de arqueo de caja y espacios para firmas de supervisión.
- **Vista Previa de Reportes**: Implementación de un modal de previsualización que permite revisar el PDF sin salir del módulo de Corte de Caja.
- **Filtros Inteligentes de Fecha**: El reporte ahora toma por defecto la fecha actual ("Hoy") si no se especifica un rango, facilitando los cortes diarios rápidos.

## [1.8.87] - 2026-03-24

### Fixed
- **Catálogo de Clientes**: Se relajaron las validaciones para que los campos **CC/Nit** y **Billetera** dejen de ser obligatorios, evitando errores de integridad en la base de datos al dejar campos vacíos.
- **Códigos de Descuento (PP/PD)**: Se implementaron valores por defecto automáticos para evitar fallos cuando el usuario no define un código manual.

## [1.8.86] - 2026-03-24

### Fixed
- **Sincronización de Filtros en Reportes**: Se alinearon los criterios de búsqueda (vendedor y fecha) de la "Hoja de Liquidación" con la "Relación de Despacho", garantizando que el PDF muestre exactamente lo mismo que se visualiza en pantalla.

## [1.8.85] - 2026-03-23

### Added
- **Hoja de Liquidación de Ruta (PDF)**: Nuevo reporte para conciliación administrativa al finalizar recorridos. Incluye desglose por factura, cobranza declarada y novedades.
- **Selector de Choferes (Modo Supervisión)**: Los administradores ahora pueden alternar entre rutas de diferentes choferes desde el dashboard centralizado.
- **Permiso `driver_monitoring`**: Control de acceso granular para la visualización de rutas de terceros.
- **Inicio Masivo de Rutas**: Botón "Iniciar Todas las Rutas" para que el chofer active todos sus pedidos pendientes con un solo clic.

### Fixed
- **Error Intermitente al Guardar Novedades**: Se corrigió el fallo de `Integrity constraint violation (sale_id cannot be null)` mediante validación robusta y estados de carga (`wire:loading`).
- **Prevención de Doble Envío**: Bloqueo del botón guardar para evitar duplicidad de registros y pérdida de estado en modales de cobranza.
- **Validación GPS**: Mejora en la captura de coordenadas al iniciar rutas masivas.

## [1.8.80] - 2026-03-22
### Added
- **Monitoreo Administrativo de Drivers**: Ahora el administrador puede ver el dashboard específico de cualquier chofer haciendo clic en su etiqueta en el mapa o en el reporte de despacho.
- **Vínculos de Seguimiento en Mapa**: Se añadieron enlaces directos en los popups del "Mapa en Vivo" para ver la "Hoja de Ruta" del chofer y el seguimiento individual de cada pedido.
- **Acceso Logístico en Sidebar**: El enlace "MI RUTA" ahora es visible para Administradores y Supervisores bajo el nombre "LOGÍSTICA / RUTAS", facilitando el acceso al monitoreo.

### Changed
- **Arquitectura de Vistas (Livewire 3)**: Se optimizó el renderizado del Mapa y el Dashboard para cumplir estrictamente con el requisito de "Root Element" único de Livewire 3, moviendo los scripts a stacks específicos.
- **Navegación Dinámica**: El dashboard del chofer ahora detecta si está siendo visto por un administrador, mostrando un banner informativo y previniendo la actualización accidental de la ubicación del administrador como si fuera la del chofer.

### Fixed
- **Error Multiple Root Elements**: Se corrigió el fallo crítico de Livewire en el Mapa de Choferes causado por etiquetas HTML mal cerradas y scripts mal posicionados.
- **Data Binding en Dashboard**: Se restauraron propiedades públicas perdidas (tab, sales, historySales) que causaban errores de variable indefinida.



## [1.8.79] - 2026-03-20
### Added
- **Reporte de Despacho (Relación de Despacho)**: Se integró un nuevo reporte detallado bajo el módulo de entregas.
- **Acceso por Módulo**: Se implementó una lógica de visibilidad dinámica en el sidebar, condicionada a la licencia `module_delivery`.

### Changed
- **Diseño de Reportes**: Se unificó la estética del Reporte de Despacho con el diseño premium de "Ventas Diarias", incluyendo agrupaciones por vendedor y secciones de firma (Despacho, Chofer y Recibido).
- **Persistencia de Choferes**: Se optimizó el proceso de carga de usuarios con roles de driver/chofer/repartidor, haciéndolo robusto ante variaciones en los nombres de roles en la base de datos.
- **Ciclo de Vida de Ventas**: Se ajustó la lógica de limpieza pos-venta para preservar la lista de choferes cargada, permitiendo múltiples operaciones consecutivas sin recarga manual.

### Fixed
- **Asignación de Chofer en Venta**: Se corrigió el bug que impedía guardar el `driver_id` en la tabla de ventas al finalizar una factura.
- **Error Spatie RoleDoesNotExist**: Se eliminó la excepción fatal que ocurría cuando un rol de chofer esperado no existía en el sistema.

## [1.8.78] - 2026-03-19
### Added
- **Permiso Forzar Descuento**: Se creó e inyectó un nuevo permiso llamado `payments.force_discounts` que le confiere poderes al usuario para eludir el sistema de control de descuentos por pronto pago o divisa.

### Changed
- **Lógica de Descuentos**: Se habilitó la capacidad a "superusuarios" con el nuevo permiso para activar de forma forzada los switches de descuento en divisas y pronto pago en los módulos de Cuentas por Cobrar y Abonos.
- **Inmutabilidad de la Factura**: Se corrigió el botón de "Actualizar Reglas de Crédito" para que su comportamiento emane una inmutabilidad en la factura: en vez de destruir la configuración y atarla en vivo, ahora crea un nuevo "Snapshot", respetando su valor a futuro si el cliente cambia.

## [1.8.77] - 2026-03-18
### Changed
- **Estética de Reportes**: Se unificaron los criterios de los contadores en el resumen del informe diario. Ahora se leen como "Total Facturas Procesadas" en vez de "Total Transacciones", y el "Total Facturas Eliminadas" ahora indica la cantidad de facturas anuladas y no la sumatoria de sus montos, para una comprensión más limpia.
## [1.8.76] - 2026-03-18
### Changed
- **Reporte de Ventas Diarias**: Se mejoró la visualización de los pagos en divisas (Zelle y Bancos), mostrando el equivalente en dólares de una forma más clara (ej. `(Dólar: $5.04)`). Además, la columna de Bolívares ahora refleja el monto exacto cobrado en moneda nacional en lugar de su conversión a dólares, mejorando la conciliación de caja.
## [1.8.75] - 2026-03-17
### Changed
- **Unificación Estética**: Se aplicaron los nuevos estilos de la "Billetera Virtual" y la disposición de botones (col-4) a todos los módulos de pago, incluyendo Ventas/Abono y Cuentas por Cobrar, para mantener una estética coherente en todo el sistema.

## [1.8.74] - 2026-03-17
### Fixed
- **Estilos en POS**: Se corrigió un error en el que el código CSS de la billetera se mostraba como texto en la parte superior del modal debido a una incompatibilidad con la etiqueta @style.

## [1.8.73] - 2026-03-17
### Changed
- **Interfaz de Pago POS**: Se removió el botón de "Crédito" del modal de pago rápido y se reubicó el botón de "Billetera Virtual" en su lugar.
- **Estética de Billetera**: Se actualizó el color del botón de Billetera a un naranja vibrante y moderno para mejorar la experiencia visual.

## [1.8.72] - 2026-03-17
### Fixed
- **Billetera en Cuentas por Cobrar**: Se habilitó la opción de pago con billetera virtual en el módulo de reportes de cuentas por cobrar, sincronizando el saldo del cliente correctamente.

## [1.8.71] - 2026-03-17
### Fixed
- **Retroactividad de NC**: Se mejoró la lógica de descubrimiento de Notas de Crédito antiguas para que aparezcan en los reportes de días anteriores basándose en su fecha de creación o asociación con facturas pagadas, incluso si no tenían un ID de planilla asignado originalmente.

## [1.8.70] - 2026-03-17
### Added
- **Integración de Notas de Crédito**: Se centralizó la lógica de creación de "Planillas de Cobro" mediante un Trait para asegurar que las Notas de Crédito (NC), manuales o por devolución, se asocien correctamente al reporte diario.
- **Relación de Cobros**: Se mejoró la búsqueda y visualización de Notas de Crédito en el reporte, permitiendo ver el historial completo de transacciones (pagos y NC) por cliente en el PDF.

## [1.8.69] - 2026-03-17
### Fixed
- **Modal de Pago**: Corregido error crítico de visualización en el que los formularios de Zelle y Banco no cargaban correctamente debido a un error de estructura HTML.

## [1.8.68] - 2026-03-17
### Fixed
- **Billetera Virtual en Abonos**: Corregido error de base de datos (SQL 1265) al intentar pagar con la billetera virtual en el módulo de abonos parciales.
- **Billetera Virtual en Abonos**: Se habilitó la visibilidad del botón de "Billetera" en el modal de abonos y se sincronizó el saldo del cliente correctamente.
- **Billetera Virtual**: Se corrigió la lógica de deducción de saldo para asegurar que se reste el monto equivalente en moneda principal, manteniendo consistencia con el punto de venta.

## [1.8.67] - 2026-03-14
### Added
- **Identificación del Operador**: Se añadió el nombre del operador que genera el reporte en el encabezado, debajo del periodo y la moneda de referencia.

## [1.8.66] - 2026-03-14
### Fixed
- **Estética del Reporte**: Se reforzaron las líneas divisorias en los totales de los cuadros de resumen (ahora en color negro sólido).
- **Firmas**: Se reorganizó la sección de firmas para que aparezcan una al lado de la otra, optimizando el espacio al final del reporte.

## [1.8.65] - 2026-03-14
### Added
- **Trazabilidad en Devoluciones**: Se añadieron las columnas "Solicitante", "Aprobador" y "Motivo" a la tabla de Notas de Crédito, garantizando el mismo nivel de control que en las facturas eliminadas.
- **Resumen de Eliminaciones**: Se reemplazó el campo "Total Exento" por el "Total de Facturas Eliminadas" en el resumen general del reporte para una mejor visibilidad de las anulaciones.
- **Base de Datos Atualizada**: Nueva migración para registrar quién solicita y quién aprueba cada devolución de mercancía.

## [1.8.64] - 2026-03-14
### Fixed
- **Detalle de Pagos en PDF**: Corregido error que impedía visualizar el banco, referencia y tasa en la descripción de las ventas.
- **Optimización de Espacio**: Se ajustaron los anchos de las columnas para otorgar más espacio a la descripción del cliente y evitar recortes innecesarios.

## [1.8.63] - 2026-03-14
### Changed
- **Optimización de Reporte de Ventas Diarias**: Se rediseñó el layout del PDF para hacerlo más compacto, permitiendo ahorrar papel sin perder legibilidad.
- **Detalles de Pago Transparente**: Ahora las descripciones de las ventas incluyen el banco y el número de referencia para pagos por Zelle o transferencia bancaria.
### Added
- **Control de Facturas Eliminadas**: Se incorporó una nueva sección que lista las facturas anuladas del día, detallando quién solicitó la eliminación, quién la aprobó y el motivo.
- **Sección de Devoluciones**: Añadida la tabla de Notas de Crédito (Devoluciones) directamente en el reporte diario.
- **Previsualización en Vivo**: Implementado un botón de "Previsualizar" que abre el reporte en una ventana modal antes de descargarlo.
 
## [1.8.62] - 2026-03-14
### Added
- **Visor de PDF Integrado (Modal)**: Se implementó un previsualizador de PDF que se abre en una ventana modal dentro del sistema, permitiendo revisar la Relación de Cobros antes de imprimir o descargar.

## [1.8.61] - 2026-03-14
### Changed
- **Ajustes de Legibilidad en Reporte**: Se aumentó el tamaño de fuente de los registros a 7pt y se incrementó el espacio entre las firmas de "Entregado" y "Recibido" para mayor claridad.

## [1.8.60] - 2026-03-14
### Fixed
- **Estética de Reporte PDF**: Se reforzó la línea divisoria del "Total Ingreso" en el resumen por categoría para asegurar su visibilidad en la generación del PDF.

## [1.8.59] - 2026-03-14
### Changed
- **Rediseño de Pie de Reporte**: Se reorganizó el pie de página de la Relación de Cobros en 3 columnas (Resumen por Categoría, Detalle por Moneda y Firmas) para evitar solapamientos y ahorrar espacio.
- **Doble Firma**: Se añadieron los bloques de firma para "Entregado por (Operador)" y "Recibido por".
- **Optimización de Papel**: Se redujo el tamaño de fuente en los registros de pago de 7pt a 6pt para compactar la información.

## [1.8.58] - 2026-03-14
### Added
- **Tag Personalizable para Pago Divisa**: Ahora puedes configurar el código (Tag) para el descuento por pago en USD (ej. "PD") directamente en la configuración global, por cliente o por vendedor.

## [1.8.57] - 2026-03-14
### Added
- **Configuración de Tags de Descuento**: Se añadió un nuevo campo "Código" (Tag) en la configuración de reglas de descuento (Global, por Cliente y por Vendedor).
- **Identificación de Descuentos en Reportes**: El reporte de Relación de Cobros ahora muestra el código específico del descuento aplicado (ej: "Desc. PP" para Pronto Pago, "Desc. PD" para Pago Divisa) en lugar de un genérico "Desc.".

### Technical
- **Base de Datos**: Nueva columna `tag` en `credit_discount_rules` y `discount_tag` en `payments`.
- **Servicios**: Actualización de `CreditConfigService` para calcular y propagar el tag del descuento aplicado.

## [1.8.56] - 2026-03-14
### Fixed
- **Corrección de Encabezado de Fechas**: Se ajustó el formato de las etiquetas de fecha (Desde/Hasta) y la información de cabecera (Fecha/Hora/Pág) para que coincidan exactamente con la estética del diseño de referencia (formato de dos puntos con espacio y sin negritas).
- **Lógica de Fechas en PDF**: Se implementó una lógica de respaldo para que, en caso de no haber un filtro activo, el reporte muestre automáticamente la fecha de apertura de la planilla de cobro.

## [1.8.55] - 2026-03-13
### Changed
- **Optimización de Reporte PDF**:
    - **Ahorro de Papel**: Se redujo el tamaño de la fuente en las tablas de registros y se ajustaron los márgenes para permitir mayor contenido por página.
    - **Encabezado Detallado**: Añadida la sección de filtros aplicados (Activo, Monedas, Fecha Desde y Fecha Hasta) para coincidir con los estándares de auditoría.
    - **Contador de Movimientos**: Ahora se muestra el "Total Transacciones" al final de cada cliente, facilitando el conteo rápido de operaciones procesadas.

## [1.8.54] - 2026-03-13
### Changed
- **Refinamiento de Relación de Cobros**:
    - **Número de Documento**: Ahora se muestra el Número de Factura (o Nota de Crédito) directamente en la columna principal, facilitando la identificación inmediata de qué se está cobrando.
    - **Cálculo de Días Vencidos**: La columna de días ha sido reprogramada para mostrar los días de mora. (Ejemplo: +10 si está vencida, -2 si se pagó antes de tiempo, 0 si se pagó el día exacto).
    - **Simplificación de Interfaz**: Se eliminaron las columnas de "Adelantos" y "Retenciones" para dar más espacio al contenido relevante, ya que el sistema no utiliza estos métodos.

### Fixed
- **Trazabilidad**: Removida la duplicidad del número de factura en la descripción para una lectura más limpia del reporte.

## [1.8.53] - 2026-03-13
### Added
- **Nueva Relación de Cobros (REPORTE PREMIUM)**: Implementado un nuevo diseño de reporte detallado para planillas de cobro que agrupa los pagos por cliente, facilitando la auditoría y conciliación de saldos recaudados.
- **Detalle por Cliente**: Cada sección del reporte incluye el RIF/CI del cliente, subtotal financiero y un desglose de facturas canceladas con sus respectivos tipos de pago.
- **Integración de Notas de Crédito**: Las Notas de Crédito (ajustes manuales) ahora se guardan vinculadas a la planilla de cobro del día, permitiendo su visualización en el reporte como parte de la gestión de deudas del operador.
- **Resumen Multimoneda y Bancos**: El reporte incluye un nuevo cuadro de resumen al final que desglosa los totales por cada moneda recolectada (USD, VED, COP) y por cada banco configurado (Bancolombia, Banesco, Zelle, etc.) para coincidir perfectamente con la cabecera del documento.

### Changed
- **Lógica de Auditoría**: Añadida una columna de "DÍAS" que calcula automáticamente el tiempo transcurrido desde la emisión de la factura hasta el pago realizado, permitiendo detectar de forma visual los pagos en mora.
- **Identificación de Banco**: Se mejoraron las descripciones automáticas en el reporte para incluir el nombre del banco y el número de referencia en transferencias, y el nombre del pagador en reportes de Zelle.

### Fixed
- **Trazabilidad**: Corregida la falta de vinculación de las Notas de Crédito manuales a las planillas de cobro, asegurando que todos los movimientos contables aparezcan en los cierres de caja correspondientes.

## [1.8.52] - 2026-03-13
### Added
- **Notas de Crédito Manuales**: Implementada la funcionalidad de "Ajustes de Saldo" que permite realizar descuentos de deuda manuales directamente desde el modal de abonos sin necesidad de devolver productos físicos.
- **Multimoneda en Ajustes**: Selector de moneda integrado en la nota de crédito manual con cálculo automático de equivalencia hacia la moneda de la deuda.
- **Seguridad**: Nuevo permiso `payments.create_credit_note` para restringir quién puede aplicar ajustes manuales a las facturas.

### Changed
- **Interfaz de Pagos**: Rediseñado el resumen de descuentos para mayor claridad. Ahora solo se muestran las filas de descuento que están activamente seleccionadas (Pronto Pago o Divisa), eliminando el texto tachado y mejorando el enfoque visual.
- **Iconografía**: Actualizado el sistema de iconos en reportes y listados. Los ajustes manuales ahora se identifican con un icono de factura naranja para diferenciarlos visualmente de las devoluciones de mercancía (amarillo).
- **PDF de Notas**: Adaptado el generador de PDF para imprimir descripciones personalizadas de "AJUSTE DE SALDO" cuando se detecta un retorno de tipo manual.

### Fixed
- **Base de Datos**: Corregido error de truncado en la tabla `sale_returns` al permitir el tipo de retorno 'manual' mediante una nueva migración.

## [1.8.51] - 2026-03-13
### Added
- **Abonos Parciales**: Implementada paginación en el listado de ventas pendientes por abonar para mejorar el rendimiento.
- **Vendedores**: Agregada columna "Vendedor" con distintivo de color identificador en el modal de abonos.
- **Búsqueda Avanzada**: Habilitada la búsqueda por nombre de vendedor en el modal de abonos.
- **Notas de Crédito**: Agregada visualización de iconos de Notas de Crédito (devoluciones) con acceso directo al PDF en el modal de abonos y reportes de ventas/cuentas por cobrar.

### Fixed
- **Estabilidad**: Corregido error de variable indefinida `$sales` al filtrar ventas en el componente de pagos parciales.

## [1.8.50] - 2026-03-12
### Fixed
- **Fechas de Pago**: Corregida la lógica de registro de fechas en pagos y abonos. Ahora el sistema prioriza y utiliza la fecha seleccionada en el formulario (fecha del voucher) en lugar de la fecha actual del sistema.
- **Historial de Pagos**: Actualizada la visualización del historial para mostrar la fecha real del depósito/transferencia, facilitando la conciliación bancaria.
- **Lógica de Descuento**: La validación de "pago a tiempo" para descuentos ahora utiliza la fecha del pago seleccionada por el usuario, evitando penalizar a clientes por registros tardíos del personal administrativo.
- **UI de Pagos**: Se añadió el campo de "Fecha de Pago" a la sección de bancos estándar para permitir registros históricos precisos.
- **Permisos**: Corregida la visibilidad y traducción del permiso "Actualizar Reglas de Crédito" en el módulo de asignación de permisos.
- **Edición de Pagos**: Asegurada la consistencia de fechas al editar pagos pendientes, manteniendo la fecha del comprobante original.

## [1.8.49] - 2026-03-12
### Fixed
- **Descuentos**: Corregida la visibilidad de las alertas de descuento en el módulo de abonos. Ahora las opciones de "Pronto Pago" y "Pago en Divisa" aparecen de inmediato al abrir el modal, permitiendo su selección manual antes de registrar el primer pago.
- **Descuentos**: Implementada exclusividad mutua entre descuentos. Activar uno desactiva automáticamente el otro para evitar errores de cálculo.
- **Ventas Foráneas**: Optimizada la detección de ventas foráneas. Ahora se marcan correctamente aunque no se seleccione aplicar comisiones al momento de la venta, siempre que haya un vendedor asignado.
- **Compatibilidad**: Expandida la disponibilidad del descuento por divisa para ventas existentes basadas en la configuración del cliente, incluso si no fueron marcadas originalmente como foráneas.

## [1.8.48] - 2026-03-12
### Fixed
- **Cuentas por Cobrar**: Corregido un error de servidor (Hotfix) al intentar abrir el modal de pago. El error era causado por variables no definidas en la última actualización de cálculo de devoluciones.

## [1.8.47] - 2026-03-12
### Fixed
- **Descuentos y Devoluciones**: Corregido el cálculo de descuentos (Pronto Pago y Pago en Divisa) cuando existen devoluciones parciales. Ahora el sistema calcula el beneficio sobre el monto neto real de la factura (Total original menos productos devueltos) en lugar de usar siempre el monto original de venta. Esto aplica tanto en el módulo de Abonos como en el de Cuentas por Cobrar.

## [1.8.46] - 2026-03-12
### Fixed
- **Pagos**: Mejora integral en la eliminación de abonos. Ahora el sistema elimina completamente los registros de **Zelle** y **Transferencias Bancarias** vinculados a un pago si este era el único que los utilizaba. Esto evita que queden referencias "fantasmas" marcadas como usadas que bloquean nuevos reportes del mismo pago.
- **Validaciones**: Se ajustó la precisión decimal al restaurar saldos para garantizar que el estatus vuelva a "Unused" (No usado) correctamente.

## [1.8.45] - 2026-03-12
### Fixed
- **Pagos**: Corregido el error de integridad (SQL 1451) al eliminar abonos que comparten una misma transferencia o depósito bancario. Ahora el sistema reintegra el saldo al registro bancario original y solo lo elimina si no existen otros pagos vinculados a él.
- **Pagos**: Se aseguró el orden correcto de eliminación (Pago primero, Registro Bancario después) en todos los módulos de gestión.

## [1.8.44] - 2026-03-12
### Fixed
- **Filtros de Ordenes**: Corregido el filtro de "Vendedor" para que sea omnicanal. Ahora, al seleccionar un usuario en el desplegable, se filtran las órdenes donde este sea el **Vendedor Responsable** del cliente O el **Operador** que creó la orden.
- **Búsqueda**: Reforzada la búsqueda por texto para incluir simultáneamente el nombre del Operador, Vendedor y Cliente.

## [1.8.43] - 2026-03-12
### Added
- **Ordenes de Venta**: Se separaron las figuras de "Vendedor" y "Operador" en la lista de órdenes procesables. 
    - **Vendedor**: Ahora muestra el vendedor asignado al cliente de la orden, incluyendo su color identificador.
    - **Operador**: Nueva columna que indica el usuario del sistema que creó la orden.
- **Filtros**: El filtro de búsqueda por vendedor ahora actúa sobre el vendedor asignado al cliente, facilitando el seguimiento de carteras de clientes por vendedor.

## [1.8.42] - 2026-03-12
### Changed
- **Navegación**: Se agregaron etiquetas descriptivas (tooltips) a los iconos de notificaciones en la barra superior ("Cuentas por Pagar", "Créditos Vencidos / Cuentas por Cobrar" y "Comisiones Pendientes") para hacerlos más intuitivos para el usuario.

## [1.8.40] - 2026-03-11
### Fixed
- **Configuración de Crédito**: Solucionado un error ("MethodNotFoundException") que ocurría si intentabas dar click al botón de "Actualizar Reglas de Crédito" desde la página de Cuentas por Cobrar en lugar de dentro de una venta específica.

## [1.8.39] - 2026-03-11
### Changed
- **Catálogos**: Se incrementó el límite de caracteres en el nombre de los Clientes, Usuarios (Vendedores) y Proveedores. Anteriormente permitían máximo 45, 85 y 50 caracteres respectivamente. Ahora todos permiten registrar nombres de hasta 200 caracteres de longitud.

## [1.8.38] - 2026-03-11
### Fixed
- **Abonos**: Corregido el error de eliminación ("Integrity Constraint Violation 1451") que ocurría al intentar borrar un depósito bancario o transferencia pendiente (BankRecord) eliminando primero el recibo de pago y luego el registro del banco.

## [1.8.37] - 2026-03-11
### Fixed
- **Configuración de Crédito**: Corregido un error técnico (TypeError) que ocurría al actualizar las reglas de crédito desde el Historial de Pagos, permitiendo que la tabla de historial se refresque correctamente.

## [1.8.36] - 2026-03-11
### Added
- **Configuración de Crédito**: Añadida la opción "Actualizar Reglas de Crédito" en el Historial de Pagos. Permite a los administradores forzar que una factura pendiente herede la configuración de crédito más reciente del cliente, resolviendo casos donde los descuentos por "Pronto Pago" no aplicaban debido a reglas antiguas guardadas en la factura.
- **Permisos**: Nuevo permiso `sales.reset_credit_snapshot` para controlar quién puede actualizar las reglas de crédito de una factura.

## [1.8.35] - 2026-03-11
### Fixed
- **Pagos**: Corregido bug donde la opción de "Pago en Divisa" aparecía incorrectamente al editar un pago aunque la venta tuviera abonos previos en Bolívares.
- **Pronto Pago**: Actualizado el cálculo de días para basarse en la fecha real del depósito bancario (`payment_date`) en lugar de la fecha en que se registró en el sistema, asegurando que el cliente no pierda su descuento por demoras administrativas.
- **Reporte de Ventas Diarias**: Corregido el cálculo del total neto y la generación del PDF para usar de forma precisa los montos equivalentes en dólares.

## [1.8.34] - 2026-03-10
### Fixed
- **Comisiones**: Filtradas y ocultadas todas las ventas que tengan una comisión aplicada explícita de `0%`. Ya no aparecerán ni en el Módulo de Comisiones ni en el Reporte de Comisiones, incluso si el vendedor tiene un porcentaje predeterminado en su perfil.

## [1.8.33] - 2026-03-10
### Added
- **Búsqueda Global**: Implementada la búsqueda por Número de Factura en los módulos Abonos a Cuenta, Reporte de Ventas, Cuentas por Cobrar, Relación de Pagos y Comisiones. 
- **Búsqueda Global**: Activada la tecla `Enter` como disparador principal para ejecutar búsquedas sin necesidad de click en los reportes mencionados.

### Fixed
- **Filtro de Vendedores**: Modificadas las consultas de la barra superior en todos los reportes (Ventas Diarias, Cuentas por Cobrar, Comisiones, Relación de Pagos y Reporte de Ventas) para incluir correctamente al rol "Vendedor foraneo" junto con el "Vendedor" regular.
- **Estatus Visuales**: Corregido un fallo en el componente de *Cuentas por Cobrar* donde las ventas "pendientes" compartían el característico color rojo de las "retornadas". Ahora las retornos conservan el color rojo (`badge-danger`), mientras que las pendientes usan un amarillo informativo (`badge-warning`).

## [1.8.32] - 2026-03-10
### Added
- **Auditoría de Pagos**: Añadido campo "Comentario de Modificación" opcional al editar un pago pendiente. El comentario se guarda en base de datos y se muestra en azul en el historial general para justificar correcciones.
- **Gestión de Descuentos**: Los administradores ahora pueden visualizar y alternar en tiempo real los descuentos por "Pronto Pago" y "Pago en Divisa" dentro del modal de edición de pagos.
- **Contexto de Deuda**: Incorporado un nuevo panel superior en el modal de Edición de Pagos que resume "Monto Venta", "Abonado" y "Deuda Actual".
- **Calculador Predictivo**: Añadido indicador dinámico "Saldo Restante Posterior a este Abono" que evalúa en vivo el equivalente en dólares del pago editado más los descuentos activados.

### Changed
- **Edición de Pagos**: La "Tasa de Cambio" ahora es editable por administradores, recalculando instantáneamente el equivalente en dólares antes de aprobar o denegar.
- **Vendedores Foráneos**: Optimizada la vista de Abonos a Cuenta para mostrar dinámicamente el badge de "Pago por aprobar" y garantizar que el botón "Ver Historial" esté siempre visible si hay depósitos en proceso.

## [1.8.31] - 2026-03-05
### Fixed
- **Abonos:** Excluidos los pagos con estado 'PENDIENTE' o 'RECHAZADO' del cálculo de la "Deuda Actual" en las tablas principales de Cuentas por Cobrar y Abonos.
- **Abonos:** Actualizado el modal "Historial de Pagos" para desglosar el "Total Aprobado" y "Total Pendiente".



## [1.8.30] - 2026-03-04
### Fixed
- **Cart Stability**: Fixed cart reordering when updating quantities. Cart items now stay in their appropriate position.
- **Price Groups**: Fixed price group synchronization when modifying item quantities. Sibling items in a price group accurately update their discounted prices simultaneously without requiring a page reload.
- **PDF Templates**: Fixed `invoice-credit-short.blade.php` and `invoice-paid-short.blade.php` PDF templates so the `Vendedor:` label isn't incorrectly grouped line-wrapped with the due date.

## [1.8.29] - 2026-02-25
### Added
- **Price Groups**: Implemented a new feature that allows grouping multiple products for volume pricing. When products in the same group are added to the cart, their quantities are summed to determine which volume discount tier applies to all members of the group.
- **Price Groups UI**: Added a new "Grupos de Precio" management screen under Catalogos and a dropdown in the Product Edit form (Price Rules tab) to assign products to groups.

### Fixed
- **Price Tiers Persistence**: Resolved a bug where price tiers added to a product would disappear after clicking "Update Product". The system now persists tiers directly to the database and reloads them correctly during Livewire re-hydration.
- **Auto-Recalculate Group Prices**: Fixed an issue where changing the quantity of one product in a group wouldn't immediately update the prices of other group members in the cart. The system now automatically recalculates and updates the entire group whenever any member's quantity changes.
- **Cart Order Stability**: Fixed a bug where updating a product's quantity would cause it to jump to the last position in the cart list. Items now maintain their original order during updates.

## [1.8.28] - 2026-02-25
### Fixed
- **Order PDF**: Fixed an issue where the Customer's specific Credit Configuration (Base discounts, Early Payment Rules, Credit Days) was completely missing from the PDF for Pending and Processed Orders due to a trait naming collision.
- **Order PDF**: Fixed missing currency decimals on Pending and Processed Orders to dynamically read from the system's global `getDecimalPlaces` setting instead of rounding to `0`.
- **Advanced Payments (Zelle/Bank)**: Resolved an "Access Denied / Módulo de pagos avanzados no activo" bug occurring for users with Premium licenses. The payment module incorrectly validated `session('tenant.modules')` instead of the globally updated `config('tenant.modules')`.

## [1.8.27] - 2026-02-25
### Added
- **Global Customer Search**: Added support for searching customers by their Taxpayer ID (RIF/Cedula) across all main modules including Point of Sale (POS), Sales Report, Daily Sales Report, and Accounts Receivable. The Taxpayer ID is now also displayed in the search result dropdown alongside the customer name.

## [1.8.26] - 2026-02-25
### Fixed
- **Sales Module**:
  - **Zero Price Bug**: Fixed a bug where `sale_price` and `regular_price` were saving as `0.00` in the database. The system now correctly maps the `base_price` from the cart.
  - **Freight Calculation**: Fixed an issue where the seller's generic freight percentage was incorrectly overriding the customer's specific prioritized freight percentage during sale finalization.
- **Products Module**:
  - **Checkbox State Preservation**: Fixed an issue in the Product Edit Form where the checkboxes "Venta por Peso/Separado" (`is_variable_quantity`) and "Permite Cantidades Decimales" (`allow_decimal`) would automatically uncheck themselves upon saving if the advanced products module was not active.

## [1.8.25] - 2026-02-23
### Fixed
- **Auto-Updater**:
  - **NSSM Locked File Error**: Fixed `copy(...nssm.exe) failed to open stream` error during system updates. The `nssm/` binary directory, `whatsapp-api/` Node service, and `instalar_servicios.bat` are now excluded from GitHub release ZIPs via `.gitattributes export-ignore`. These files are not needed during an app update and were causing Windows file lock errors because NSSM services were running.

## [1.8.24] - 2026-02-23
### Fixed
- **Users Module**:
  - **Livewire Binding Error**: Fixed `CannotBindToModelDataWithoutValidationRuleException` for `user.phone`, `user.taxpayer_id`, and `user.address`. The fields were defined in the `Store()` method's local `$rules` variable but were missing from the class-level `$rules` property, which Livewire requires to allow `wire:model` binding on Eloquent model fields.

## [1.8.23] - 2026-02-23
### Fixed
- **Service Installer**:
  - **Queue Worker Stuck**: Fixed `instalar_servicios.bat` where the `JSPOS_Queue_Worker` Windows service would fail silently because `php` was referenced as a generic command. NSSM-managed services don't inherit Laragon's PATH, so `php.exe` was never found. The script now auto-detects the full path to `php.exe` using `where php` at install-time and passes the absolute path to NSSM.
  - **Clear Error Message**: Added a user-friendly error message if PHP is not found in the PATH, with a suggestion to run the script from the Laragon Terminal.

## [1.8.22] - 2026-02-23
### Added
- **User Management**:
  - **Contact Information**: Added `phone`, `taxpayer_id` (RIF/CI), and `address` fields to the User profile to improve data completeness and support staff.
  - **Database Migration**: Added a new migration to safely implement these fields without data loss.

### Changed
- **Roles & Permissions**:
  - **SaaS Consistency**: The "Comisiones" and "Config. Crédito" tabs in the user profile are now strictly hidden if the active license plan does not include the `module_commissions` or `module_credits` modules, resulting in a cleaner interface for Basic plans.
  - **Seller Role Availability**: Fixed a bug where the "Vendedor" and "Vendedor Foraneo" roles were incorrectly disabled when advanced modules were off. These roles are now always available for basic cashier/sales operations.
- **Customers Module**:
  - **Clean UI**: Removed the deprecated "Tipo" (Type) field from the customer creation and edit forms, as it was no longer used by the system.

### Fixed
- **WhatsApp Integration**:
  - **Fallback Logic Verification**: Ensured the system correctly falls back to using the Seller's phone number for notifications when the Customer does not have a registered phone number.

## [1.8.21] - 2026-02-20
- **Products Module**:
  - **Search Relevance**: Re-wrote the search algorithm to prioritize exact SKU matches and Name matches before considering Category matches, ensuring accurate results (e.g., searching "Tenedor" no longer shows "Contenedores" first).
  - **Pagination Bug**: Fixed a persistent bug where clicking on page 2 or beyond while a search filter was active would instantly bounce the user back to page 1. Pagination now behaves correctly during filtered searches.
- **System Internals**: Addressed a risk where clients might forget to manually run database commands after updating by enforcing auto-migrations in the internal release documentation.

## [1.8.20] - 2026-02-20
### Added
- **Payments**:
  - **Consultation Modules**: Implemented full support for viewing "Cash Sales" (Ventas de Contado) within the Zelle and Bank consultation screens.
  - **Database Links**: Added `salePaymentDetails` relationship to `ZelleRecord` and `BankRecord` models.
  - **PDF Reports**: Updated Zelle and Bank exported PDF reports to include cash sale usages alongside regular credit payments.
  - **UI/UX**: Cash sales are now clearly distinguished with a green "(Contado)" label in the UI, separating them from standard "(Abono)" credits.



## [1.8.19] - 2026-02-19
### Fixed
- **Permissions**:
  - **Super Admin Paradox**: Resolved an issue where Super Admins were blocked from certain UI actions (like changing invoice currency) because they implicitly held conflicting permissions. Logic updated to rely on positive permissions only.
  - **Legacy Cleanup**: Migrated old permissions (`aprobar_cargos`, `metodos de pago`) to new, standardized keys (`adjustments.approve_cargo`, `payments.methods`).
  - **Orphans**: Removed unused legacy permissions (`compras`, `clientes`, etc.) to clean up the assignment UI.
  - **Auto-Migration**: Added a DB migration to legally execute the permission cleanup and reassignment on client update.

## [1.8.17] - 2026-02-17
### Fixed
- **Permissions**:
  - **Missing Permissions**: Resolved an issue where some permissions (e.g., `system.is_foreign_seller`, `sales.switch_warehouse`, `cash_register.bypass`) were missing in some environments because they were defined in separate seeders.
  - **Consolidation**: Consolidated all 125 system permissions into the main `CreatePermissionsSeeder` to ensure consistency.
  - **Auto-Repair**: Included a migration that automatically runs the permission seeder during the update process to restore any missing permissions on existing installations.

## [1.8.17] - 2026-02-17
### Fixed
- **Permissions**: 
    - Fixed translation issue preventing Spanish names and icons from appearing (replaced dot notation with underscores).
    - Updated `CreatePermissionsSeeder` to include the full list of 125 permissions, ensuring synchronization with client environments.

## [1.8.16] - 2026-02-17
### Fixed
- **Customer Import**:
  - **Data Truncated Error**: Resolved an issue where importing customers with type "Minorista" failed due to database enum restrictions. Added "Minorista" to `customers` table and manual creation form.
- **Backup System**:
  - **Mysqldump Path**: Made `mysqldump` binary path configurable via `.env` (`DB_DUMP_PATH`) to support different development environments and prevent "Path not found" errors on client machines.

## [1.8.15] - 2026-02-17
### Added
- **Customers**:
  - **Bulk Import**: Added `CustomerImport` module allowing bulk upload of customers via Excel/CSV.
  - **Intelligent Mapping**: System automatically detects columns like Name, Phone, Email, TaxID, etc.
  - **Seller Assignment**: Can assign sellers by name from the Excel file; creates new seller users if they don't exist.
- **UX/UI**:
  - **Error 419**: Created a custom "Session Expired" page that automatically redirects to Login after 3 seconds, improving user experience.

## [1.8.14] - 2026-02-17
### Changed
- **Reports**:
  - **Sale Detail**: Improved detailed view of sales, specifically handling of multi-currency payments and pending approvals.
  - **Payment Logic**: Adjustments to `PaymentComponent` and `PartialPayment` for better consistency.
- **Sales**:
  - **Refinements**: General improvements and fixes in the Sales module and product management.
  - **Data Handling**: Updates to `DataController` and `PermissionSeeder`.
### Added
- **PDF**:
  - **Customer Debt**: Added new template `customer-debt.blade.php`.

## [1.8.13] - 2026-02-16
### Added
- **Sales**:
  - **Currency Persistence**:
    - **Session & Orders**: The selected "Invoice Currency" is now remembered across page reloads and correctly saved/restored when parking (pending) and retrieving orders.
    - **Database**: Updated `Order` model to support `invoice_currency_id`.
  - **Total Display**:
    - **Contextual Total**: The main "Total" amount now dynamically displays in the *selected currency* (e.g., Bolívares), matching the user's preference.
    - **Reference Values**: Added a summary section below the total showing equivalents in other currencies (USD, COP).
    - **USD/BCV**: Explicitly added "USD/BCV" reference calculation (Total VED / BCV Rate) in red for easier verification during payment.
  - **Visuals**:
    - **Product Grid/List**: When Bolívares is the active currency, prices in USD are now highlighted as "USD/BCV" in red to indicate they are calculated at the configured rate.

## [1.8.12] - 2026-02-14
### Changed
- **Invoices**:
  - **Visual Unification**: Aligned "Payment Conditions" and "Disclaimer" blocks in `invoice-credit-short`, `invoice-paid-short`, and `invoice-order-pending` templates to match the width and style of the "Amount in Words" block.
  - **Formatting**: Removed extra spacing and standardized borders for a cleaner, more professional look.


## [1.8.11] - 2026-02-12
### Added
- **Foreign Seller Payments**:
  - **Shared Cash Register**: Implemented logic to strictly exclude Foreign Sellers from the "Shared Cash Register" pool. Their sales now correctly remain "Pending" until approved.
  - **Payment Approval**: Updated `PartialPayment` to require an open cash register (Personal or Shared) when approving a payment, ensuring strict financial reconciliation.
  - **Date Refinement**:
    - **Commissions**: "On Time" calculation now uses the *actual transaction date* (Bank/Zelle date) provided by the seller, not the system approval date.
    - **Reporting**: Approved payments are now assigned to the *approver's* daily Collection Sheet, ensuring they appear in the day's Cash Report (Cierre de Caja).
  - **Reports**: Updated "Relación de Cobro General" to strictly filter out Pending/Rejected payments.

## [1.8.10] - 2026-02-12
### Added
- **Configurable Sales View**:
  - Implemented a toggle between **Grid View** (Large Images) and **List View** (Compact) for product search results.
  - Added global default configuration in *Settings > Sales*.
  - Added individual user override in *Users > Edit Profile*.
- **Freight Logic**:
  - Decoupled "Apply Freight" and "Breakdown Freight" toggles from Seller Commissions. Now they can be used even if the customer has no specific seller assigned (reads from Product settings).
  - Kept "Apply Commissions" locked to Seller Config for security.

## [1.8.9] - 2026-02-10
### Fixed
- **Freight Calculation**:
  - **Breakdown Logic**: Fixed incorrect compounding of freight when "Breakdown Freight" (Desglosar Flete) is enabled.
  - **Commission Calculation**: Resolved issues where commissions were being calculated on inflated prices.
  - **UX/UI**: Added security lock to Commission/Freight switches when no customer is selected to prevent user error.

## [1.8.8] - 2026-02-06
### Added
- **Global Exchange Rates System:**
    - **Configuration:** Added ability to set global reference rates for BCV and Binance in System Settings.
    - **History Tracking:** Implemented automated history logging for every rate change.
    - **Reactive Lookup (Smart Rates):**
        - **Cash Payments (VED):** Added "Payment Date" field. Selecting a date automatically fetches the historical rate valid for that specific day.
        - **Bank Payments (VED):** Bank payments in Bolívares now also support historical rate lookup based on the transaction date.
    - **Custom Rate Override:** Users can manually override the suggested historical rate if necessary.

## [1.8.7] - 2026-01-30
### Added
- **Sale Deletion Workflow:**
    - **Approval Process:** Implemented a secure Request-Approval workflow for deleting sales. Operators request deletion with a reason; Supervisors (Admin/Owner) approve or reject.
    - **Financial Integrity:** Pending deletions remain valid in financial reports until explicitly approved. Approval triggers strict cleanup of payments, Zelle/Bank records, and inventory.
    - **Notifications:** Automated email notifications to supervisors when a deletion is requested.
    - **UI:** Added visual indicators (yellow row, "Solicitud Borrado" badge) for sales pending deletion.
- **Login Page Customization:**
    - **Dynamic Branding:** Displays the configured "Business Name" and System Logo (Shopping Cart) on the login screen.
    - **Dynamic Version:** Shows the actual system version (from `version.txt`) instead of a hardcoded value.

## v1.8.6 - 2026-01-29
### Added
- **License Renewal System:**
    - Interactive modal in header to check expiration and renew license.
    - Permanent "License" option in the User Profile menu.
    - Automated email notifications for upcoming expiration (requires configuration in Settings).
    - Email request feature for license renewal directly from the application.
- `license:check-expiration` console command for automated monitoring.

## [1.8.5] - 2026-01-29

### Added
- **Reports**:
  - **Payment Relationship**: Added "Commissions to Pay" table in the Detailed View, mirroring the PDF layout for easier verification of commission amounts.

### Fixed
- **PDF Report**:
  - **Payment Details**: Resolved an issue where the "Payment Details" row appeared empty for Cash payments. It now conditionally renders only for Zelle, Bank Transfer, or Deposit payments.

## [1.8.4] - 2026-01-29

### Fixed
- **Reports**:
  - **Payment Relationship**: Resolved an issue where "Abonos" (Partial Payments) were not appearing in the Collection Sheet report.
- **Payments**:
  - **Collection Sheet**: Fixed logic in `PartialPayment` to automatically assign or create a daily Collection Sheet when registering a partial payment, ensuring proper tracking and reporting.

## [1.8.3] - 2026-01-29

### Fixed
- **Discounts**:
  - **Mutual Exclusivity**: Enforced strict exclusivity between "USD Discount" and "Early Payment Discount".
  - **Mixed Payments**: Fixed logic so that ANY payment in Bolívares (VED/VES) automatically invalidates the USD Discount and falls back to Early Payment rules.
  - **UI Persistence**: Fixed bugs where checkboxes would re-enable themselves automatically or disappear incorrectly.
  - **Visuals**:
    - **Inactive State**: Unchecked discounts now remain visible but appear **greyed out and strikethrough**, ensuring user awareness of eligibility.
    - **Styling**: Standardized the display of discount amounts (using red text) for consistent visual identity across both discount types.

## [1.8.2] - 2026-01-28

### Changed
- UI: Payment Modal now displays "GESTIÓN DE CRÉDITO" and "REGISTRAR CRÉDITO" dynamically when credit payment is selected.
- Logic: Reverted direct credit processing to use the confirmation modal workflow with improved UI context.

### Fixed
- Logs: Fixed UTF-16 LE encoding issue for Laravel logs on Windows environments.
- Credit: Fixed `validateCreditLimit` to correctly calculate current debt by including partial payments.
- System: Updated `reset_system.php` to truncate `credit_discount_rules` table.

## [1.8.1] - 2026-01-27

### Fixed
- **Sales**:
  - **Registration Hang**: Fixed a critical issue where the "Registrar Venta" process would hang indefinitely when validation failed (e.g., missing customer), due to debug output corrupting the Livewire response.
- **Payments**:
  - **Bank History**: Fixed incomplete data display for Bank/Transfer payments in the history modal. Implemented proper `BankRecord` creation and linking in both "Abonos" (Partial Payments) and "Accounts Receivable" modules.
  - **Double Counting**: Resolved a calculation error where new payments were being double-counted (Database + Memory) during the "Paid" status check, causing some invoices to be marked as paid prematurely.
- **Data Integrity**:
  - **Repairs**: Included scripts to retroactively fix missing database links for recent bank payments.
- **Maintenance**:
  - **Clean**: Removed temporary debug scripts and updated `.gitignore`.

## [1.7.3] - 2026-01-22

### Added
- **Production Module**:
  - **Edit Functionality**: Enabled editing for productions in "Pending" status. Users can now modify dates, notes, and product details before sending to inventory.
  - **UI/UX**: Added an "Edit" button (pencil icon) in the production list, strictly controlled by status logic.

## [1.7.2] - 2026-01-22

### Fixed
- **Invoices**:
  - **PDF Generation**: Fixed "Blank Page" issue for Pending Sales by restoring missing logic and robustifying error handling.
  - **Logo**: Added fallback logic to prevent PDF generation failure when the company logo file is missing or the path is invalid.
- **Settings**:
  - **System Logo**: Fixed broken logo display in General Settings by repairing the `public/storage` symlink.
  - **Backup Email**: Added validation to prevent saving invalid email addresses and corrected database typos.
- **Backup System**:
  - **Database Dump**: Configured `mysqldump` binary path explicitly to fix "mysqldump not recognized" error.
  - **Transaction Mode**: Enabled `useSingleTransaction` to ensure consistent backups without locking tables.

## [1.7.1] - 2026-01-22

### Fixed
- **Update System**: Increased download timeout limit to 5 minutes to prevent cURL error 28 on slow connections.

## [1.7.0] - 2026-01-22

### Added
- **Network Printer Authentication**:
  - **Global Settings**: Added fields in "Configuraciones > General" to define a default network printer with authentication (IP, Share Name, User, Password).
  - **User Profile**: Added overrides in "Usuarios > Editar" to assign specific network printers and credentials per user.
  - **Database**: Added `is_network`, `printer_user`, and `printer_password` columns to `configurations` and `users` tables.
  - **Printing Logic**: Updated system to prioritize printer configuration in the following order: Device > User > Global.
  - **SMB Protocol**: Implemented secure SMB connection URI construction (`smb://user:pass@host/share`) for printing to password-protected shared printers.

## [1.6.0] - 2026-01-18

### Added
- **Label Generator Module**:
  - **New Module**: Added a dedicated module for generating product labels (accessible via Sidebar > Etiquetas).
  - **Product Selection**: Search by Name, SKU, Category, or Tag.
  - **PDF Generation**: Generates a printable PDF with 28 labels per page (4 columns x 7 rows) on Letter size paper.
  - **Label Design**: Includes Product Name (Large), Operator, Date, and Barcode (Code 128).

## [1.5.4] - 2026-01-18

### Fixed
- **Access Control**:
  - **Permissions**: Fixed "Access Denied" error in "Assign Permissions" module for the Super Admin account when the "Admin" role is missing. Added explicit bypass for the owner's email.

## [1.5.3] - 2026-01-18

### Fixed
- **Installation**:
  - **Middleware**: Fixed a critical crash on fresh installations where `CheckDeviceAuthorization` and `CheckLicense` middleware would attempt to connect to the database before it was configured. Added checks to skip these middlewares if the application is not installed.

## [1.5.2] - 2026-01-18

### Fixed
- **Database**:
  - **Migrations**: Fixed "Column already exists" error by making the delivery fields migration idempotent. This ensures smooth updates even if previous migrations partially ran.

## [1.5.1] - 2026-01-18

### Fixed
- **System Update**:
  - **UI**: Fixed an issue where a dark overlay (backdrop) would block the screen after an update.
  - **Error Handling**: Added robust error handling for reading release notes.
- **Database**:
  - **Migrations**: Fixed execution order for delivery tracking migrations to prevent "Column not found" errors.
  - **Roles**: Ensure "Driver" role is correctly created by the seeder.
- **Access Control**:
  - **Super Admin**: Added failsafe mechanism to restore Admin access for the system owner.

## [1.5.0] - 2026-01-18

### Added
- **Delivery Tracking System**:
  - **Driver Dashboard**: New dedicated dashboard for drivers to view assigned orders, update status, and report collections.
  - **Live Tracking**: Real-time driver location tracking for administrators.
  - **Collection Reporting**: Drivers can now report payments (multi-currency) and notes directly from their dashboard.
  - **Admin Visibility**: Added "Reportes de Chofer / Cobranza" section to the Sale Detail modal in Admin Sales Report.
- **Mobile Experience**:
  - **Barcode Scanner**: Integrated camera-based barcode scanner for mobile POS.
  - **Optimizations**: Improved touch targets and layout for mobile devices.
- **Performance**:
  - **Database Indexes**: Added missing indexes to `sales`, `products`, and `customers` tables for faster queries.
  - **Query Optimization**: Fixed N+1 query issues in Sales and Reports.

## [1.4.11] - 2026-01-18

### Fixed
- **Reports**:
  - **Rotation Report**: Fixed "Malformed UTF-8 characters" error during PDF generation by implementing robust data sanitization and switching to `streamDownload`.
  - **Styling**: Applied professional design to the Rotation Report PDF, matching the "Accounts Receivable" report style (Logo, Header, Styled Table).
- **Security**:
  - **Device Authorization**: Enhanced middleware robustness with aggressive input sanitization and error handling to prevent crashes from malformed User Agent strings.

## [1.4.10] - 2026-01-17

### Fixed
- **POS**:
  - **Partial Payment Modal**: Fixed a bug where the "Abonos" modal would close automatically (leaving a gray backdrop) due to a component re-render issue caused by a dynamic key.

## [1.4.9] - 2026-01-17

### Fixed
- **System Update**:
  - **Progress Bar Visibility**: Changed the update progress bar color to yellow (`bg-warning`) with a white background track to ensure it is clearly visible against the blue alert background.

## [1.4.8] - 2026-01-17

### Fixed
- **UI**:
  - **Scrollbar**: Further improved scrollbar visibility with high-contrast colors (Dark Grey thumb on Light Grey track) and increased width for better accessibility.

## [1.4.6] - 2026-01-17

### Fixed
- **System Update**:
  - **Friendly Error Page**: Implemented a user-friendly "Update Required" page when database migrations are pending, replacing the raw Laravel error screen.
  - **Auto-Fix Button**: Added a "Run Update" button to the error page that automatically executes pending migrations.
- **UI**:
  - **Scrollbar**: Improved scrollbar visibility (darker contrast) in the POS sales view.

## [1.4.5] - 2026-01-17

### Added
- **Printing**:
  - **Device-Specific Printers**: Added ability to assign a specific printer and paper width to each device (PC/Mobile) via "Device Manager".
  - **Priority Logic**: Printing now prioritizes: Device Configuration > User Configuration > Global Configuration.
- **Device Manager**:
  - **Inline Editing**: Restored ability to edit device names directly in the list.
  - **Configuration Modal**: Added modal to configure printer name/path and width per device.
  - **Help Guide**: Added comprehensive guide for device and printer configuration.

## [1.4.4] - 2026-01-17

### Fixed
- **Update System**:
  - **Changelog Visibility**: Fixed an issue where `CHANGELOG.md` was excluded from release zips (via `.gitattributes`), causing clients to not see release notes after updating.

## [1.4.3] - 2026-01-16

### Changed
- **UI**:
  - **Footer**: Updated copyright year to 2026.

## [1.4.2] - 2026-01-16

### Fixed
- **Update System**:
  - **Cache Clearing**: Implemented automatic clearing of the "Update Available" cache key (`system_update_available`) after a successful update to ensure the header notification disappears immediately.

## [1.4.1] - 2026-01-16

### Fixed
- **Update System**:
  - **Version Persistence**: Fixed an issue where `version.txt` was not being updated after a system update.
  - **Update Logic**: Modified `UpdateService` to explicitly write the new version number to `version.txt` upon successful installation.

## [1.4.0] - 2026-01-14

### Added
- **Composite Products (Kits/Bundles)**:
  - **Modes**: Implemented "Pre-assembled" (Physical Stock) and "On-Demand" (Dynamic Stock) modes.
  - **Stock Management**:
    - **Pre-assembled**: Creating/Increasing stock deducts components. Selling deducts the kit. Purchasing increments the kit.
    - **On-Demand**: Selling deducts components directly. Purchasing increments components.
  - **UI**: Added "Pre-assembled" switch and "Additional Cost" field to Product Form.
- **Inventory Visibility**:
  - **Stock Distribution**: Added a table in Product Form (Inventory tab) showing stock quantity per warehouse.
- **Product Form Enhancements**:
  - **Persistent Edit**: Form now stays open after saving/updating to allow continuous editing.
  - **Navigation**: Renamed "Cancel" button to "Volver a Productos" for clarity.

### Changed
- **Sales**:
  - **Validation**: Updated stock validation to allow selling "On-Demand" products even if parent stock is 0 (checks components instead).
- **Purchases**:
  - **Stock Logic**: Updated purchase logic to handle both composite modes correctly.

## [1.3.3] - 2026-01-15

### Added
- **Reports**:
  - **Rotation Report**: Added a new report to analyze product rotation and movement.
- **Configuration**:
  - **Purchasing Settings**: Added configuration for purchasing calculation mode and coverage days.
- **Products**:
  - **Pre-assembled Products**: Added support for pre-assembled products and additional costs.

## [1.3.2] - 2026-01-14

### Changed
- **POS**:
  - **Compact Search Results**: Redesigned the product search dropdown to be more compact, showing more results (limit increased to 25).
  - **Stock Display**: Fixed discrepancy in "Total Stock" display by dynamically summing warehouse stocks.
  - **Revert**: Reverted "Product Presentations" and "Advanced Pricing" features to restore previous stability and functionality.

## [1.3.1] - 2026-01-12

### Added
- **Backup System**:
  - **Google Drive Integration**: Added support for automated backups to Google Drive.
  - **Windows Automation**: Included `backup.bat` script for Windows Task Scheduler integration.
  - **Email Attachments**: Configured system to send database backups via email (optional).
- **Auto-Updater**:
  - Implemented `UpdateService` to fetch releases from GitHub.
  - Added "Update System" UI in Settings to check for and apply updates.

## [1.3.0] - 2026-01-11

### Added
- **Licensing System**:
  - Implemented secure offline licensing using RSA cryptography.
  - Added "System Locked" mode for expired licenses.
  - Added "License Generator" tool for administrators.
- **Installation System**:
  - Created a web-based Installation Wizard (Steps: Requirements, Database, Migrations, License, Admin).
  - Added `InstallController` and routes to handle the setup process.
  - Added `CheckInstalled` middleware to redirect to installer if not configured.
- **Role Management**:
  - Implemented **Level-based Hierarchy** (Admin=100, Dueño=50, etc.).
  - Users can only assign roles with a lower level than their own.
  - Added `level` column to `roles` table.
  - Protected Super Admin account from modification.
- **Desktop Integration**:
  - Added "Create Shortcut" feature to the installer.
  - Generates a `.bat` script that creates a Chrome App Mode shortcut (`--app`) and auto-launches the system.
- **Data Initialization**:
  - Added `WarehouseSeeder` to create a default "Tienda Principal" warehouse.
  - Updated `ConfigurationSeeder` to set the default warehouse automatically.

## [1.2.9] - 2026-01-11

### Added
- **Sales**:
  - **Zelle Integration**: Fully integrated Zelle payments into the Sales module.
    - Added `zelle_records` and `sale_payment_details` tables.
    - Implemented real-time validation for Zelle payments.
    - Made Zelle image upload mandatory for verification.
    - Added "Ver Comprobante" link in Sale Details modal.
- **Printing**:
  - **Dynamic Ticket Format**: Implemented intelligent detection for **58mm** and **80mm** printers.
    - Tickets automatically adjust width and separators based on configuration.
    - Centered business header with optimized font size.
    - Added "Condición de Venta" (Crédito/Contado) to the ticket header.
    - Validated compatibility across all ticket types (Sales, Orders, Payments, Cash Count).

## [1.2.8] - 2026-01-10

### Added
- **Warehouse Management**:
  - **System Default Warehouse**: Added configuration to set a system-wide default warehouse for users without a specific assignment.
  - **Permissions**: Implemented granular permissions for warehouse management:
    - `warehouses.create`, `warehouses.edit`, `warehouses.delete` (Internal).
    - `sales.switch_warehouse`, `sales.mix_warehouses` (Internal).
  - **Permission Assignment UI**: Redesigned the permission assignment view with a professional Bootstrap grid layout and Spanish translations (e.g., "Ventas: Cambiar Depósito").

### Changed
- **Sales**:
  - **Warehouse Selection**: Automatically selects the system default warehouse if the user has no principal warehouse assigned.
  - **Permission Enforcement**: Restricted warehouse switching and mixing based on user permissions.

## [1.2.8] - 2026-01-10

### Added
- **Reports**:
  - **Best Sellers Report**: Added a new report module to view top-selling products with filters for date range, category, and status. Includes Bar and Pie charts.
- **Dashboard**:
  - **Top Sellers Chart**: Added a new chart to visualize the top 5 sellers by profit for the current month.
  - **Chart Type Toggle**: Added functionality to switch the "Top Sellers" chart between Column, Bar, Pie, and Donut views dynamically.
  - **Role Filtering**: Configured "Top Sellers" chart to only display users with the "Vendedor" role, correctly attributing sales to the account manager (Customer's Seller).

### Changed
- **Dashboard**:
  - **Charting Library**: Migrated all dashboard charts from Chart.js to **Highcharts** for better performance and consistency.
  - **Optimizations**: Optimized database queries for "Top Products" and "Low Stock" widgets to improve dashboard load time.
  - **Image Handling**: Improved product image loading logic to prevent broken images.

## [1.2.4] - 2026-01-09

### Added
- **Profile**:
  - **Browser Sessions**: Added functionality to view and manage active browser sessions (Desktop/Mobile, IP, Last Activity).
  - **Logout Other Devices**: Added ability to log out from all other devices securely.
  - **AdminLTE Integration**: Redesigned the entire Profile page to match the system's AdminLTE theme.
    - Used Bootstrap Grid and Cards.
    - Replaced Tailwind CSS forms with Bootstrap forms.
    - Replaced Alpine.js modals with Bootstrap modals.

### Fixed
- **UI/UX**:
  - **Sidebar Logo**: Fixed the sidebar to dynamically display the company logo and name from settings.
  - **Profile Page**: Fixed broken layout and navigation links on the profile page by switching to the correct AdminLTE layout component.
  - **Vite Manifest**: Resolved `ViteManifestNotFoundException` by regenerating build assets.

## [1.2.3] - 2026-01-09

### Added
- **Settings**:
  - Added "Company Logo" upload functionality in General Settings.
  - Added `logo` field to `configurations` table.

### Changed
- **PDF Reports & Invoices**:
  - **Standardized Header Design**: Applied a consistent, professional header design across ALL system PDFs (Invoices, Orders, Reports).
    - Layout: Logo (Left), Company Name (Center), Document Title/Number (Right).
    - Added rounded "Info Box" for client/report details.
    - Updated color scheme to use consistent Blue (`#0380b2`) for titles and backgrounds.
  - **Updated Templates**:
    - `invoice-paid` (Sales Invoice)
    - `invoice-order-processed` (Processed Order)
    - `invoice-order-pending` (Pending Order)
    - `accounts-receivable-pdf` (Cuentas por Cobrar)
    - `payment-relationship-pdf` (Relación de Pagos)
    - `daily-sales-report-pdf` (Ventas Diarias)
    - `payment-history-pdf` (Historial de Pagos)
    - `collection-sheets-list-pdf` (Planillas General)
    - `collection-sheet-detail-pdf` (Planilla Básica)
    - `collection-sheet-detail-full-pdf` (Planilla Detallada)

## [1.2.2] - 2026-01-09

### Fixed
- **Purchases**:
  - Fixed layout issue where the "Resumen" card was not properly aligned in the grid (wrapped in `col-md-3`).

## [1.2.0] - 2026-01-09

### Added
- **Dashboard**:
  - Implemented a comprehensive Dashboard at `/welcome`.
  - Added KPI Cards for Sales, Purchases, and Receivables.
  - Added "Recent Sales" table and "Top Products" list.
  - Added "Low Stock Alerts" widget.
  - Added "Pending Commissions" widget (moved to top row).
  - Added "Sales vs Profit" Chart (Last 7 Days).
  - Added "Top Suppliers" widget.
- **UI Enhancements**:
  - Added scrollbar (`max-height: 300px`) to all header notification dropdowns.

### Fixed
- **Dashboard**:
  - Resolved `MultipleRootElementsDetectedException` in Livewire component.
  - Fixed Commission Widget value to match Header Notification logic (Paid sales, Foreign sales, Permissions).
  - Fixed Low Stock Alert contrast issue.
- **Navigation**:
  - Added "DASHBOARD" link to the sidebar.

## [1.1.0] - 2026-01-08

### Added
- **Collection Sheet Reports**:
  - Implemented `CollectionSheet` model and migration.
  - Added "Relación de Cobro" (Payment Relationship) reports with detailed and basic views.
  - Added PDF export functionality for Collection Sheets (Basic, Detailed, and General).
  - Added "Hojas de Cobranza" listing and management.
  - **Enhanced PDF Summaries**: Added a detailed summary table to all PDF reports showing "Original Amount" (per currency) and "USD Equivalent".
  - **PDF Styling**: Aligned "Detailed" PDF style with "Basic" PDF, including payment details row.

### Changed
- **Payment Relationship**:
  - Enhanced `PaymentRelationshipReport` to include dynamic filtering and better data presentation.
  - Refined PDF layouts: Moved summary table to the top of the report (below filters) for better visibility.
  - Updated `Payment` model to support new reporting relationships.

## [1.0.0] - 2026-01-08

### Added
- **Zelle Payment Integration**:
  - Added `zelle_records` table to store Zelle transaction details.
  - Added `zelle_record_id` to `payments` table for direct linking.
  - Integrated Zelle into the "Bank" payment method in `PaymentComponent`.
  - Real-time validation for Zelle payments (duplicate detection, balance tracking).
  - Automatic status updates for Zelle records ('partial', 'used').
  - Display of Zelle details (Sender, Date) in payment history.
  - Support for Zelle payments in `AccountsReceivableReport`.

### Changed
- Updated `pay_way` ENUM in `payments` table to include 'zelle'.
- Modified `historypays.blade.php` to show Zelle specific information.

### Fixed
- Fixed issue where Zelle records were not being created when paying via Accounts Receivable Report.
