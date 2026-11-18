<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Precomputed bcrypt dummy hash used to prevent timing-based username enumeration.
     */
    private const DUMMY_BCRYPT_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('psb.create');
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $credentials = $request->validated();
        $username = strtolower(trim((string) $credentials['username']));
        $password = (string) $credentials['password'];

        $existingUser = User::where('username', $username)->first();
        $targetHash = $existingUser !== null ? $existingUser->password : self::DUMMY_BCRYPT_HASH;
        $passwordMatches = Hash::check($password, $targetHash);

        if ($existingUser === null || ! $passwordMatches) {
            RateLimiter::hit($request->throttleKey(), 60);

            return back()
                ->withInput($request->only('username'))
                ->withErrors([
                    'username' => 'Username atau password yang Anda masukkan salah.',
                ]);
        }

        // Only reveal inactive status after valid credentials are verified (prevents account enumeration).
        if (! $existingUser->is_active) {
            RateLimiter::hit($request->throttleKey(), 60);

            return back()
                ->withInput($request->only('username'))
                ->withErrors([
                    'username' => 'Akun Anda sedang tidak aktif. Silakan hubungi Administrator NOC.',
                ]);
        }

        RateLimiter::clear($request->throttleKey());

        // Default remember-me ON for field technicians on mobile, opt-in only for NOC Administrators.
        $remember = $request->has('remember')
            ? $request->boolean('remember')
            : ! $existingUser->isAdmin();

        Auth::login($existingUser, $remember);
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
