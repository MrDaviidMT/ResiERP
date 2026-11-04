<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'ResiERP') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased min-h-screen flex flex-col items-center justify-center gap-6">
    <h1 class="titulo-pagina">Tu sesión ha expirado. Inicia sesión de nuevo</h1>
    <a href="{{ route('dashboard') }}" class="boton boton-primario">
        Volver al inicio
    </a>
</body>
</html>
