@extends('layouts.app')

@section('title', 'Problem Access Blocked')

@section('content')
<section class="card" role="alert" style="max-width: 720px; margin: var(--space-8) auto; border-color: var(--color-danger-text);">
    <h1 style="margin-top: 0; color: var(--color-danger-text);">Access Blocked due to Copy-Paste Policy Violation</h1>
    <p>Access to this problem has been blocked for your account after a restricted copy, paste, or cut attempt.</p>
    <p>Please contact an administrator if you believe this block should be removed.</p>
    <a class="btn btn-primary" href="{{ route('judge.index') }}">Return to problems</a>
</section>
@endsection
