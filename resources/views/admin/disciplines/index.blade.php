<x-layouts.admin>
    <x-slot:heading>
        <x-ui.page-heading>
            <x-slot:icon>
                <x-ui.icon name="disciplines" class="size-full" />
            </x-slot:icon>

            {{ __('disciplines.title') }}
        </x-ui.page-heading>
    </x-slot:heading>
    <x-lists.panel>
        <x-lists.table>
            <x-slot:head>
                <tr>
                    @foreach ($columns as $column => $definition)
                        <x-lists.sortable-column :column="$column" route="admin.disciplines.index" :sortable="$definition['sortable']"
                            :align="$definition['align']">
                            {{ $definition['label'] }}
                        </x-lists.sortable-column>
                    @endforeach
                </tr>
            </x-slot:head>

            @forelse ($disciplines as $discipline)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 font-medium text-gray-950">
                        {{ $discipline->name }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="px-6 py-8 text-center text-gray-500">
                        {{ __('disciplines.empty') }}
                    </td>
                </tr>
            @endforelse
        </x-lists.table>
    </x-lists.panel>
</x-layouts.admin>
