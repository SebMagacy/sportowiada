<x-admin-layout>
    <h1 class="mb-6 text-2xl font-bold tracking-tight text-gray-950">
        Użytkownicy
    </h1>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-950">ID</th>
                    <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-950">Imię</th>
                    <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-950">E-mail</th>
                    <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-950">Typ konta</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-gray-500">{{ $user->id }}</td>
                        <td class="px-6 py-3 font-medium text-gray-950">{{ $user->name }}</td>
                        <td class="px-6 py-3 text-gray-700">{{ $user->email }}</td>
                        <td class="px-6 py-3 text-gray-700">{{ $user->is_admin ? 'Administrator' : 'Użytkownik' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                            Brak użytkowników.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="border-t border-gray-200 px-6 py-4 text-sm text-gray-600">
            @if ($users->total() > 0)
                Wyświetlono {{ $users->firstItem() }}–{{ $users->lastItem() }}
                z {{ $users->total() }} użytkowników.
            @else
                Brak użytkowników.
            @endif
        </div>
        @if ($users->hasPages())
            <div class="border-t border-gray-200 px-6 py-4">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</x-admin-layout>
