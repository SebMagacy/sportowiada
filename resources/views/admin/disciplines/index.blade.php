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
        <p class="px-6 py-3">Miejsce na listę dyscyplin</p>
    </x-lists.panel>
</x-layouts.admin>
