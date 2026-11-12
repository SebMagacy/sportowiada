<div class="overflow-x-auto">
    <table {{ $attributes->class(['w-full text-left text-sm']) }}>
        <thead class="border-b border-gray-200 bg-gray-50">
            {{ $head }}
        </thead>

        <tbody class="divide-y divide-gray-200">
            {{ $slot }}
        </tbody>
    </table>
</div>