<h1>Rejestracja</h1>

@if ($errors->any())
    <ul>
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

<form method="POST" action="{{ route('register') }}">
    @csrf
    <label for="name">Imię i nazwisko</label>
    <input id="name" name="name" type="text" autocomplete="name" value="{{ old('name') }}" required>

    <label for="email">Adres e-mail</label>
    <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" required>

    <label for="password">Hasło</label>
    <input id="password" name="password" type="password" autocomplete="new-password" required>

    <label for="password_confirmation">Powtórz hasło</label>
    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>

    <button type="submit">Utwórz konto</button>
</form>
