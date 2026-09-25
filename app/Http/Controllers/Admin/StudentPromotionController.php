<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Rombel;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Move many students at once to another rombel (class promotion), or mark
 * them as graduated. Graduated students become inactive, so they can no
 * longer use the attendance page or their RFID card.
 */
class StudentPromotionController extends Controller
{
    public function index(Request $request)
    {
        $rombelGroups = $this->rombelGroups();

        $sourceRombel = null;
        $students = collect();

        if ($request->filled('rombel_id')) {
            $sourceRombel = Rombel::with(['academicYear', 'grade'])->find($request->rombel_id);

            if ($sourceRombel) {
                $students = Student::where('rombel_id', $sourceRombel->id)
                    ->where('is_active', true)
                    ->orderBy('full_name')
                    ->get();
            }
        }

        return view('admin.students.promotion', compact('rombelGroups', 'sourceRombel', 'students'));
    }

    public function process(Request $request)
    {
        $validated = $request->validate([
            'source_rombel_id' => 'required|exists:rombels,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer',
            'action' => 'required|in:move,graduate',
            'target_rombel_id' => 'required_if:action,move|nullable|exists:rombels,id|different:source_rombel_id',
            'release_rfid' => 'nullable|boolean',
        ], [
            'student_ids.required' => 'Pilih minimal satu siswa.',
            'target_rombel_id.required_if' => 'Pilih rombel tujuan.',
            'target_rombel_id.different' => 'Rombel tujuan harus berbeda dengan rombel asal.',
        ]);

        $source = Rombel::findOrFail($validated['source_rombel_id']);

        // Only active students that really are in the source rombel
        $students = Student::where('rombel_id', $source->id)
            ->where('is_active', true)
            ->whereIn('id', $validated['student_ids'])
            ->get();

        if ($students->isEmpty()) {
            return back()->with('error', 'Tidak ada siswa aktif yang dipilih dari rombel ' . $source->name . '.');
        }

        if ($validated['action'] === 'move') {
            $target = Rombel::findOrFail($validated['target_rombel_id']);

            DB::transaction(function () use ($students, $target) {
                Student::whereIn('id', $students->pluck('id'))->update(['rombel_id' => $target->id]);
            });

            $message = $students->count() . ' siswa dipindahkan dari ' . $source->name . ' ke ' . $target->name . '.';
        } else {
            $releaseRfid = $request->boolean('release_rfid');

            DB::transaction(function () use ($students, $releaseRfid) {
                $changes = ['is_active' => false, 'graduated_at' => now()->toDateString()];
                if ($releaseRfid) {
                    $changes['rfid_uid'] = null;
                }
                Student::whereIn('id', $students->pluck('id'))->update($changes);
            });

            $message = $students->count() . ' siswa dari ' . $source->name . ' dinyatakan lulus dan dinonaktifkan'
                . ($releaseRfid ? ', kartu RFID-nya dilepas.' : '.');
        }

        return redirect()->route('admin.students.promotion.index', ['rombel_id' => $source->id])
            ->with('success', $message);
    }

    /**
     * Rombels grouped by academic year, active year first.
     */
    private function rombelGroups()
    {
        $activeYearId = AcademicYear::where('status', 'active')->value('id');

        return Rombel::with('academicYear')
            ->withCount(['students as active_students_count' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('grade_id')
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($rombel) => $rombel->academicYear->year_name ?? 'Tanpa Tahun Ajaran')
            ->sortBy(fn ($rombels) => [
                optional($rombels->first()->academicYear)->id === $activeYearId ? 0 : 1,
                // newer years first
                -1 * (int) substr(optional($rombels->first()->academicYear)->year_name ?? '0', 0, 4),
            ]);
    }
}
