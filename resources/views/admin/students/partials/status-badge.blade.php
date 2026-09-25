@if($student->graduated_at)
    <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">Lulus {{ $student->graduated_at->format('Y') }}</span>
@elseif($student->is_active)
    <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">Aktif</span>
@else
    <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-800">Tidak Aktif</span>
@endif
