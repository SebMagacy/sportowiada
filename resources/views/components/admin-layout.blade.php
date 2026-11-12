<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel administratora — Sportowiada</title>
    <script src="{{ asset('js/admin-sidebar-init.js') }}"></script>
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
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

    <div class="min-w-0 flex-1">
        <header class="flex min-h-16 items-center gap-4 border-b border-gray-200 bg-white px-4 py-3 sm:px-8">
            <div class="min-w-0 flex-1">
                @isset($heading)
                    {{ $heading }}
                @endisset
            </div>
            <a href="{{ route('home') }}" target="_blank" rel="noopener"
                class="inline-flex w-24 shrink-0 sm:w-auto items-center gap-2 rounded-lg px-2 py-2 text-sm font-medium text-blue-600 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                <span>{{ __('dashboard.view_site') }}</span>

                <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M13.5 6H6A2.25 2.25 0 0 0 3.75 8.25v9A2.25 2.25 0 0 0 6 19.5h9a2.25 2.25 0 0 0 2.25-2.25V9.75M8.25 15 20.25 3m0 0H15m5.25-1.5V8.25" />
                </svg>

                <span class="sr-only">({{ __('dashboard.opens_in_new_tab') }})</span>
            </a>
        </header>

        <main class="p-8">
            {{ $slot }}
        </main>
    </div>

</body>

</html>
