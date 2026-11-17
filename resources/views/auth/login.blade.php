<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in - OfflineBridge</title>
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
            background:
                radial-gradient(circle at 12% 14%, rgba(99, 102, 241, 0.25) 0%, transparent 45%),
                radial-gradient(circle at 88% 12%, rgba(168, 85, 247, 0.22) 0%, transparent 45%),
                radial-gradient(circle at 50% 50%, rgba(6, 182, 212, 0.18) 0%, transparent 50%),
                radial-gradient(circle at 14% 84%, rgba(244, 63, 94, 0.16) 0%, transparent 45%),
                radial-gradient(circle at 86% 86%, rgba(16, 185, 129, 0.20) 0%, transparent 45%),
                #edf2f7;
            background-attachment: fixed;
        }
        [data-theme="dark"] body {
            background:
                radial-gradient(circle at 12% 14%, rgba(99, 102, 241, 0.38) 0%, transparent 45%),
                radial-gradient(circle at 88% 12%, rgba(168, 85, 247, 0.32) 0%, transparent 45%),
                radial-gradient(circle at 50% 50%, rgba(6, 182, 212, 0.24) 0%, transparent 50%),
                radial-gradient(circle at 14% 84%, rgba(244, 63, 94, 0.22) 0%, transparent 45%),
                radial-gradient(circle at 86% 86%, rgba(16, 185, 129, 0.26) 0%, transparent 45%),
                #060812;
            background-attachment: fixed;
        }
    </style>
</head>
<body>
    <main class="card" style="width: min(100%, 450px); padding: var(--space-8);">
        <div style="display: flex; align-items: center; gap: var(--space-3); margin-bottom: var(--space-4);">
            <div style="width: 44px; height: 44px; background: linear-gradient(135deg, #2563eb, #3b82f6); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 800; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);">
                OB
            </div>
            <div>
                <h1 class="card-title" style="margin-bottom: 2px;">OfflineBridge</h1>
                <span style="font-size: var(--font-size-xs); color: var(--text-muted); font-weight: 500;">Master Control Panel Sign In</span>
            </div>
        </div>

        <p class="card-subtitle" style="margin-bottom: var(--space-5);">Authenticate with your credentials to access the air-gapped lab controller.</p>

        @if($errors->any())
            <div class="card" role="alert" style="margin: 0 0 var(--space-4); border-color: rgba(239, 68, 68, 0.4); background: var(--color-danger-bg); color: var(--color-danger-text); padding: var(--space-3) var(--space-4);">
                @foreach($errors->all() as $error)
                    <p style="margin: 0; font-size: var(--font-size-xs); font-weight: 600;">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" style="display: flex; flex-direction: column; gap: var(--space-4);">
            @csrf
            <div>
                <label class="form-label" for="email">Email Address</label>
                <input class="form-input" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus placeholder="admin@example.com">
            </div>

            <div>
                <label class="form-label" for="password">Password</label>
                <input class="form-input" id="password" name="password" type="password" autocomplete="current-password" required placeholder="••••••••">
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; font-size: var(--font-size-xs);">
                <label style="display: flex; align-items: center; gap: var(--space-2); cursor: pointer; color: var(--text-muted);">
                    <input type="checkbox" name="remember" value="1" style="accent-color: var(--primary);">
                    <span>Remember me</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; margin-top: var(--space-2);">
                Sign In to Master
            </button>
        </form>

        <p style="margin-top: var(--space-5); margin-bottom: 0; text-align: center; font-size: var(--font-size-xs); color: var(--text-muted);">
            Don't have an account? <a href="{{ route('register') }}" style="color: var(--primary); font-weight: 600;">Register here</a>
        </p>
    </main>
</body>
</html>
