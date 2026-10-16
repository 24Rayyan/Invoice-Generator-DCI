<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->intended('/dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // 1. Validasi input username
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ]);

        // 2. Attempt Login dengan username
        if (Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            
            if (!Auth::user()->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'username' => 'Akun Anda dinonaktifkan. Silakan hubungi Super Admin.',
                ]);
            }

            $request->session()->regenerate();

            ActivityLogger::log(
                ActivityLog::ACTION_LOGIN,
                'Autentikasi',
                "User " . Auth::user()->username . " berhasil login ke sistem."
            );

            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'username' => 'Username atau password yang dimasukkan salah.',
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            ActivityLogger::log(
                ActivityLog::ACTION_LOGOUT,
                'Autentikasi',
                "User {$user->username} logout dari sistem.",
                null,
                $user->id,
                $user->username
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}