{{--
    Super App ID of a GTK or student: filled automatically on the first
    login through Super App, can be set or cleared by an admin.
--}}
<div class="mb-4">
    <label for="superapp_id" class="block text-sm font-medium text-gray-700 mb-2">
        Super App ID <span class="text-gray-400 font-normal">(opsional)</span>
    </label>
    <input type="text" id="superapp_id" name="superapp_id" maxlength="64" autocomplete="off"
           value="{{ old('superapp_id', $value ?? '') }}"
           placeholder="Terisi otomatis saat pertama kali masuk lewat Super App"
           class="w-full px-4 py-2 border rounded-lg font-mono @error('superapp_id') border-red-500 @enderror">
    <p class="text-xs text-gray-500 mt-1">
        Penghubung ke akun Super App untuk login SSO. Kosongkan untuk memutus hubungan; akun akan dicocokkan lagi lewat {{ $matchedBy ?? 'NIP' }} saat login berikutnya.
    </p>
    @error('superapp_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
</div>
