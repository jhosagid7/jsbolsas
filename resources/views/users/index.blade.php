@extends('layouts.app')
@section('title', 'Gestión de Usuarios y Roles')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">👥 Usuarios y Control de Accesos</h3>
        <p class="text-white-50 mb-0">Jerarquía de permisos: Super Admin (Dueño), Administrador, Jefe de Operaciones, Operarios y Almacén.</p>
    </div>
    <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#createUserModal">
        <i class="bi bi-person-plus me-1"></i> Nuevo Usuario
    </button>
</div>

<!-- Tabla de Usuarios -->
<div class="card-custom">
    <div class="table-responsive">
        <table class="table table-custom mb-0 align-middle">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo Electrónico</th>
                    <th>Rol en el Sistema</th>
                    <th>Fecha Registro</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                    <tr>
                        <td class="fw-bold text-white">
                            <i class="bi bi-person-circle me-2 text-info"></i> {{ $u->name }}
                            @if(Auth::id() == $u->id)
                                <span class="badge bg-secondary ms-1">Tú</span>
                            @endif
                        </td>
                        <td>{{ $u->email }}</td>
                        <td>
                            @if($u->isSuperAdmin())
                                <span class="badge bg-warning text-dark fw-bold px-3 py-1 shadow-sm">👑 SUPER ADMIN (Dueño)</span>
                            @elseif($u->isAdmin())
                                <span class="badge bg-info text-dark fw-bold px-2 py-1">👔 Administrador</span>
                            @elseif($u->isSupervisor())
                                <span class="badge bg-primary text-white fw-bold px-2 py-1">⚖️ Jefe de Operaciones</span>
                            @elseif($u->isOperario())
                                <span class="badge bg-secondary text-white px-2 py-1">👷 Operario de Planta</span>
                            @elseif($u->isAlmacen())
                                <span class="badge bg-success text-white px-2 py-1">📦 Almacén / POS</span>
                            @else
                                <span class="badge bg-dark px-2 py-1">{{ strtoupper($u->role) }}</span>
                            @endif
                        </td>
                        <td>{{ $u->created_at ? $u->created_at->format('d/m/Y') : '-' }}</td>
                        <td class="text-end">
                            <button class="btn btn-outline-warning btn-sm me-1" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $u->id }}">
                                ✏️ Editar
                            </button>
                            @if(Auth::id() != $u->id && (!$u->isSuperAdmin() || Auth::user()->isSuperAdmin()))
                                <form action="{{ route('users.destroy', $u->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que deseas eliminar este usuario?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">🗑️</button>
                                </form>
                            @endif
                        </td>
                    </tr>

                    <!-- Edit User Modal -->
                    <div class="modal fade" id="editUserModal{{ $u->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content bg-dark text-white border-secondary">
                                <form action="{{ route('users.update', $u->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header border-secondary">
                                        <h5 class="modal-title fw-bold">Editar Usuario: {{ $u->name }}</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label small text-white-50">Nombre Completo</label>
                                            <input type="text" name="name" class="form-control bg-secondary text-white border-0" value="{{ $u->name }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small text-white-50">Correo Electrónico</label>
                                            <input type="email" name="email" class="form-control bg-secondary text-white border-0" value="{{ $u->email }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small text-white-50">Rol y Nivel de Acceso</label>
                                            <select name="role" class="form-select bg-secondary text-white border-0" required>
                                                @if(Auth::user()->isSuperAdmin())
                                                    <option value="superadmin" {{ $u->role === 'superadmin' ? 'selected' : '' }}>👑 Super Admin (Dueño - Control Total)</option>
                                                @endif
                                                <option value="admin" {{ $u->role === 'admin' ? 'selected' : '' }}>👔 Administrador de Sistema</option>
                                                <option value="supervisor" {{ $u->role === 'supervisor' ? 'selected' : '' }}>⚖️ Jefe de Operaciones / Supervisor (Báscula y Auditoría)</option>
                                                <option value="operario" {{ $u->role === 'operario' ? 'selected' : '' }}>👷 Operario (Registro Móvil de Turnos y Producción)</option>
                                                <option value="almacen" {{ $u->role === 'almacen' ? 'selected' : '' }}>📦 Almacén (Levantamiento y Recepción General)</option>
                                            </select>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label small text-white-50">Salario Semanal ($ USD)</label>
                                                <input type="number" step="0.01" min="0" name="weekly_salary" class="form-control bg-secondary text-white border-0" value="{{ $u->weekly_salary ?? 90.00 }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small text-white-50">Jornada Laboral</label>
                                                <select name="work_days_per_week" class="form-select bg-secondary text-white border-0">
                                                    <option value="5" {{ ($u->work_days_per_week ?? 6) == 5 ? 'selected' : '' }}>5 Días / Semana</option>
                                                    <option value="6" {{ ($u->work_days_per_week ?? 6) == 6 ? 'selected' : '' }}>6 Días / Semana (Estándar)</option>
                                                    <option value="7" {{ ($u->work_days_per_week ?? 6) == 7 ? 'selected' : '' }}>7 Días / Semana</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" name="pay_partial_packages" value="1" id="payPartial{{ $u->id }}" {{ !empty($u->pay_partial_packages) ? 'checked' : '' }}>
                                            <label class="form-check-label small text-white-50" for="payPartial{{ $u->id }}">
                                                Pagar fracciones sueltas inmediatamente (sin retener)
                                            </label>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small text-white-50">Nueva Contraseña (Opcional)</label>
                                            <input type="password" name="password" class="form-control bg-secondary text-white border-0" placeholder="Dejar en blanco para mantener la actual">
                                        </div>
                                    </div>
                                    <div class="modal-footer border-secondary">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-warning btn-sm fw-bold">Actualizar Usuario</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white border-secondary">
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="modal-header border-secondary">
                    <h5 class="modal-title fw-bold text-primary">Registrar Nuevo Usuario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small text-white-50">Nombre Completo</label>
                        <input type="text" name="name" class="form-control bg-secondary text-white border-0" placeholder="Ej. Juan Pérez" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-white-50">Correo Electrónico</label>
                        <input type="email" name="email" class="form-control bg-secondary text-white border-0" placeholder="correo@empresa.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-white-50">Rol y Nivel de Acceso</label>
                        <select name="role" class="form-select bg-secondary text-white border-0" required>
                            @if(Auth::user()->isSuperAdmin())
                                <option value="superadmin">👑 Super Admin (Dueño - Control Total)</option>
                            @endif
                            <option value="admin">👔 Administrador de Sistema</option>
                            <option value="supervisor" selected>⚖️ Jefe de Operaciones / Supervisor</option>
                            <option value="operario">👷 Operario (App Móvil)</option>
                            <option value="almacen">📦 Almacén (Levantamiento General)</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small text-white-50">Salario Semanal ($ USD)</label>
                            <input type="number" step="0.01" min="0" name="weekly_salary" class="form-control bg-secondary text-white border-0" value="90.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-white-50">Jornada Laboral</label>
                            <select name="work_days_per_week" class="form-select bg-secondary text-white border-0">
                                <option value="5">5 Días / Semana</option>
                                <option value="6" selected>6 Días / Semana (Estándar)</option>
                                <option value="7">7 Días / Semana</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="pay_partial_packages" value="1" id="payPartialCreate">
                        <label class="form-check-label small text-white-50" for="payPartialCreate">
                            Pagar fracciones sueltas inmediatamente (sin retener)
                        </label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-white-50">Contraseña</label>
                        <input type="password" name="password" class="form-control bg-secondary text-white border-0" placeholder="Mínimo 6 caracteres" required>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold">Guardar Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
