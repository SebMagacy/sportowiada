<span class="rogacki-brand">
    <img
        src="{{ asset('rogacki-icon.png') }}"
        alt="{{ filament()->getBrandName() }}"
        width="40"
        height="40"
    >

    @unless (\Livewire\Livewire::current() instanceof \Filament\Pages\SimplePage)
        <span
            aria-hidden="true"
            x-show="$store.sidebar?.isOpen ?? true"
        >ogacki</span>
    @endunless
</span>