<x-admin-layout>
    <x-slot:heading>
        <x-page-heading>
            <x-slot:icon>
                <x-icon name="edit" class="size-full" />
            </x-slot:icon>

            {{ __('users.edit_title', ['name' => $user->name]) }}
        </x-page-heading>
    </x-slot:heading>

    <form method="POST"
        action="{{ route('admin.users.update', [
            'user' => $user,
            ...$listParameters,
        ]) }}">

        @csrf
        @method('PATCH')

        <div
            class="grid max-w-3xl grid-cols-1 gap-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm md:grid-cols-2">
            <div>
                <label for="name" class="mb-2 block text-sm font-medium text-gray-700">
                    {{ __('users.name') }}
                </label>

                <input type="text" id="name" name="name"
                    value="{{ is_string(old('name', $user->name)) ? old('name', $user->name) : '' }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2">

                @error('name')
                    <p role="alert" class="mt-2 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-gray-700">
                    {{ __('users.email') }}
                </label>

                <input type="email" id="email" name="email"
                    value="{{ is_string(old('email', $user->email)) ? old('email', $user->email) : '' }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2">

                @error('email')
                    <p role="alert" class="mt-2 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label for="is_admin" class="mb-2 block text-sm font-medium text-gray-700">
                    {{ __('users.account_type') }}
                </label>

                <select id="is_admin" name="is_admin"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2">
                    <option value="0" @selected(old('is_admin', $user->is_admin ? '1' : '0') === '0')>
                        {{ __('users.user') }}
                    </option>
                    <option value="1" @selected(old('is_admin', $user->is_admin ? '1' : '0') === '1')>
                        {{ __('users.administrator') }}
                    </option>
                </select>

                @error('is_admin')
                    <p role="alert" class="mt-2 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>
        </div>
        <div class="mt-6 flex items-center gap-3">
            <button type="submit"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                {{ __('users.save_changes') }}
            </button>

            <a href="{{ route('admin.users.index', $listParameters) }}"
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                {{ __('users.cancel') }}
            </a>
        </div>
    </form>
</x-admin-layout>
