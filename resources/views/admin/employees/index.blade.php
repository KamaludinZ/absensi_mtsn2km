@extends('layouts.app')
@section('title', 'Data Pegawai')
@section('page-title', 'Data Pegawai')
@section('content')
<div class="mb-4 flex justify-between">
    <div>
        <a href="{{ route('admin.employees.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Tambah Pegawai</a>
        <a href="{{ route('admin.employees.import-form') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 ml-2">Import Excel</a>
    </div>
</div>
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jabatan</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($employees as $employee)
            <tr>
                <td class="px-6 py-4">{{ $employee->full_name }}</td>
                <td class="px-6 py-4">{{ $employee->position }}</td>
                <td class="px-6 py-4">
                    {{ $employee->user ? $employee->user->username : '-' }}
                </td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs rounded {{ $employee->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $employee->is_active ? 'Aktif' : 'Tidak Aktif' }}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <a href="{{ route('admin.employees.edit', $employee) }}" class="text-blue-600 hover:text-blue-800">Edit</a>
                    <form action="{{ route('admin.employees.destroy', $employee) }}" method="POST" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800 ml-2" onclick="return confirm('Yakin hapus?')">Hapus</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $employees->links() }}</div>

<script>
function togglePassword(employeeId) {
    const passwordElement = document.getElementById('password-' + employeeId);
    const eyeIcon = document.getElementById('eye-icon-' + employeeId);
    
    if (passwordElement.textContent === '********') {
        // In a real scenario, for security you wouldn't reveal the actual password
        // Here we'll just show a placeholder to indicate the functionality
        passwordElement.textContent = '[Disimpan | Diubah: 2025-01-15]';
        eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-1.563 3.029m-9.344 9.344A10.03 10.03 0 0112 19c-4.478 0-8.268-2.943-9.543-7a10.03 10.03 0 011.563-3.029m9.344-9.344A10.05 10.05 0 0012 5c-4.478 0-8.268 2.943-9.543 7a10.05 10.05 0 001.563 3.029m-9.344 9.344z" />';
    } else {
        // Hide password
        passwordElement.textContent = '********';
    }
}
</script>

@endsection
