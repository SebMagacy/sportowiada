<x-admin-layout>
    <h1 class="mb-6 text-2xl font-bold tracking-tight text-gray-950">
        {{ __('users.title') }}
    </h1>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex justify-end border-b border-gray-200 p-4">
            <form method="GET" action="{{ route('admin.users.index') }}" class="w-full sm:w-80" role="search">
                <label for="users-search" class="sr-only">
                    {{ __('users.search_label') }}
                </label>

                <div class="relative">
                    <svg class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-gray-400"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" aria-hidden="true">
                        <circle cx="10.5" cy="10.5" r="6.75" />
                        <path stroke-linecap="round" d="m16 16 4.25 4.25" />
                    </svg>

                    <input type="search" id="users-search" name="search" value="{{ $search }}"
                        placeholder="{{ __('users.search_placeholder') }}"
                        class="w-full rounded-lg border border-gray-300 bg-white py-2 pr-3 pl-10 text-sm text-gray-950 placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </form>
        </div>
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-950">{{ __('users.name') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-950">{{ __('users.email') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left font-semibold text-gray-950">
                        {{ __('users.account_type') }}</th>
                    <th scope="col" class="px-6 py-3 text-right font-semibold text-gray-950">ID</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 font-medium text-gray-950">{{ $user->name }}</td>
                        <td class="px-6 py-3 text-gray-700">{{ $user->email }}</td>
                        <td class="px-6 py-3 text-gray-700">
                            {{ $user->is_admin ? __('users.administrator') : __('users.user') }}</td>
                        <td class="px-6 py-3 text-right text-gray-500">{{ $user->id }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                            {{ $search !== '' ? __('users.no_results') : __('users.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($users->count() > 0)
            <div
                class="flex flex-col gap-4 border-t border-gray-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm text-gray-600">
                    {{ __('pagination.overview', [
                        'first' => $users->firstItem(),
                        'last' => $users->lastItem(),
                        'total' => $users->total(),
                    ]) }}
                </div>

                @if ($users->hasPages())
                    <div>
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        @endif

    </div>

</x-admin-layout>
