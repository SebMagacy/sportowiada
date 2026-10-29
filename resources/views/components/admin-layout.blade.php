<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel administratora — Sportowiada</title>
    @vite(['resources/css/admin.css', 'resources/js/app.js'])
</head>

<body class="admin-layout">
    <aside class="admin-sidebar">
        <h2>Sportowiada</h2>

        <nav aria-label="Menu administratora">
            <a href="{{ route('admin.dashboard') }}">Pulpit</a>
        </nav>
    </aside>
    <main class="admin-content">
        {{ $slot }}
    </main>
</body>

</html>
