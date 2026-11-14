@if (session('status'))
    <div role="status"
        {{ $attributes->class(['mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800']) }}>
        {{ session('status') }}
    </div>
@endif