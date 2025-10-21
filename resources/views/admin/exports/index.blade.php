@extends('layouts.app')

@section('title', 'Export Laporan')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6">Export Laporan</h1>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <form action="{{ route('admin.reports.generate') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Laporan</label>
                    <select name="type" id="type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Pilih Jenis</option>
                        <option value="employee">Absensi Pegawai</option>
                        <option value="student">Absensi Siswa</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Format</label>
                    <select name="format" id="format" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Pilih Format</option>
                        <option value="excel">Excel</option>
                        <option value="pdf">PDF</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Awal</label>
                    <input type="date" name="start_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tanggal Akhir</label>
                    <input type="date" name="end_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div id="rombel-field" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rombel</label>
                    <select name="rombel_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Pilih Rombel</option>
                        @foreach($rombels as $rombel)
                            <option value="{{ $rombel->id }}">{{ $rombel->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                Export
            </button>
        </form>
    </div>
</div>

<script>
document.getElementById('type').addEventListener('change', function() {
    const rombelField = document.getElementById('rombel-field');
    if (this.value === 'student') {
        rombelField.classList.remove('hidden');
    } else {
        rombelField.classList.add('hidden');
        document.querySelector('select[name="rombel_id"]').value = '';
    }
});
</script>
@endsection