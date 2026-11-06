<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - OfflineBridge</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body style="min-height: 100vh; display: grid; place-items: center; padding: var(--space-4); background: var(--bg-body);">
    <main class="card" style="width: min(100%, 440px);">
        <h1 class="card-title">Create a lab account</h1>
        <p class="card-subtitle">Register as a user to access student features.</p>

        @if($errors->any())
            <div class="card" role="alert" style="margin: var(--space-4) 0; border-color: var(--color-danger-text); color: var(--color-danger-text);">
                @foreach($errors->all() as $error)
                    <p style="margin: 0;">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" style="display: flex; flex-direction: column; gap: var(--space-3);">
            @csrf
            <label class="form-label" for="name">Name</label>
            <input class="form-input" id="name" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="255" required autofocus>

            <label class="form-label" for="email">Email</label>
            <input class="form-input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>

            <label class="form-label" for="password">Password</label>
            <input class="form-input" id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>

            <label class="form-label" for="password_confirmation">Confirm password</label>
            <input class="form-input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>

            <button type="submit" class="btn btn-primary">Register</button>
        </form>

        <p style="margin-bottom: 0; text-align: center;">Already registered? <a href="{{ route('login') }}">Sign in</a></p>
    </main>
</body>
</html>
