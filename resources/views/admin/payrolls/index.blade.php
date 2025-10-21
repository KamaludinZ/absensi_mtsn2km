@extends('layouts.app')
@section('title', 'Gaji Pegawai')
@section('page-title', 'Gaji Pegawai')
@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <table class="min-w-full">
        <thead><tr><th class="px-6 py-3 text-left">Pegawai</th><th class="px-6 py-3 text-left">Bulan/Tahun</th><th class="px-6 py-3 text-left">Gaji Pokok</th><th class="px-6 py-3 text-left">Total</th><th class="px-6 py-3 text-left">Aksi</th></tr></thead>
        <tbody>
            @forelse($payrolls as $payroll)
            <tr><td class="px-6 py-4">{{ $payroll->employee->full_name }}</td><td class="px-6 py-4">{{ $payroll->month }}/{{ $payroll->year }}</td>
            <td class="px-6 py-4">Rp {{ number_format($payroll->base_salary, 0, ',', '.') }}</td>
            <td class="px-6 py-4">Rp {{ number_format($payroll->total_salary, 0, ',', '.') }}</td>
            <td class="px-6 py-4">
                <form action="{{ route('admin.payrolls.destroy', $payroll) }}" method="POST" class="inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-red-600" onclick="return confirm('Hapus?')">Hapus</button>
                </form>
            </td></tr>
            @empty
            <tr><td colspan="5" class="text-center py-4">Tidak ada data</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="mt-4">{{ $payrolls->links() }}</div>
</div>
@endsection
