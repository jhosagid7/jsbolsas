<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>@yield('title', 'Control de Planta') - JSBolsas Pro</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon-jsbolsas.png') }}">
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon-jsbolsas.png') }}">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --bg-main: #0b132b;
            --bg-card: #1c2541;
            --bg-sidebar: #0e1726;
            --accent: #0284c7;
            --accent-hover: #0369a1;
            --border-color: rgba(255, 255, 255, 0.08);
        }
        body {
            background-color: var(--bg-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #f1f5f9;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Top Navbar Móvil */
        .mobile-topbar {
            background: var(--bg-sidebar);
            border-bottom: 1px solid var(--border-color);
            padding: 12px 16px;
            z-index: 1030;
        }

        /* Sidebar Desktop */
        @media (min-width: 992px) {
            .sidebar-desktop {
                width: 270px;
                background: var(--bg-sidebar);
                border-right: 1px solid var(--border-color);
                height: 100vh;
                position: fixed;
                top: 0;
                left: 0;
                z-index: 1020;
                padding: 24px 16px;
                display: flex;
                flex-direction: column;
                overflow-y: auto;
            }
            .main-content {
                margin-left: 270px;
                padding: 30px;
                min-height: 100vh;
                background-color: var(--bg-main);
            }
        }

        /* Sidebar Móvil (Offcanvas) */
        @media (max-width: 991.98px) {
            .sidebar-desktop {
                display: none;
            }
            .main-content {
                margin-left: 0;
                padding: 16px 12px;
                min-height: calc(100vh - 65px);
            }
            .offcanvas-sidebar {
                background: var(--bg-sidebar) !important;
                border-right: 1px solid var(--border-color) !important;
                width: 285px !important;
            }
            .offcanvas-sidebar .offcanvas-body {
                padding: 20px 16px;
                display: flex;
                flex-direction: column;
            }
        }

        .nav-link-custom {
            color: #94a3b8;
            font-weight: 600;
            font-size: 13.5px;
            padding: 10px 14px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 4px;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .nav-link-custom:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.06);
        }
        .nav-link-custom.active {
            color: #fff;
            background: var(--accent);
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.35);
        }
        .card-custom {
            background: var(--bg-card);
            border-radius: 16px;
            border: 1px solid var(--border-color);
            padding: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }
        @media (max-width: 576px) {
            .card-custom {
                padding: 15px;
                border-radius: 12px;
            }
        }

        /* Tablas con scroll suave y táctil */
        .table-responsive {
            -webkit-overflow-scrolling: touch;
            border-radius: 10px;
        }
        .table-custom {
            --bs-table-bg: transparent;
            --bs-table-color: #f1f5f9;
            border-color: var(--border-color);
            white-space: nowrap;
        }
        .table-custom th {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border-color);
            padding: 12px 14px;
            background: rgba(0, 0, 0, 0.2);
        }
        .table-custom td {
            padding: 12px 14px;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
            font-size: 13px;
        }
        .form-control, .form-select {
            background: #0f172a;
            border: 1px solid #334155;
            color: #fff;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
        }
        .form-control:focus, .form-select:focus {
            background: #0f172a;
            color: #fff;
            border-color: #38bdf8;
            box-shadow: 0 0 0 0.25rem rgba(56,189,248,0.25);
        }
        .modal-content {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            color: #fff;
        }
        .badge-role {
            font-size: 0.72rem;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: 700;
        }

        /* Scrollbar estilizado */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.2);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.15);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Info Popover & Tooltip Styles Globales */
        .info-tooltip-btn {
            color: #38bdf8 !important;
            cursor: pointer;
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), color 0.2s ease;
            vertical-align: middle;
            font-size: 13.5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            padding: 0;
            border: none;
            background: transparent;
        }
        .info-tooltip-btn:hover, .info-tooltip-btn:focus {
            color: #facc15 !important;
            transform: scale(1.25);
            outline: none;
        }
        .dark-info-popover {
            --bs-popover-bg: #0f172a;
            --bs-popover-border-color: rgba(56, 189, 248, 0.35);
            --bs-popover-header-bg: #1e293b;
            --bs-popover-header-color: #facc15;
            --bs-popover-body-color: #f8fafc;
            --bs-popover-box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.7), 0 10px 15px -5px rgba(0, 0, 0, 0.5);
            --bs-popover-border-radius: 12px;
            max-width: 330px;
            font-size: 12.5px;
            line-height: 1.55;
            z-index: 1060;
        }
        .dark-info-popover .popover-header {
            font-weight: 700;
            font-size: 13.5px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 10px 14px;
            background-color: #1e293b;
            color: #facc15;
            border-top-left-radius: 11px;
            border-top-right-radius: 11px;
        }
        .dark-info-popover .popover-body {
            padding: 12px 14px;
            color: #e2e8f0;
            background-color: #0f172a;
            border-bottom-left-radius: 11px;
            border-bottom-right-radius: 11px;
        }
        .dark-info-popover .popover-body strong {
            color: #38bdf8;
        }
    </style>
</head>
<body>
    <!-- Topbar para Móviles y Tablets (< 992px) -->
    <header class="mobile-topbar d-lg-none d-flex justify-content-between align-items-center sticky-top">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-info btn-sm p-2 d-flex align-items-center justify-content-center" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" style="border-radius: 8px; width: 38px; height: 38px;">
                <i class="bi bi-list fs-4"></i>
            </button>
            <div class="d-flex align-items-center gap-2">
                <span style="font-size: 24px;">🏭</span>
                <div>
                    <h6 class="fw-bold mb-0 text-white line-height-1">JSBolsas <span class="text-info">Pro</span></h6>
                    <small class="text-white-50" style="font-size: 9.5px;">M&F Steel • {{ $systemVersion ?? 'v2.3.1' }}</small>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            @if(Auth::user()->isSuperAdmin())
                <span class="badge bg-warning text-dark font-monospace" style="font-size: 10px;">👑 ADMIN</span>
            @elseif(Auth::user()->isSupervisor())
                <span class="badge bg-primary font-monospace" style="font-size: 10px;">⚖️ SUPERVISOR</span>
            @else
                <span class="badge bg-secondary font-monospace" style="font-size: 10px;">{{ strtoupper(Auth::user()->role) }}</span>
            @endif
        </div>
    </header>

    <!-- Sidebar Desktop (Visible en >= 992px) -->
    <aside class="sidebar-desktop d-none d-lg-flex">
        @include('layouts.sidebar-content')
    </aside>

    <!-- Sidebar Offcanvas para Móvil (Visible al presionar menú en < 992px) -->
    <div class="offcanvas offcanvas-start offcanvas-sidebar text-white d-lg-none" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
        <div class="offcanvas-header border-bottom border-secondary-subtle py-3 px-3">
            <div class="d-flex align-items-center gap-2" id="mobileSidebarLabel">
                <span style="font-size: 26px;">🏭</span>
                <div>
                    <h5 class="fw-bold mb-0 text-white">JSBolsas <span class="text-info">Pro</span></h5>
                    <small class="text-white-50" style="font-size: 10px;">Plásticos M&F Steel</small>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            @include('layouts.sidebar-content')
        </div>
    </div>

    <!-- Contenido Principal -->
    <main class="main-content">
        @if(session('status'))
            <div class="alert alert-success alert-dismissible fade show border-0 bg-success-subtle text-success fw-bold py-2 mb-3" role="alert">
                <strong><i class="bi bi-check-circle-fill me-1"></i> Éxito:</strong> {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 bg-danger-subtle text-danger fw-bold py-2 mb-3" role="alert">
                <strong><i class="bi bi-exclamation-triangle-fill me-1"></i> Error:</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inicializador Global de Popovers / Tooltips en Tema Oscuro
        function initGlobalPopovers() {
            if (typeof bootstrap === 'undefined' || !bootstrap.Popover) return;
            const popovers = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popovers.forEach(function (el) {
                const inst = bootstrap.Popover.getInstance(el);
                if (inst) inst.dispose();
                new bootstrap.Popover(el, {
                    trigger: 'hover focus',
                    html: true,
                    container: 'body',
                    customClass: 'dark-info-popover shadow-lg'
                });
            });
        }
        document.addEventListener('DOMContentLoaded', initGlobalPopovers);
    </script>
</body>
</html>
