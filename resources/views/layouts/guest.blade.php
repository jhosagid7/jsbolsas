<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $systemTitle ?? config('app.name', 'JSBolsas Pro') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
</head>

<body class="font-sans text-gray-900 antialiased bg-slate-900 text-slate-100">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-900">
        <div class="text-center mb-2">
            <h1 class="text-2xl font-bold text-sky-400">JSBolsas Pro</h1>
            <p class="text-xs text-slate-400">Sistema de Control de Fábrica</p>
        </div>

        <div class="w-full sm:max-w-md mt-4 px-6 py-6 bg-slate-800 border border-slate-700 shadow-xl overflow-hidden sm:rounded-xl text-slate-200">
            {{ $slot }}
        </div>
    </div>
</body>

</html>
