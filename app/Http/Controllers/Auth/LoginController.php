<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login', [
            'activeUser' => Auth::user(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $matchedUser */
        $matchedUser = User::where('email', $credentials['email'])->first();

        if (! $matchedUser || ! Hash::check($credentials['password'], (string) $matchedUser->password)) {
            return back()
                ->withErrors(['email' => 'The provided credentials do not match our records.'])
                ->onlyInput('email');
        }

        // If a different account was signed in on this browser host, cleanly terminate the old session first
        // so Admin and Student sessions never silently collide or hijack each other.
        if (Auth::check() && Auth::id() !== $matchedUser->id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        Auth::login($matchedUser, $request->boolean('remember'));

        $matchedUser->update([
            'last_login_at' => now(),
            'status' => 'active',
        ]);

        $request->session()->regenerate();
        session()->forget('url.intended');

        ActivityLogger::log('User Login', "User {$matchedUser->name} (" . ucfirst((string) $matchedUser->role) . ") connected from workstation", 'info', $request);

        return redirect()->route('dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        if ($user = Auth::user()) {
            ActivityLogger::log('User Logout', "User {$user->name} signed out", 'info', $request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
