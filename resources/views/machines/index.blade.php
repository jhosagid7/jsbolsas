@extends('layouts.app')
@section('title', 'Máquinas y Líneas de Producción')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">⚙️ Máquinas y Líneas de Producción</h3>
        <p class="text-white-50 mb-0">Extrusoras, selladoras y molinos de la planta</p>
    </div>
    <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#createMachineModal">
        <i class="bi bi-plus-circle-fill me-1"></i> Registrar Nueva Máquina
    </button>
</div>

<div class="card-custom">
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Nombre de la Máquina</th>
                    <th>Código</th>
                    <th>Tipo</th>
                    <th>Turnos Realizados</th>
                    <th>Novedades Calidad</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($machines as $m)
                    <tr>
                        <td class="fw-bold text-white">
                            <a href="{{ route('machines.show', $m->id) }}" class="text-white text-decoration-none hover-primary">
                                {{ $m->name }} <i class="bi bi-box-arrow-up-right small text-primary ms-1"></i>
                            </a>
                        </td>
                        <td><span class="badge bg-secondary">{{ $m->code }}</span></td>
                        <td><span class="badge bg-info text-dark">{{ strtoupper($m->type) }}</span></td>
                        <td><span class="badge bg-dark border border-secondary">{{ $m->shifts_count ?? 0 }} turnos</span></td>
                        <td>
                            @if(($m->incidents_count ?? 0) > 0)
                                <span class="badge bg-danger">{{ $m->incidents_count }} reportes</span>
                            @else
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Sin Novedades</span>
                            @endif
                        </td>
                        <td><span class="badge bg-success">Operativa</span></td>
                        <td class="text-end">
                            <a href="{{ route('machines.show', $m->id) }}" class="btn btn-outline-primary btn-sm me-1" title="Ver Historial y Métricas">
                                <i class="bi bi-graph-up me-1"></i> Historial
                            </a>
                            <form action="{{ route('machines.destroy', $m->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que deseas eliminar esta máquina?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar Máquina">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createMachineModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('machines.store') }}" method="POST">
                @csrf
                <div class="modal-header border-secondary-subtle">
                    <h5 class="modal-title fw-bold">Registrar Máquina</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small text-white-50">Nombre</label>
                        <input type="text" name="name" class="form-control" placeholder="Ej. Extrusora Monocapa #3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-white-50">Código</label>
                        <input type="text" name="code" class="form-control" placeholder="Ej. EXT-03" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-white-50">Tipo</label>
                        <select name="type" class="form-select" required>
                            <option value="extrusora">Extrusora</option>
                            <option value="selladora">Selladora</option>
                            <option value="cortadora">Cortadora</option>
                            <option value="recuperadora">Molino / Recuperadora</option>
                            <option value="otra">Otra</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-secondary-subtle">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold">Guardar Máquina</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
