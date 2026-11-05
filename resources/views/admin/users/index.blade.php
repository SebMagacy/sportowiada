<x-admin-layout>
    <h1 class="mb-6 text-2xl font-bold tracking-tight text-gray-950">
        {{ __('users.title') }}
    </h1>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-950">ID</th>
                    <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-950">{{ __('users.name') }}</th>
                    <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-950">{{ __('users.email') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-950">
                        {{ __('users.account_type') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-gray-500">{{ $user->id }}</td>
                        <td class="px-6 py-3 font-medium text-gray-950">{{ $user->name }}</td>
                        <td class="px-6 py-3 text-gray-700">{{ $user->email }}</td>
                        <td class="px-6 py-3 text-gray-700">
                            {{ $user->is_admin ? __('users.administrator') : __('users.user') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                            {{ __('users.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div
            class="flex flex-col gap-4 border-t border-gray-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-gray-600">
                @if ($users->count() > 0)
                    {{ __('pagination.overview', [
                        'first' => $users->firstItem(),
                        'last' => $users->lastItem(),
                        'total' => $users->total(),
                    ]) }}
                @else
                    {{ __('users.empty') }}
                @endif
            </div>

            @if ($users->hasPages())
                <div>
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>

</x-admin-layout>
