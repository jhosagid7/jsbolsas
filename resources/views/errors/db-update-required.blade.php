<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Actualización Requerida | JSBolsas Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0b132b;
            color: #f1f5f9;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            text-align: center;
            background: #1c2541;
            padding: 40px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            max-width: 480px;
            width: 90%;
        }
        h1 {
            color: #ef4444;
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 12px;
        }
        p {
            font-size: 14px;
            color: #94a3b8;
            margin-bottom: 24px;
            line-height: 1.6;
        }
        .btn {
            background-color: #0284c7;
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn:hover {
            background-color: #0369a1;
            transform: translateY(-1px);
        }
        .icon-box {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(239, 68, 68, 0.12);
            color: #ef4444;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon-box">
            <i class="bi bi-database-fill-gear"></i>
        </div>
        <h1>Actualización de Base de Datos</h1>
        <p>
            El sistema detectó cambios en la estructura de datos que requieren sincronización. Por favor recarga o ejecuta la actualización para continuar.
        </p>
        <a href="javascript:location.reload()" class="btn">
            <i class="bi bi-arrow-clockwise"></i> Recargar Página
        </a>
    </div>
</body>
</html>
