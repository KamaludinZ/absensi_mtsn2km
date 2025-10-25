<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Show the change password form
     */
    public function showChangePasswordForm()
    {
        $user = auth()->user();
        $employee = $user->employee;

        if (!$employee) {
            return redirect()->back()->with('error', 'Data pegawai tidak ditemukan.');
        }

        return view('employee.profile.change-password', compact('employee'));
    }

    /**
     * Update the user's password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ], [
            'current_password.required' => 'Password saat ini harus diisi',
            'current_password.current_password' => 'Password saat ini tidak sesuai',
            'password.required' => 'Password baru harus diisi',
            'password.confirmed' => 'Konfirmasi password tidak sesuai',
            'password.min' => 'Password minimal 6 karakter',
        ]);

        $user = auth()->user();

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Logout user after password change for security
        auth()->logout();

        return redirect()->route('login')->with('success', 'Password berhasil diubah! Silakan login kembali dengan password baru.');
    }
}
