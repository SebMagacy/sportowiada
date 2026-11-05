<h1>Logowanie</h1>

@error('email')
    <p>{{ $message }}</p>
@enderror

<form method="POST" action="{{ route('login') }}">
    @csrf
    <label for="email">Adres e-mail</label>
    <input id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" required>

    <label for="password">Hasło</label>
    <input id="password" name="password" type="password" autocomplete="current-password" required>

    <button type="submit">Zaloguj się</button>
</form>
