@props(['accountType', 'search', 'sort', 'direction', 'perPage'])

<div id="users-filters-panel" class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-4" hidden>
    <div class="w-full sm:max-w-70">
        <label for="users-account-type" class="mb-1 block text-sm font-medium text-gray-700">
            {{ __('users.account_type') }}
        </label>

        <select id="users-account-type" name="account_type"
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            <option value="all" @selected($accountType === 'all')>
                {{ __('users.all_accounts') }}
            </option>
            <option value="admin" @selected($accountType === 'admin')>
                {{ __('users.administrators') }}
            </option>
            <option value="user" @selected($accountType === 'user')>
                {{ __('users.regular_users') }}
            </option>
        </select>
    </div>
    <button type="submit"
        class="mt-3 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
        {{ __('users.apply_filters') }}
    </button>
    <a href="{{ route('admin.users.index', [
        'search' => $search,
        'sort' => $sort,
        'direction' => $direction,
        'per_page' => $perPage,
    ]) }}"
        class="ml-3 inline-block rounded px-2 py-1 text-sm font-semibold text-red-600 transition duration-150 hover:text-red-700 hover:blur-[0.3px] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
        {{ __('users.clear_filters') }}
    </a>
</div>
