<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $systemTitle ?? config('app.name', 'JSBolsas Pro') }} - Acceso</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon-jsbolsas.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --bg-main: #0b132b;
            --bg-card: #1c2541;
            --accent: #0284c7;
            --accent-hover: #0369a1;
            --border-color: rgba(255, 255, 255, 0.08);
        }
        body {
            background-color: var(--bg-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .login-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5);
            max-width: 440px;
            width: 100%;
            padding: 36px;
        }
        .brand-badge {
            background: rgba(2, 132, 199, 0.15);
            color: #38bdf8;
            border: 1px solid rgba(2, 132, 199, 0.3);
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            padding: 4px 10px;
            display: inline-block;
        }
        .form-control {
            background-color: #0b132b;
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #f8fafc;
            border-radius: 10px;
            padding: 12px 16px;
        }
        .form-control:focus {
            background-color: #0b132b;
            border-color: var(--accent);
            color: #fff;
            box-shadow: 0 0 0 0.25rem rgba(2, 132, 199, 0.25);
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            border: none;
            color: #fff;
            font-weight: 700;
            border-radius: 10px;
            padding: 12px 20px;
            width: 100%;
            transition: all 0.2s ease;
        }
        .btn-primary-custom:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <div class="mb-2">
                <i class="bi bi-box-seam-fill" style="font-size: 2.5rem; color: #38bdf8;"></i>
            </div>
            <span class="brand-badge mb-2">CONTROL INDUSTRIAL</span>
            <h2 class="fw-bold mb-1" style="color: #f8fafc;">JSBolsas Pro</h2>
            <p class="text-muted small">Plataforma de Fabricación de Bolsas & Extrusión</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger py-2 px-3 small border-0 mb-3" style="background-color: rgba(239, 68, 68, 0.2); color: #fca5a5;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label small fw-semibold text-slate-300">Correo Electrónico</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-envelope"></i></span>
                    <input id="email" name="email" type="email" class="form-control" placeholder="usuario@fabrica.com" value="{{ old('email') }}" required autofocus autocomplete="email">
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label small fw-semibold text-slate-300">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="bi bi-lock"></i></span>
                    <input id="password" name="password" type="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label small text-secondary" for="remember">Recordarme</label>
                </div>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="small text-decoration-none" style="color: #38bdf8;">¿Olvidó contraseña?</a>
                @endif
            </div>

            <button type="submit" class="btn btn-primary-custom">
                <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
            </button>
        </form>

        <div class="text-center mt-4 pt-3 border-top border-secondary border-opacity-25">
            <span class="small text-muted">{{ $systemVersion ?? 'v1.0.0' }} &bull; JSBolsas Pro</span>
        </div>
    </div>
</body>
</html>