<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('psb.create');
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $username = strtolower(trim((string) $credentials['username']));

        $existingUser = User::where('username', $username)->first();

        if ($existingUser !== null && ! $existingUser->is_active) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors([
                    'username' => 'Akun Anda sedang tidak aktif. Silakan hubungi Administrator NOC.',
                ]);
        }

        // Force remember-me token so field technicians remain signed in on mobile devices.
        $attemptSuccess = Auth::attempt(
            [
                'username' => $username,
                'password' => $credentials['password'],
                'is_active' => true,
            ],
            true
        );

        if (! $attemptSuccess) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors([
                    'username' => 'Username atau password yang Anda masukkan salah.',
                ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('psb.create'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
