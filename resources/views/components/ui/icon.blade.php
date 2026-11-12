@props(['name'])

@php
    $iconPath = match ($name) {
        'edit' => resource_path('svg/edit.svg'),
        'users' => resource_path('svg/users.svg'),
        'dashboard' => resource_path('svg/dashboard.svg'),
        'disciplines' => resource_path('svg/disciplines.svg'),
        'logout' => resource_path('svg/logout.svg'),
        'open-in-new' => resource_path('svg/open-in-new.svg'),
        'menu' => resource_path('svg/menu.svg'),
        default => throw new InvalidArgumentException('Nieznana ikona.'),
    };

    $svg = Illuminate\Support\Facades\File::get($iconPath);
@endphp

<span aria-hidden="true"
    {{ $attributes->class(['inline-flex shrink-0 [&>svg]:size-full']) }}>
    {!! $svg !!}
</span>