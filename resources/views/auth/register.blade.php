<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - OfflineBridge</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script>
        (function() {
            const savedTheme = localStorage.getItem('offlinebridge_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    <style>
        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: var(--space-4);
        }
    </style>
</head>
<body>
    <main class="card" style="width: min(100%, 480px); padding: var(--space-8);">
        <div style="display: flex; align-items: center; gap: var(--space-3); margin-bottom: var(--space-4);">
            <div style="width: 44px; height: 44px; background: linear-gradient(135deg, var(--primary), #147A7D); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; box-shadow: 0 4px 14px rgba(13, 92, 94, 0.35);">
                OB
            </div>
            <div>
                <h1 class="card-title" style="margin-bottom: 2px;">Create Account</h1>
                <span style="font-size: var(--font-size-xs); color: var(--text-muted); font-weight: 500;">OfflineBridge Lab Registration</span>
            </div>
        </div>

        <p class="card-subtitle" style="margin-bottom: var(--space-5);">Register as a user to access student lab tasks and local submissions.</p>

        @if($errors->any())
            <div class="card" role="alert" style="margin: 0 0 var(--space-4); border-color: rgba(239, 68, 68, 0.4); background: var(--color-danger-bg); color: var(--color-danger-text); padding: var(--space-3) var(--space-4);">
                @foreach($errors->all() as $error)
                    <p style="margin: 0; font-size: var(--font-size-xs); font-weight: 600;">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" style="display: flex; flex-direction: column; gap: var(--space-4);">
            @csrf
            <div>
                <label class="form-label" for="name">Full Name</label>
                <input class="form-input" id="name" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="255" required autofocus placeholder="John Doe">
            </div>

            <div>
                <label class="form-label" for="email">Email Address</label>
                <input class="form-input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required placeholder="student@example.com">
            </div>

            <div>
                <label class="form-label" for="password">Password</label>
                <input class="form-input" id="password" name="password" type="password" autocomplete="new-password" minlength="8" required placeholder="At least 8 characters">
            </div>

            <div>
                <label class="form-label" for="password_confirmation">Confirm Password</label>
                <input class="form-input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required placeholder="Re-enter password">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; margin-top: var(--space-2);">
                Create Account
            </button>
        </form>

        <p style="margin-top: var(--space-5); margin-bottom: 0; text-align: center; font-size: var(--font-size-xs); color: var(--text-muted);">
            Already registered? <a href="{{ route('login') }}" style="color: var(--primary); font-weight: 600;">Sign in here</a>
        </p>
    </main>
</body>
</html>
