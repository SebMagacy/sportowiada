<h1 {{ $attributes->class(['mb-6 flex items-end gap-3 text-2xl font-bold tracking-tight text-gray-950']) }}>
    @isset($icon)
        <span aria-hidden="true" class="mt-1 flex size-10 shrink-0 items-center justify-center">
            {{ $icon }}
        </span>
    @endisset

    <span class="min-w-0 wrap-anywhere">
        {{ $slot }}
    </span>
</h1>