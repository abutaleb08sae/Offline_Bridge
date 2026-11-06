<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in - OfflineBridge</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body style="min-height: 100vh; display: grid; place-items: center; padding: var(--space-4); background: var(--bg-body);">
    <main class="card" style="width: min(100%, 440px);">
        <h1 class="card-title">OfflineBridge sign in</h1>
        <p class="card-subtitle">Sign in with your lab account to access administrator tools.</p>

        @if($errors->any())
            <div class="card" role="alert" style="margin: var(--space-4) 0; border-color: var(--color-danger-text); color: var(--color-danger-text);">
                @foreach($errors->all() as $error)
                    <p style="margin: 0;">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" style="display: flex; flex-direction: column; gap: var(--space-3);">
            @csrf
            <label class="form-label" for="email">Email</label>
            <input class="form-input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>

            <label class="form-label" for="password">Password</label>
            <input class="form-input" id="password" name="password" type="password" autocomplete="current-password" required>

            <label style="display: flex; align-items: center; gap: var(--space-2);">
                <input type="checkbox" name="remember" value="1">
                <span>Remember me</span>
            </label>

            <button type="submit" class="btn btn-primary">Sign in</button>
        </form>
    </main>
</body>
</html>
