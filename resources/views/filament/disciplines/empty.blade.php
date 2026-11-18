<x-catalog-empty
    icon="sportowiada-disciplines"
    :heading="__('disciplines.empty.heading')"
    :description="__('disciplines.empty.description')"
>
    @if ($canCreate)
        <x-filament::button
            tag="a"
            :href="$createUrl"
        >
            {{ __('disciplines.create_title') }}
        </x-filament::button>
    @endif
</x-catalog-empty>