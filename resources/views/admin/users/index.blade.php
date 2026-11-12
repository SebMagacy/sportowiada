<x-admin-layout>
    <x-slot:heading>
        <x-page-heading>
            <x-slot:icon>
                <x-icon name="users" class="size-full" />
            </x-slot:icon>

            {{ __('users.title') }}
        </x-page-heading>
    </x-slot:heading>

    @if (session('status'))
        <div role="status" class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    <div data-row-selection class="rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 p-4">
            <form method="GET" action="{{ route('admin.users.index') }}" class="w-full" role="search">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">

                <x-user-filters :account-type="$accountType" :search="$search" :sort="$sort" :direction="$direction"
                    :per-page="$perPage" />

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
        <div data-selection-toolbar hidden>
            <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-6 py-3">
                <p role="status" aria-atomic="true" class="text-sm text-gray-600">
                    {{ __('users.selected_count') }}
                    <span data-selection-count>0</span>
                </p>

                <button type="button" data-clear-selection
                    class="rounded-lg px-3 py-2 text-sm font-semibold text-blue-600 hover:bg-blue-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    {{ __('users.clear_selection') }}
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50">
                    <tr>
                        <th scope="col" class="w-12 px-6 py-3">
                            <input type="checkbox" data-select-page disabled aria-label="{{ __('users.select_page') }}"
                                class="size-4 align-middle accent-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        </th>
                        @foreach ($columns as $column => $definition)
                            <x-sortable-column :column="$column" route="admin.users.index" :sort="$sort"
                                :direction="$direction" :sortable="$definition['sortable']" :filters="[
                                    'search' => $search,
                                    'account_type' => $accountType,
                                    'per_page' => $perPage,
                                ]" :align="$definition['align']">
                                <span @class(['sr-only' => $column === 'actions'])>
                                    {{ $definition['label'] }}
                                </span>
                            </x-sortable-column>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3">
                                <input type="checkbox" data-select-user value="{{ $user->id }}"
                                    aria-label="{{ __('users.select_user', ['name' => $user->name, 'id' => $user->id]) }}"
                                    class="size-4 align-middle accent-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            </td>
                            <td class="px-6 py-3 font-medium text-gray-950">{{ $user->name }}</td>
                            <td class="px-6 py-3 text-gray-700">{{ $user->email }}</td>
                            <td class="px-6 py-3 text-gray-700">
                                {{ $user->is_admin ? __('users.administrator') : __('users.user') }}</td>
                            <td class="px-6 py-3 text-right text-gray-500">{{ $user->id }}</td>
                            <td class="px-6 py-3 text-right">
                                <a href="{{ route('admin.users.edit', [
                                    'user' => $user,
                                    'search' => $search,
                                    'account_type' => $accountType,
                                    'sort' => $sort,
                                    'direction' => $direction,
                                    'per_page' => $perPage,
                                    'page' => $users->currentPage(),
                                ]) }}"
                                    class="inline-flex items-center gap-1.5 font-medium text-blue-600 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125M18 14.25V19.5A2.25 2.25 0 0 1 15.75 21H4.5A2.25 2.25 0 0 1 2.25 18.75V7.5A2.25 2.25 0 0 1 4.5 5.25H9" />
                                    </svg>

                                    {{ __('users.edit') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + 1 }}" class="px-6 py-8 text-center text-gray-500">
                                {{ $search !== '' || $accountType !== 'all' ? __('users.no_results') : __('users.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="grid grid-cols-3 items-center gap-4 border-t border-gray-200 px-6 py-4">
            <div class="text-sm text-gray-600">
                @if ($users->count() > 0)
                    {{ __('pagination.overview', [
                        'first' => $users->firstItem(),
                        'last' => $users->lastItem(),
                        'total' => $users->total(),
                    ]) }}
                @endif
            </div>

            <x-per-page-select id="users-per-page" :action="route('admin.users.index')" :value="$perPage" :options="[10, 25, 50]"
                :label="__('users.per_page')" :hint="__('users.per_page_hint')" :parameters="[
                    'search' => $search,
                    'account_type' => $accountType,
                    'sort' => $sort,
                    'direction' => $direction,
                ]" class="justify-self-center" />

            <div class="justify-self-end">
                @if ($users->hasPages())
                    {{ $users->links() }}
                @endif
            </div>
        </div>

    </div>

</x-admin-layout>
