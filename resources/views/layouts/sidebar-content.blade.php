<!-- Logo y Marca (Visible solo en Desktop Sidebar) -->
<div class="d-none d-lg-flex align-items-center gap-2 mb-4 px-2">
    <span style="font-size: 30px;">🏭</span>
    <div>
        <h5 class="fw-bold mb-0 text-white">JSBolsas <span class="text-info">Pro</span></h5>
        <div class="d-flex align-items-center gap-1 mt-1">
            <small class="text-white-50" style="font-size: 10px;">Plásticos M&F Steel</small>
            <span class="badge bg-warning text-dark font-monospace" style="font-size: 9px; padding: 2px 5px;">{{ $systemVersion ?? 'v1.0.0' }}</span>
        </div>
    </div>
</div>

<!-- Descargar APK Móvil -->
<div class="mb-3 px-1">
    <a href="/JSBolsas.apk" class="btn btn-outline-info btn-sm w-100 fw-bold d-flex align-items-center justify-content-center gap-2 py-2" style="border-radius: 10px;">
        <i class="bi bi-android2 fs-5"></i> Descargar APK Móvil <span class="badge bg-info text-dark">{{ $systemVersion ?? 'v1.0.0' }}</span>
    </a>
</div>

<!-- Módulos de Control de Planta -->
<div class="mb-3">
    <small class="text-uppercase text-secondary fw-bold px-2" style="font-size: 10px; letter-spacing: 0.5px;">CONTROL DE PLANTA</small>
    <div class="mt-2">
        <a href="{{ route('dashboard') }}" class="nav-link-custom {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-fill text-info"></i> Monitor & Finanzas
        </a>
        <a href="{{ route('scale.index') }}" class="nav-link-custom {{ request()->routeIs('scale.index') ? 'active' : '' }}">
            <i class="bi bi-speedometer2 text-warning"></i> Báscula & Auditoría
        </a>
        <a href="{{ route('reports.index') }}" class="nav-link-custom {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <i class="bi bi-file-earmark-bar-graph-fill text-success"></i> Reportes por Día
        </a>
    </div>
</div>

<!-- Fórmulas y Costos -->
<div class="mb-3">
    <small class="text-uppercase text-secondary fw-bold px-2" style="font-size: 10px; letter-spacing: 0.5px;">FÓRMULAS & MATERIA PRIMA</small>
    <div class="mt-2">
        <a href="{{ route('formulas.index') }}" class="nav-link-custom {{ request()->routeIs('formulas.*') ? 'active' : '' }}">
            <i class="bi bi-bezier2 text-warning"></i> Fórmulas de Mezcla
        </a>
        <a href="{{ route('raw_materials.index') }}" class="nav-link-custom {{ request()->routeIs('raw_materials.*') ? 'active' : '' }}">
            <i class="bi bi-boxes text-info"></i> Materias Primas
        </a>
        <a href="{{ route('costs.index') }}" class="nav-link-custom {{ request()->routeIs('costs.*') ? 'active' : '' }}">
            <i class="bi bi-sliders text-success"></i> Costos & Precios
        </a>
    </div>
</div>

<!-- Administración y Catálogo -->
<div class="mb-4">
    <small class="text-uppercase text-secondary fw-bold px-2" style="font-size: 10px; letter-spacing: 0.5px;">ADMINISTRACIÓN</small>
    <div class="mt-2">
        <a href="{{ route('products.index') }}" class="nav-link-custom {{ request()->routeIs('products.*') ? 'active' : '' }}">
            <i class="bi bi-box-seam-fill text-info"></i> Catálogo de Bolsas
        </a>
        <a href="{{ route('machines.index') }}" class="nav-link-custom {{ request()->routeIs('machines.*') ? 'active' : '' }}">
            <i class="bi bi-gear-wide-connected text-primary"></i> Máquinas & Líneas
        </a>
        <a href="{{ route('users.index') }}" class="nav-link-custom {{ request()->routeIs('users.*') ? 'active' : '' }}">
            <i class="bi bi-people-fill text-warning"></i> Usuarios & Roles (APK)
        </a>
    </div>
</div>

<!-- Perfil de Usuario y Cierre de Sesión al Fondo -->
<div class="mt-auto pt-3 border-top border-secondary-subtle">
    <div class="px-2 mb-2">
        <div class="d-flex justify-content-between align-items-center">
            <strong class="text-white small text-truncate" style="max-width: 140px;">{{ Auth::user()->name ?? 'Usuario' }}</strong>
            @if(Auth::user()->isSuperAdmin())
                <span class="badge bg-warning text-dark badge-role">👑 SUPER ADMIN</span>
            @elseif(Auth::user()->isAdmin())
                <span class="badge bg-info text-dark badge-role">👔 ADMIN</span>
            @elseif(Auth::user()->isSupervisor())
                <span class="badge bg-primary text-white badge-role">⚖️ SUPERVISOR</span>
            @else
                <span class="badge bg-secondary badge-role">{{ strtoupper(Auth::user()->role) }}</span>
            @endif
        </div>
        <small class="text-white-50 d-block text-truncate" style="font-size: 11px;">{{ Auth::user()->email ?? '' }}</small>
    </div>
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-outline-danger btn-sm w-100 fw-bold d-flex align-items-center justify-content-center gap-1 mt-2">
            <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
        </button>
    </form>
</div>
