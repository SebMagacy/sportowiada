@props(['id', 'action', 'value', 'options', 'label', 'hint', 'parameters' => []])

<form method="GET" action="{{ $action }}" data-per-page-select
    {{ $attributes->class(['inline-flex items-center rounded-lg border border-gray-300 bg-white text-sm']) }}>
    @foreach ($parameters as $name => $parameter)
        <input type="hidden" name="{{ $name }}" value="{{ $parameter }}">
    @endforeach

    <label for="{{ $id }}" class="px-3 py-2 text-gray-600">
        {{ $label }}
    </label>

    <select id="{{ $id }}" name="per_page" aria-describedby="{{ $id }}-hint"
        class="rounded-r-lg border-l border-gray-300 bg-white px-3 py-2 text-gray-950 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
        @foreach ($options as $option)
            <option value="{{ $option }}" @selected((string) $value === (string) $option)>
                {{ $option }}
            </option>
        @endforeach
    </select>

    <span id="{{ $id }}-hint" class="sr-only">
        {{ $hint }}
    </span>
</form>
