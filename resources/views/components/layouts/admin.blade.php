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
            <x-ui.icon name="menu" class="size-6" />
        </button>

        <nav aria-label="Menu administratora">
            <a href="{{ route('admin.dashboard') }}" aria-label="Pulpit">
                <x-ui.icon name="dashboard" class="size-6" />
                <span class="admin-nav-label">Pulpit</span>
            </a>


            <a href="{{ route('admin.users.index') }}" aria-label="Użytkownicy">
                <x-ui.icon name="users" class="size-6" />
                <span class="admin-nav-label">Użytkownicy</span>
            </a>
            <a href="{{ route('admin.disciplines.index') }}" aria-label="{{ __('disciplines.title') }}">
                <x-ui.icon name="disciplines" class="size-6" />
                <span class="admin-nav-label">{{ __('disciplines.title') }}</span>
            </a>
        </nav>
        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" aria-label="Wyloguj się"
                class="flex w-full cursor-pointer items-center gap-3 rounded-lg p-3 hover:bg-gray-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                <x-ui.icon name="logout" class="size-6" />
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

                <x-ui.icon name="open-in-new" class="size-4" />

                <span class="sr-only">({{ __('dashboard.opens_in_new_tab') }})</span>
            </a>
        </header>

        <main class="p-8">
            {{ $slot }}
        </main>
    </div>

</body>

</html>
