@extends('layouts.app')
@section('title', 'Kartu RFID Siswa')
@section('page-title', 'Kartu RFID Siswa')

@section('content')
<div class="container mx-auto px-4 py-6 space-y-6" x-data="{ autoNext: true }">
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Pendaftaran Kartu RFID Siswa</h1>
        <p class="text-gray-600 mb-4">Kartu yang sudah didaftarkan bisa dipakai siswa untuk absen dengan cara menempelkan kartu, sebagai pengganti mengetik NISN.</p>

        <ol class="list-decimal list-inside text-sm text-gray-700 space-y-1 mb-4">
            <li>Colokkan alat pembaca kartu RFID (USB) ke komputer ini.</li>
            <li>Klik kolom <strong>Kartu RFID</strong> pada baris siswa, lalu tempelkan kartunya. Nomor kartu terisi dan tersimpan otomatis.</li>
            <li>Tanpa alat pembaca: ketik nomor kartu, lalu tekan Enter atau tombol <strong>Simpan</strong>.</li>
        </ol>

        @php $percent = $stats['total'] ? round($stats['registered'] / $stats['total'] * 100) : 0; @endphp
        <div>
            <div class="flex justify-between text-sm text-gray-600 mb-1">
                <span>{{ $stats['registered'] }} dari {{ $stats['total'] }} siswa aktif sudah punya kartu</span>
                <span>{{ $percent }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-green-500 h-2 rounded-full" style="width: {{ $percent }}%"></div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="{{ route('admin.students.rfid.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div>
                <label for="rombel_id" class="block text-sm font-medium text-gray-700 mb-1">Rombel</label>
                <select id="rombel_id" name="rombel_id" class="w-full px-3 py-2 border rounded-lg">
                    <option value="">Semua Rombel</option>
                    @foreach($rombels as $rombel)
                        <option value="{{ $rombel->id }}" @selected(request('rombel_id') == $rombel->id)>{{ $rombel->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status Kartu</label>
                <select id="status" name="status" class="w-full px-3 py-2 border rounded-lg">
                    <option value="all" @selected($status === 'all')>Semua</option>
                    <option value="unregistered" @selected($status === 'unregistered')>Belum punya kartu</option>
                    <option value="registered" @selected($status === 'registered')>Sudah punya kartu</option>
                </select>
            </div>
            <div>
                <label for="q" class="block text-sm font-medium text-gray-700 mb-1">Cari Nama / NISN</label>
                <input id="q" type="text" name="q" value="{{ request('q') }}" class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Tampilkan</button>
            </div>
        </form>

        <label class="inline-flex items-center text-sm text-gray-700 mb-4">
            <input type="checkbox" x-model="autoNext" class="rounded border-gray-300">
            <span class="ml-2">Setelah kartu tersimpan, langsung pindah ke siswa berikutnya yang belum punya kartu</span>
        </label>

        @if($students->isEmpty())
            <p class="text-gray-600">Tidak ada siswa yang cocok dengan filter.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">NISN</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rombel</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kartu RFID</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($students as $student)
                            <tr x-data="rfidRow({{ $student->id }}, @js($student->rfid_uid))" :class="focused ? 'bg-blue-50' : ''">
                                <td class="px-4 py-3 text-sm text-gray-800">{{ $student->full_name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $student->nisn }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $student->rombel->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm min-w-[18rem]">
                                    <div class="flex gap-2">
                                        <input type="text" x-ref="input" x-model="value" data-rfid-input :data-has-card="uid ? '1' : '0'"
                                               maxlength="50" autocomplete="off" placeholder="Klik lalu tempel kartu"
                                               @focus="focused = true; $el.select()" @blur="focused = false"
                                               @keydown.enter.prevent="save()"
                                               class="flex-1 min-w-0 px-3 py-1.5 border rounded-md font-mono"
                                               :class="uid ? 'border-green-400' : 'border-gray-300'">
                                        <button type="button" @click="save()" :disabled="saving || !value.trim() || value.trim() === uid"
                                                class="px-3 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-40">Simpan</button>
                                        <button type="button" x-show="uid" @click="remove()" :disabled="saving"
                                                class="px-3 py-1.5 bg-red-50 text-red-600 rounded-md hover:bg-red-100">Hapus</button>
                                    </div>
                                    <p class="text-xs mt-1 text-blue-600" x-show="focused && !saving" x-cloak>Tempelkan kartu sekarang…</p>
                                    <p class="text-xs mt-1" x-show="message && !focused" x-cloak
                                       :class="state === 'ok' ? 'text-green-600' : 'text-red-600'" x-text="message"></p>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $students->links() }}</div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function rfidRow(id, uid) {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const url = @js(route('admin.students.rfid.assign', ['student' => '__ID__'])).replace('__ID__', id);

    return {
        uid: uid,
        value: uid || '',
        focused: false,
        saving: false,
        state: '',
        message: '',

        async request(method, body) {
            const response = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: body ? JSON.stringify(body) : undefined,
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.success) {
                const firstError = data.errors ? Object.values(data.errors)[0][0] : null;
                throw new Error(data.message && !data.errors ? data.message : (firstError || 'Gagal menyimpan. Coba lagi.'));
            }
            return data;
        },

        async save() {
            const value = this.value.trim();
            if (!value || this.saving) return;
            this.saving = true;
            try {
                const data = await this.request('POST', { rfid_uid: value });
                this.uid = data.rfid_uid;
                this.value = data.rfid_uid;
                this.state = 'ok';
                this.message = 'Kartu tersimpan.';
                this.$refs.input.blur();
                if (this.autoNext) this.focusNext();
            } catch (error) {
                this.state = 'error';
                this.message = error.message;
                this.value = this.uid || '';
                this.$refs.input.blur();
            } finally {
                this.saving = false;
            }
        },

        async remove() {
            if (!confirm('Hapus kartu RFID siswa ini?')) return;
            this.saving = true;
            try {
                await this.request('DELETE');
                this.uid = null;
                this.value = '';
                this.state = 'ok';
                this.message = 'Kartu dihapus.';
            } catch (error) {
                this.state = 'error';
                this.message = error.message;
            } finally {
                this.saving = false;
            }
        },

        focusNext() {
            const inputs = Array.from(document.querySelectorAll('[data-rfid-input]'));
            const next = inputs.slice(inputs.indexOf(this.$refs.input) + 1).find(el => el.dataset.hasCard !== '1');
            if (next) {
                next.focus();
                next.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        },
    };
}
</script>
@endpush
