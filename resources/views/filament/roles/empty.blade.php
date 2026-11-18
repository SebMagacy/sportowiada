<x-catalog-empty
    icon="heroicon-o-shield-check"
    :heading="__('roles.empty.heading')"
    :description="__('roles.empty.description')"
>
    @if ($canCreate)
        <x-filament::button
            tag="a"
            :href="$createUrl"
        >
            {{ __('roles.create_title') }}
        </x-filament::button>
    @endif
</x-catalog-empty>