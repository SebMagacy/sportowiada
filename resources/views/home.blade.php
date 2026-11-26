<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sportowiada</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-950 p-6 font-sans text-zinc-100">
    <div id="app"></div>
    @auth
        <p>Zalogowano jako {{ auth()->user()->name }}</p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Wyloguj się</button>
        </form>
    @else
        <a href="{{ route('filament.panel.auth.login') }}">Zaloguj się</a>
        <a href="{{ route('filament.panel.auth.register') }}">Zarejestruj się</a>
    @endauth
</body>
</html>
