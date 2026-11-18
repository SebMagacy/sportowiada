<x-catalog-empty
    :icon="\Filament\Support\Icons\Heroicon::OutlinedTrophy"
    :heading="__('leagues.empty.heading')"
    :description="__('leagues.empty.description')"
>
    @if ($canCreate)
        <x-filament::button
            tag="a"
            :href="$createUrl"
        >
            {{ __('leagues.create') }}
        </x-filament::button>
    @endif
</x-catalog-empty>