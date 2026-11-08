@props([
    'column',
    'route',
    'sort' => null,
    'direction' => null,
    'sortable' => false,
    'filters' => [],
    'align' => 'left',
])

@php
    $isActive = $sortable && $sort === $column;

    $nextSort = $column;
    $nextDirection = 'asc';

    if ($isActive && $direction === 'asc') {
        $nextDirection = 'desc';
    } elseif ($isActive && $direction === 'desc') {
        $nextSort = null;
        $nextDirection = null;
    }

    $parameters = $filters;

    unset($parameters['page'], $parameters['sort'], $parameters['direction']);

    $parameters['sort'] = $nextSort;
    $parameters['direction'] = $nextDirection;

    $sortUrl = route($route, $parameters);
@endphp

<th scope="col"
    @if ($isActive) aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}" @endif
    {{ $attributes->class([
        'px-6 py-3 font-semibold text-gray-950',
        'text-right' => $align === 'right',
        'text-left' => $align !== 'right',
    ]) }}>
    @if ($sortable)
        <a href="{{ $sortUrl }}"
            class="inline-flex items-center gap-2 rounded focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600">
            {{ $slot }}
            <span class="sr-only">
                @if ($nextDirection === 'asc')
                    {{ __('Sort ascending') }}
                @elseif ($nextDirection === 'desc')
                    {{ __('Sort descending') }}
                @else
                    {{ __('Reset sorting') }}
                @endif
            </span>
            <span aria-hidden="true" class="{{ $isActive ? 'text-gray-700' : 'text-gray-400' }}">
                {{ $isActive ? ($direction === 'asc' ? '↓' : '↑') : '↓↑' }}
            </span>
        </a>
    @else
        {{ $slot }}
    @endif
</th>
