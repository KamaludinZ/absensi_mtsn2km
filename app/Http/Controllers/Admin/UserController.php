<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        // Authorize access - only admin or monitoring can view all users
        $userRole = auth()->user()->role->name;
        if ($userRole !== 'admin' && $userRole !== 'monitoring') {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
        
        // Exclude pegawai role from the list
        $pegawaiRole = Role::where('name', 'pegawai')->first();
        
        $users = User::with('role')
            ->where('role_id', '!=', $pegawaiRole ? $pegawaiRole->id : 0)
            ->orderBy('name')
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }
    
    public function create()
    {
        // Authorize access - only admin or monitoring can create users
        $userRole = auth()->user()->role->name;
        if ($userRole !== 'admin' && $userRole !== 'monitoring') {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
        
        // Get roles excluding pegawai
        $pegawaiRole = Role::where('name', 'pegawai')->first();
        $roles = Role::where('id', '!=', $pegawaiRole ? $pegawaiRole->id : 0)->get();
        
        return view('admin.users.create', compact('roles'));
    }
    
    public function store(Request $request)
    {
        // Authorize access - only admin or monitoring can create users
        $userRole = auth()->user()->role->name;
        if ($userRole !== 'admin' && $userRole !== 'monitoring') {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'role_id' => $request->role_id,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }
    
    public function edit(User $user)
    {
        // Authorize access - only admin or monitoring can edit users
        $userRole = auth()->user()->role->name;
        if ($userRole !== 'admin' && $userRole !== 'monitoring') {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
        
        // Get roles excluding pegawai
        $pegawaiRole = Role::where('name', 'pegawai')->first();
        $roles = Role::where('id', '!=', $pegawaiRole ? $pegawaiRole->id : 0)->get();
        
        return view('admin.users.edit', compact('user', 'roles'));
    }
    
    public function update(Request $request, User $user)
    {
        // Authorize access - only admin or monitoring can update users
        $userRole = auth()->user()->role->name;
        if ($userRole !== 'admin' && $userRole !== 'monitoring') {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        $data = [
            'name' => $request->name,
            'username' => $request->username,
            'role_id' => $request->role_id,
        ];

        if ($request->filled('password')) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil diperbarui.');
    }
    
    public function destroy(User $user)
    {
        // Authorize access - only admin or monitoring can delete users
        $userRole = auth()->user()->role->name;
        if ($userRole !== 'admin' && $userRole !== 'monitoring') {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
        
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil dihapus.');
    }
}
