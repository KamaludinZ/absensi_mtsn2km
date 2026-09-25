{{--
    RFID card number field. A USB RFID reader works like a keyboard: it
    "types" the card number and presses Enter. Enter is caught here so it
    does not submit the whole form.
--}}
<div class="mb-4" x-data="{ scanning: false, captured: '' }">
    <label for="rfid_uid" class="block text-sm font-medium text-gray-700 mb-2">
        Nomor Kartu RFID <span class="text-gray-400 font-normal">(opsional)</span>
    </label>
    <div class="flex gap-2">
        <input type="text" id="rfid_uid" name="rfid_uid" x-ref="rfid" maxlength="50" autocomplete="off"
               value="{{ old('rfid_uid', $value ?? '') }}"
               placeholder="Ketik nomor kartu atau tempel kartu"
               @focus="scanning = true" @blur="scanning = false"
               @keydown.enter.prevent="captured = $event.target.value.trim(); $event.target.blur()"
               class="flex-1 px-4 py-2 border rounded-lg font-mono">
        <button type="button" @click="$refs.rfid.value = ''; captured = ''; $refs.rfid.focus()"
                class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm whitespace-nowrap">
            Scan Kartu
        </button>
    </div>
    <p class="text-xs mt-1 text-blue-600" x-show="scanning" x-cloak>Tempelkan kartu ke alat pembaca sekarang…</p>
    <p class="text-xs mt-1 text-green-600" x-show="!scanning && captured" x-cloak>
        Kartu terbaca: <span class="font-mono" x-text="captured"></span>. Tekan tombol simpan untuk menyimpan.
    </p>
    <p class="text-xs mt-1 text-gray-500" x-show="!scanning && !captured">
        Klik <strong>Scan Kartu</strong> lalu tempelkan kartu pelajar ke alat pembaca RFID, atau ketik nomornya.
    </p>
    @error('rfid_uid')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>
