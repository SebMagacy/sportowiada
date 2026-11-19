@props([
    'actions' => [],
])

<div {{ $attributes->class(['catalog-toolbar']) }}>
    <x-filament::actions :actions="$actions" />

    <div id="catalog-bulk-actions" wire:ignore></div>
</div>
