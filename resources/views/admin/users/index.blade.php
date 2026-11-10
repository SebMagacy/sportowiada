<x-admin-layout>
    <h1 class="mb-6 text-2xl font-bold tracking-tight text-gray-950">
        {{ __('users.title') }}
    </h1>

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 p-4">
            <form method="GET" action="{{ route('admin.users.index') }}" class="w-full" role="search">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">

                <x-user-filters :account-type="$accountType" :search="$search" :sort="$sort" :direction="$direction" />

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <button type="button" id="users-filters-toggle" aria-controls="users-filters-panel"
                        aria-expanded="false"
                        class="inline-flex shrink-0 items-center gap-2 rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 self-start sm:self-auto">
                        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 4.5h18l-7.5 9v6l-3-1.5v-4.5L3 4.5Z" />
                        </svg>

                        @if ($activeFiltersCount > 0)
                            <span>({{ $activeFiltersCount }})</span>
                        @endif

                        {{ __('users.filters') }}
                    </button>

                    <x-search-input id="users-search" name="search" :value="$search" :label="__('users.search_label')"
                        :placeholder="__('users.search_placeholder')" :clear-url="$clearSearchUrl" :clear-label="__('users.clear_search')" class="w-full sm:flex-1 sm:max-w-xs" />
                </div>

            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        @foreach ($columns as $column => $definition)
                            <x-sortable-column :column="$column" route="admin.users.index" :sort="$sort"
                                :direction="$direction" :sortable="$definition['sortable']" :filters="['search' => $search, 'account_type' => $accountType]" :align="$definition['align']">
                                {{ $definition['label'] }}
                            </x-sortable-column>
                        @endforeach
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
                                {{ $search !== '' || $accountType !== 'all' ? __('users.no_results') : __('users.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

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
