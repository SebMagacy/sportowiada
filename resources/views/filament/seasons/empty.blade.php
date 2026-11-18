<x-catalog-empty
    :icon="\Filament\Support\Icons\Heroicon::OutlinedCalendarDays"
    :heading="__('seasons.empty.heading')"
    :description="__('seasons.empty.description')"
>
    @if ($canCreate)
        <x-filament::button
            tag="a"
            :href="$createUrl"
        >
            {{ __('seasons.create_title') }}
        </x-filament::button>
    @endif
</x-catalog-empty>