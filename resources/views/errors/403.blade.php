@extends('layouts.app')

@section('title', '403 - Access Restricted')

@section('content')
<section class="card" role="alert" style="max-width: 680px; margin: var(--space-8) auto; border-color: var(--color-danger-text);">
    <div style="display: flex; align-items: center; gap: var(--space-3); margin-bottom: var(--space-4);">
        <span class="badge badge-danger">403 FORBIDDEN</span>
        @auth
            <span class="badge badge-neutral">
                Signed in as: {{ auth()->user()->name }} ({{ strtoupper(auth()->user()->role ?? 'USER') }})
            </span>
        @endauth
    </div>

    <h1 style="margin-top: 0; color: var(--color-danger-text); font-size: var(--font-size-xl);">
        Administrator Access Required
    </h1>

    <p style="color: var(--text-main); line-height: 1.6;">
        {{ $exception?->getMessage() ?: 'You do not have permission to open this page with your current account.' }}
    </p>

    @auth
        @if(!auth()->user()->isAdmin())
            <p style="color: var(--text-muted); font-size: var(--font-size-sm); line-height: 1.5;">
                If you were testing the Admin panel in another tab of the same browser, signing in as a student switched your active browser session. To keep Admin and Student logged in separately in the same browser, open Admin on <code>http://127.0.0.1:8000</code> and Student on <code>http://localhost:8000</code> (or an Incognito window).
            </p>
        @endif
    @endauth

    <div style="display: flex; gap: var(--space-3); flex-wrap: wrap; margin-top: var(--space-5);">
        <a class="btn btn-primary" href="{{ route('dashboard') }}">Go to Dashboard</a>
        @auth
            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                @csrf
                <button type="submit" class="btn btn-secondary">Sign Out &amp; Switch Account</button>
            </form>
        @else
            <a class="btn btn-secondary" href="{{ route('login') }}">Sign In</a>
        @endauth
    </div>
</section>
@endsection
