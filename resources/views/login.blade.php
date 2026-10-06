<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JSBolsas Pro - Iniciar Sesión</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon-jsbolsas.png') }}">
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon-jsbolsas.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #0b132b;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #1c2541;
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.08);
        }
        .btn-custom {
            background: #0284c7;
            color: #fff;
            font-weight: 700;
            border-radius: 10px;
            padding: 12px;
        }
        .btn-custom:hover { background: #0369a1; color: #fff; }
        .form-control {
            background: #0f172a;
            border: 1px solid #334155;
            color: #fff;
            border-radius: 10px;
            padding: 12px;
        }
        .form-control:focus {
            background: #0f172a;
            color: #fff;
            border-color: #38bdf8;
            box-shadow: 0 0 0 0.25rem rgba(56,189,248,0.25);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <h2 class="fw-bold text-white">🏭 JSBolsas Pro</h2>
            <p class="text-white-50 small">Administración de Fábrica • Plásticos M&F Steel</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger py-2 small">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ url('/login') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label text-white-50 small">Correo Electrónico</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus placeholder="admin@plasticosmyf.com">
            </div>

            <div class="mb-3">
                <label class="form-label text-white-50 small">Contraseña</label>
                <input type="password" name="password" class="form-control" required placeholder="••••••••">
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label text-white-50 small" for="remember">Recordarme</label>
            </div>

            <button type="submit" class="btn btn-custom w-100 mb-3">INGRESAR AL PANEL</button>
        </form>

        <div class="text-center text-white-50 small mt-4">
            <a href="/JSBolsas.apk" class="btn btn-outline-info btn-sm w-100 mb-2 fw-bold"><i class="bi bi-android2 me-1"></i> 📱 Descargar APK para Android <span class="badge bg-info text-dark">{{ $systemVersion ?? 'v2.3.1' }}</span></a><br>Servidor VPS en la Nube • Cloud VPS 4
        </div>
    </div>
</body>
</html>
