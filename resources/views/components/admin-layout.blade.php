<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel administratora — Sportowiada</title>
    <script src="{{ asset('js/admin-sidebar-init.js') }}"></script>
    @vite(['resources/css/admin.css', 'resources/js/app.js'])
</head>

<body class="flex min-h-screen">
    <aside class="admin-sidebar" id="admin-sidebar">
        <button type="button" id="sidebar-toggle" class="admin-sidebar-toggle" aria-controls="admin-sidebar"
            aria-expanded="true" aria-label="Zwiń panel nawigacyjny">
            ☰
        </button>

        <nav aria-label="Menu administratora">
            <a href="{{ route('admin.dashboard') }}" aria-label="Pulpit">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="1.5" aria-hidden="true">
                    <path d="M3 10.5 12 3l9 7.5M5 9v12h5v-7h4v7h5V9" />
                </svg>
                <span class="admin-nav-label">Pulpit</span>
            </a>
        </nav>
    </aside>
    <main class="flex-1 p-8">
        {{ $slot }}
    </main>
</body>

</html>
