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


            <a href="{{ route('admin.users.index') }}" aria-label="Użytkownicy">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="1.5" aria-hidden="true">
                    <circle cx="9" cy="8" r="3" />
                    <path d="M3 20v-2a6 6 0 0 1 12 0v2" />
                    <path d="M17 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5v1" />
                </svg>
                <span class="admin-nav-label">Użytkownicy</span>
            </a>
        </nav>
        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" aria-label="Wyloguj się"
                class="flex w-full cursor-pointer items-center gap-3 rounded-lg p-3 hover:bg-gray-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="1.5" aria-hidden="true" class="shrink-0">
                    <path d="M9 5H5v14h4M9 12h12m-5-5 5 5-5 5" />
                </svg>
                <span class="admin-nav-label">Wyloguj się</span>
            </button>
        </form>
    </aside>

    <main class="flex-1 p-8">
        {{ $slot }}
    </main>

</body>

</html>
