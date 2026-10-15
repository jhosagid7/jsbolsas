<div class="cloud-sync-widget card border-0 p-2 shadow-sm mb-3" style="background: #111827; border-radius: 12px; border: 1px solid #374151 !important;">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="cloud-sync-dot spinner-grow spinner-grow-sm text-secondary" style="width: 9px; height: 9px;" role="status"></span>
            <div>
                <div class="d-flex align-items-center gap-1">
                    <span class="cloud-sync-badge badge bg-secondary font-monospace" style="font-size: 10px;">
                        Verificando Nube...
                    </span>
                    <span class="badge bg-dark text-white-50 border border-secondary font-monospace" style="font-size: 9px;">
                        {{ strtoupper(env('NODE_ROLE', 'LOCAL')) }}
                    </span>
                </div>
                <small class="cloud-sync-last-text text-white-50 d-block" style="font-size: 10px;">
                    Última sinc: Consultando...
                </small>
            </div>
        </div>

        <div class="d-flex align-items-center gap-1">
            @if(env('NODE_ROLE', 'local') === 'local')
                <button type="button" class="btn-cloud-sync-manual btn btn-outline-info btn-sm fw-bold py-1 px-2 d-flex align-items-center gap-1" style="font-size: 11px; border-radius: 8px;">
                    <i class="cloud-sync-icon bi bi-arrow-repeat"></i>
                    <span class="cloud-sync-btn-text">Sincronizar VPS</span>
                </button>
            @endif
            @if(Auth::check() && Auth::user()->isSuperAdmin())
                <a href="{{ route('sistema.respaldo.descargar') }}" class="btn btn-outline-success btn-sm fw-bold py-1 px-2 d-flex align-items-center gap-1 text-decoration-none" style="font-size: 11px; border-radius: 8px;" title="Descargar copia de seguridad de la base de datos (.sql.gz)">
                    <i class="bi bi-database-down"></i>
                    <span>Respaldar DB</span>
                </a>
                <button type="button" class="btn-system-update btn btn-outline-warning btn-sm fw-bold py-1 px-2 d-flex align-items-center gap-1" style="font-size: 11px; border-radius: 8px;" title="Actualizar código desde GitHub">
                    <i class="system-update-icon bi bi-cloud-arrow-down"></i>
                    <span class="system-update-btn-text">Git Update</span>
                </button>
            @endif
        </div>
    </div>
</div>

<style>
    .spin-fast {
        animation: spin-cloud 1s linear infinite;
    }
    @keyframes spin-cloud {
        100% { transform: rotate(360deg); }
    }
</style>

<script>
(function() {
    if (window._cloudSyncInitialized) return;
    window._cloudSyncInitialized = true;

    function updateStatusUI(data) {
        if (!data) return;
        const pending = parseInt(data.pending_count || 0);
        const lastSync = data.last_sync || 'Nunca';

        if (data.is_cloud) {
            document.querySelectorAll('.cloud-sync-badge').forEach(badge => {
                badge.className = 'cloud-sync-badge badge bg-primary text-white font-monospace fw-bold';
                badge.textContent = '☁️ Nube Central Activa';
            });
            document.querySelectorAll('.cloud-sync-dot').forEach(dot => {
                dot.className = 'cloud-sync-dot spinner-grow spinner-grow-sm text-primary';
            });
            document.querySelectorAll('.cloud-sync-last-text').forEach(el => {
                el.textContent = `Última sinc con planta: ${lastSync}`;
            });
            return;
        }

        document.querySelectorAll('.cloud-sync-last-text').forEach(el => {
            el.textContent = `Última sinc: ${lastSync}`;
        });

        document.querySelectorAll('.cloud-sync-badge').forEach(badge => {
            if (pending > 0) {
                badge.className = 'cloud-sync-badge badge bg-warning text-dark font-monospace fw-bold';
                badge.textContent = `🟡 ${pending} pend. por subir`;
            } else {
                badge.className = 'cloud-sync-badge badge bg-success text-white font-monospace fw-bold';
                badge.textContent = '🟢 Nube al día';
            }
        });

        document.querySelectorAll('.cloud-sync-dot').forEach(dot => {
            if (pending > 0) {
                dot.className = 'cloud-sync-dot spinner-grow spinner-grow-sm text-warning';
            } else {
                dot.className = 'cloud-sync-dot spinner-grow spinner-grow-sm text-success';
            }
        });
    }

    function checkSyncStatus() {
        fetch('{{ route('cloud_sync.status') }}')
            .then(res => res.json())
            .then(data => updateStatusUI(data))
            .catch(() => {
                document.querySelectorAll('.cloud-sync-badge').forEach(badge => {
                    badge.className = 'cloud-sync-badge badge bg-danger text-white font-monospace fw-bold';
                    badge.textContent = '🔴 Sin Conexión';
                });
                document.querySelectorAll('.cloud-sync-dot').forEach(dot => {
                    dot.className = 'cloud-sync-dot spinner-grow spinner-grow-sm text-danger';
                });
                document.querySelectorAll('.cloud-sync-last-text').forEach(el => {
                    el.textContent = 'Modo Local (Autónomo)';
                });
            });
    }

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-cloud-sync-manual');
        if (!btn) return;

        const allButtons = document.querySelectorAll('.btn-cloud-sync-manual');
        allButtons.forEach(b => {
            b.disabled = true;
            b.querySelector('.cloud-sync-icon')?.classList.add('spin-fast');
            const txt = b.querySelector('.cloud-sync-btn-text');
            if (txt) txt.textContent = 'Sincronizando...';
        });

        fetch('{{ route('cloud_sync.manual') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            allButtons.forEach(b => {
                b.disabled = false;
                b.querySelector('.cloud-sync-icon')?.classList.remove('spin-fast');
                const txt = b.querySelector('.cloud-sync-btn-text');
                if (txt) txt.textContent = 'Sincronizar VPS';
            });

            if (data.success) {
                alert(`✅ Sincronización exitosa:\n${data.message}`);
                checkSyncStatus();
            } else {
                alert(`⚠️ ${data.message || 'No se pudo sincronizar con el VPS.'}`);
                checkSyncStatus();
            }
        })
        .catch(err => {
            allButtons.forEach(b => {
                b.disabled = false;
                b.querySelector('.cloud-sync-icon')?.classList.remove('spin-fast');
                const txt = b.querySelector('.cloud-sync-btn-text');
                if (txt) txt.textContent = 'Sincronizar VPS';
            });
            alert('⚠️ No hay conexión a internet o el VPS no respondió. Los datos están seguros en la base de datos local y se sincronizarán al reconectar.');
            checkSyncStatus();
        });
    });

    // Actualización de Sistema desde GitHub (1 Clic)
    document.addEventListener('click', function(e) {
        const updateBtn = e.target.closest('.btn-system-update');
        if (!updateBtn) return;

        if (!confirm('¿Desea actualizar el sistema a la última versión disponible en GitHub (main)? Se aplicarán migraciones y optimizaciones automáticamente.')) {
            return;
        }

        const allUpdateBtns = document.querySelectorAll('.btn-system-update');
        allUpdateBtns.forEach(b => {
            b.disabled = true;
            b.querySelector('.system-update-icon')?.classList.add('spin-fast');
            const txt = b.querySelector('.system-update-btn-text');
            if (txt) txt.textContent = 'Actualizando...';
        });

        fetch('{{ route('system.update') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            allUpdateBtns.forEach(b => {
                b.disabled = false;
                b.querySelector('.system-update-icon')?.classList.remove('spin-fast');
                const txt = b.querySelector('.system-update-btn-text');
                if (txt) txt.textContent = 'Git Update';
            });

            if (data.success) {
                const logs = (data.logs || []).join('\n');
                alert(`✅ ${data.message}\n\nDetalles del proceso:\n${logs}`);
                window.location.reload();
            } else {
                alert(`❌ ${data.message || 'Error al actualizar.'}`);
            }
        })
        .catch(err => {
            allUpdateBtns.forEach(b => {
                b.disabled = false;
                b.querySelector('.system-update-icon')?.classList.remove('spin-fast');
                const txt = b.querySelector('.system-update-btn-text');
                if (txt) txt.textContent = 'Git Update';
            });
            alert('❌ Error de comunicación o tiempo de espera al intentar actualizar el sistema.');
        });
    });

    document.addEventListener('DOMContentLoaded', checkSyncStatus);
    setInterval(checkSyncStatus, 60000);
})();
</script>
