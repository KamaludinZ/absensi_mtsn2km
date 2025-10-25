<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Show the change password form for monitoring users
     */
    public function showChangePasswordForm()
    {
        $user = Auth::user();
        return view('monitoring.profile.change-password', compact('user'));
    }

    /**
     * Update the password for monitoring users
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Password saat ini harus diisi',
            'current_password.current_password' => 'Password saat ini tidak sesuai',
            'password.required' => 'Password baru harus diisi',
            'password.confirmed' => 'Konfirmasi password tidak sesuai',
            'password.min' => 'Password minimal 8 karakter',
        ]);

        $user = Auth::user();

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Logout user after password change for security
        Auth::logout();

        return redirect()->route('login')->with('success', 'Password berhasil diubah! Silakan login kembali dengan password baru.');
    }
}