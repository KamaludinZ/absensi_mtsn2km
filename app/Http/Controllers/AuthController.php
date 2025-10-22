<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SecurityService;

class AuthController extends Controller
{
    protected $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $username = $credentials['username'];

        // Check if username is blocked due to too many failed attempts
        if ($this->securityService->isUsernameBlocked($username)) {
            $failedCount = $this->securityService->getFailedAttemptsCount($username);

            return back()->withErrors([
                'username' => "Akun diblokir sementara karena terlalu banyak percobaan login gagal ($failedCount kali). Silakan coba lagi dalam 15 menit.",
            ])->onlyInput('username');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Record successful login
            $this->securityService->recordLoginAttempt($username, true);
            $this->securityService->logEvent(
                'login_success',
                "User {$user->username} berhasil login",
                'info',
                ['user_id' => $user->id, 'role' => $user->role->name]
            );

            // Redirect based on role
            if ($user->isAdmin()) {
                return redirect()->intended(route('admin.dashboard'));
            } elseif ($user->isEmployee()) {
                return redirect()->intended(route('employee.dashboard'));
            } elseif ($user->isMonitoring()) {
                return redirect()->intended(route('monitoring.dashboard'));
            }

            return redirect()->intended('/');
        }

        // Record failed login attempt
        $this->securityService->recordLoginAttempt($username, false, 'Invalid credentials');

        return back()->withErrors([
            'username' => 'Username atau password salah.',
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        // Log logout event
        if ($user) {
            $this->securityService->logEvent(
                'logout',
                "User {$user->username} melakukan logout",
                'info',
                ['user_id' => $user->id]
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
