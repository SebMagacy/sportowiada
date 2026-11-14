<x-layouts.admin>
    <x-slot:heading>
        <x-ui.page-heading>
            <x-slot:icon>
                <x-ui.icon name="add-circle" class="size-full" />
            </x-slot:icon>

            {{ __('disciplines.create_title') }}
        </x-ui.page-heading>
    </x-slot:heading>
    <form method="POST" action="{{ route('admin.disciplines.store') }}" data-discipline-form>
        @csrf
        <div
            class="grid max-w-3xl grid-cols-1 gap-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm md:grid-cols-2">
            <div>
                <label for="name" class="mb-2 block text-sm font-medium text-gray-700">
                    {{ __('disciplines.name') }}
                </label>

                <input type="text" id="name" name="name" maxlength="255" required
                    value="{{ is_string(old('name')) ? old('name') : '' }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
            </div>

            <div>
                <label for="alias" class="mb-2 block text-sm font-medium text-gray-700">
                    {{ __('disciplines.alias') }}
                </label>

                <input type="text" id="alias" name="alias" maxlength="255" required
                    value="{{ is_string(old('alias')) ? old('alias') : '' }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                @error('alias')
                    <p role="alert" class="mt-2 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>
        </div>
        <div class="mt-6 flex flex-wrap items-center gap-3">
            <button type="submit"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                {{ __('disciplines.save') }}
            </button>
            <a href="{{ route('admin.disciplines.index') }}"
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                {{ __('disciplines.cancel') }}
            </a>
        </div>
    </form>
</x-layouts.admin>
