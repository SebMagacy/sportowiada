@props(['id', 'name', 'value' => '', 'label', 'placeholder' => '', 'clearUrl', 'clearLabel'])

<div data-search-input {{ $attributes->class(['relative min-w-0']) }}>
    <label for="{{ $id }}" class="sr-only">
        {{ $label }}
    </label>

    <svg class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-gray-400"
        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
        aria-hidden="true">
        <circle cx="10.5" cy="10.5" r="6.75" />
        <path stroke-linecap="round" d="m16 16 4.25 4.25" />
    </svg>

    <input type="search" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        class="w-full rounded-lg border border-gray-300 bg-white py-2 pr-10 pl-10 text-sm text-gray-950 placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:outline-none [&::-webkit-search-cancel-button]:hidden">

    <button type="button" data-clear-search data-clear-url="{{ $clearUrl }}" aria-label="{{ $clearLabel }}"
        @if ($value === '') hidden @endif
        class="absolute top-1/2 right-2 -translate-y-1/2 rounded p-1 text-gray-500 hover:text-gray-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
        <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
            stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" d="m6 6 12 12M18 6 6 18" />
        </svg>
    </button>
</div>
