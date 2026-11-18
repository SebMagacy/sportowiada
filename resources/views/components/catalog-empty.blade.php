@props([
    'icon',
    'heading',
    'description',
])

<x-filament::empty-state
    :icon="$icon"
    :heading="$heading"
    :description="$description"
    {{ $attributes }}
>
    @if ($slot->isNotEmpty())
        <x-slot name="footer">
            {{ $slot }}
        </x-slot>
    @endif
</x-filament::empty-state>