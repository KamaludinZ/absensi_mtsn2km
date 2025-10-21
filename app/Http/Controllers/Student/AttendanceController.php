<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Rombel;
use Carbon\Carbon;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class AttendanceController extends Controller
{
    public function index()
    {
        return view('student.attendance');
    }

    public function getGrades()
    {
        $grades = Cache::remember('active_grades', 3600, function () {
            return \App\Models\Grade::where('is_active', true)
                ->orderBy('name')
                ->select('id', 'name', 'description')
                ->get();
        });
        
        return response()->json([
            'grades' => $grades
        ]);
    }

    public function getRombels($grade)
    {
        $rombels = Cache::remember("active_rombels_{$grade}", 3600, function () use ($grade) {
            return Rombel::where('grade_id', $grade)
                ->orderBy('name', 'asc')
                ->get(['id', 'name']);
        });
        
        return response()->json([
            'rombels' => $rombels
        ]);
    }

    public function getStudents($rombelId)
    {
        // Validate the rombelId
        if (!$rombelId || !is_numeric($rombelId)) {
            return response()->json([
                'students' => [],
                'message' => 'Invalid rombel ID provided'
            ]);
        }

        $students = Cache::remember("active_students_{$rombelId}", 300, function () use ($rombelId) { // Reduced cache time to 5 minutes
            return Student::where('rombel_id', $rombelId)
                ->where('is_active', true)
                ->with('rombel') // Load rombel relationship
                ->select('id', 'full_name', 'rombel_id')
                ->orderBy('full_name', 'asc')
                ->get();
        });
        
        // Format the data to include rombel name
        $formattedStudents = $students->map(function ($student) {
            return [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'rombel_name' => $student->rombel ? $student->rombel->name : ''
            ];
        });
        
        // Log for debugging purposes
        \Log::info('getStudents called', [
            'rombelId' => $rombelId,
            'studentCount' => count($formattedStudents)
        ]);
        
        return response()->json([
            'students' => $formattedStudents
        ]);
    }

    public function getStudentInfo(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        $student = Student::where('id', $request->student_id)
            ->where('is_active', true)
            ->with('rombel')
            ->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Siswa tidak ditemukan atau tidak aktif.',
            ]);
        }

        // Check if already clocked in today
        $today = Carbon::today();
        $attendance = Attendance::where('attendable_type', Student::class)
            ->where('attendable_id', $student->id)
            ->whereDate('date', $today)
            ->first();

        return response()->json([
            'success' => true,
            'student' => [
                'id' => $student->id,
                'full_name' => $student->full_name,

                'rombel' => $student->rombel->name,
                'has_clocked_in' => $attendance && $attendance->clock_in !== null,
                'has_clocked_out' => $attendance && $attendance->clock_out !== null,
            ],
        ]);
    }

    public function clockIn(Request $request)
    {
        try {
            $request->validate([
                'student_id' => 'required|exists:students,id',
                'selfie' => [
                    'required',
                    'string',
                    'regex:/^data:image\/(?:jpeg|png|jpg);base64,[A-Za-z0-9+\/=]+$/',
                    'max:2000000' // 2MB max file size
                ],
            ]);

            $student = Student::findOrFail($request->student_id);

            // Check if already clocked in today
            $today = Carbon::today();
            $attendance = Attendance::where('attendable_type', Student::class)
                ->where('attendable_id', $student->id)
                ->whereDate('date', $today)
                ->first();

            if ($attendance && $attendance->clock_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan absen masuk hari ini.',
                ]);
            }

            // Process selfie
            $selfiePath = $this->processSelfie($request->selfie, 'student', $student->id, 'in');

            // Determine status
            $now = Carbon::now();
            $schedule = Schedule::where('type', 'siswa')
                ->where('day_of_week', $now->dayOfWeek)
                ->first();

            $status = 'Tepat Waktu';
            if ($schedule) {
                // Ambil waktu saja dalam format H:i:s untuk perbandingan
                $absensiMasukTime = $now->format('H:i:s');
                $jamMasukTime = Carbon::parse($schedule->start_time)->format('H:i:s');

                // Bandingkan string waktu
                if ($absensiMasukTime > $jamMasukTime) {
                    $status = 'Terlambat';
                }
            }

            // Get shift information for the student
            $shiftInfo = null;
            if ($schedule) {
                $shiftInfo = $schedule;
            } else {
                // Fallback to default schedule
                $shiftInfo = Schedule::where('type', 'siswa')
                    ->where('day_of_week', $now->dayOfWeek)
                    ->first();
            }
            
            // Create or update attendance
            if ($attendance) {
                $attendance->update([
                    'clock_in' => $now,
                    'selfie_path_in' => $selfiePath,
                    'status_in' => $status,
                    'shift_name' => $shiftInfo ? $shiftInfo->shift_name : null,
                    'shift_type' => $shiftInfo ? $shiftInfo->shift_type : null,
                    'scheduled_start_time' => $shiftInfo ? $shiftInfo->start_time : null,
                    'scheduled_end_time' => $shiftInfo ? $shiftInfo->end_time : null,
                ]);
            } else {
                Attendance::create([
                    'attendable_type' => Student::class,
                    'attendable_id' => $student->id,
                    'date' => $today,
                    'clock_in' => $now,
                    'selfie_path_in' => $selfiePath,
                    'status_in' => $status,
                    'shift_name' => $shiftInfo ? $shiftInfo->shift_name : null,
                    'shift_type' => $shiftInfo ? $shiftInfo->shift_type : null,
                    'scheduled_start_time' => $shiftInfo ? $shiftInfo->start_time : null,
                    'scheduled_end_time' => $shiftInfo ? $shiftInfo->end_time : null,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Absen masuk berhasil dicatat.',
                'status' => $status,
                'redirect_url' => '/' // Redirect to landing page after successful attendance
            ]);
        } catch (\Exception $e) {
            \Log::error('Student clock-in error: ' . $e->getMessage(), [
                'student_id' => $request->student_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses absensi. Silakan coba lagi.',
            ], 500);
        }
    }

    public function clockOut(Request $request)
    {
        try {
            $request->validate([
                'student_id' => 'required|exists:students,id',
                'selfie' => [
                    'required',
                    'string',
                    'regex:/^data:image\/(?:jpeg|png|jpg);base64,[A-Za-z0-9+\/=]+$/',
                    'max:2000000' // 2MB max file size
                ],
            ]);

            $student = Student::findOrFail($request->student_id);

            // Check attendance
            $today = Carbon::today();
            $attendance = Attendance::where('attendable_type', Student::class)
                ->where('attendable_id', $student->id)
                ->whereDate('date', $today)
                ->first();

            if (!$attendance || !$attendance->clock_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum melakukan absen masuk hari ini.',
                ]);
            }

            if ($attendance->clock_out) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan absen keluar hari ini.',
                ]);
            }

            // Process selfie
            $selfiePath = $this->processSelfie($request->selfie, 'student', $student->id, 'out');

            // Determine status
            $now = Carbon::now();
            $schedule = Schedule::where('type', 'siswa')
                ->where('day_of_week', $now->dayOfWeek)
                ->first();

            $status = 'Tepat Waktu';
            if ($schedule) {
                // Ambil waktu saja dalam format H:i:s untuk perbandingan
                $absensiPulangTime = $now->format('H:i:s');
                $jamPulangTime = Carbon::parse($schedule->end_time)->format('H:i:s');

                // Bandingkan string waktu
                if ($absensiPulangTime < $jamPulangTime) {
                    $status = 'Pulang Awal';
                }
            }

            // Get shift information for the student
            $shiftInfo = null;
            if ($schedule) {
                $shiftInfo = $schedule;
            } else {
                // Fallback to default schedule
                $shiftInfo = Schedule::where('type', 'siswa')
                    ->where('day_of_week', $now->dayOfWeek)
                    ->first();
            }
            
            $attendance->update([
                'clock_out' => $now,
                'selfie_path_out' => $selfiePath,
                'status_out' => $status,
                'shift_name' => $shiftInfo ? $shiftInfo->shift_name : null,
                'shift_type' => $shiftInfo ? $shiftInfo->shift_type : null,
                'scheduled_start_time' => $shiftInfo ? $shiftInfo->start_time : null,
                'scheduled_end_time' => $shiftInfo ? $shiftInfo->end_time : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Absen keluar berhasil dicatat.',
                'status' => $status,
                'redirect_url' => '/' // Redirect to landing page after successful attendance
            ]);
        } catch (\Exception $e) {
            \Log::error('Student clock-out error: ' . $e->getMessage(), [
                'student_id' => $request->student_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses absensi. Silakan coba lagi.',
            ], 500);
        }
    }

    private function processSelfie($base64Image, $type, $id, $direction)
    {
        // Verify the image is a valid base64 encoded image
        if (!preg_match('/^data:image\/(?:jpeg|png|jpg);base64,[A-Za-z0-9+\/=]+$/', $base64Image)) {
            throw new \InvalidArgumentException('Invalid image format');
        }

        // Remove data URI prefix
        $image = preg_replace('/^data:image\/(?:jpeg|png|jpg);base64,/', '', $base64Image);
        $image = str_replace(' ', '+', $image);
        
        // Verify base64 is valid
        if (!base64_decode($image, true)) {
            throw new \InvalidArgumentException('Invalid base64 image data');
        }
        
        $imageData = base64_decode($image);

        // Validate image content by attempting to create image instance
        try {
            $img = Image::read($imageData);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid image file');
        }

        // Verify image dimensions are reasonable
        $width = $img->width();
        $height = $img->height();
        if ($width > 5000 || $height > 5000) {
            throw new \InvalidArgumentException('Image dimensions are too large');
        }

        // Create filename with security in mind
        $filename = $type . '_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $id) . '_' . $direction . '_' . time() . '.jpg';
        $directory = 'selfies/' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $type);

        // Validate directory path to prevent directory traversal
        if (strpos($directory, '..') !== false || strpos($directory, '/') === 0) {
            throw new \InvalidArgumentException('Invalid directory path');
        }

        // Create directory if not exists
        Storage::disk('public')->makeDirectory($directory);

        $path = storage_path('app/public/' . $directory . '/' . $filename);

        // Sanitize filename to prevent directory traversal
        $path = str_replace(['../', './', '..\\', '.\\'], '', $path);

        // Verify the final path is within the allowed storage directory
        $allowedPath = storage_path('app/public/');
        if (strpos(realpath($path) ?: $path, $allowedPath) !== 0) {
            throw new \InvalidArgumentException('Invalid file path');
        }

        // Resize image to max width of 800px while maintaining aspect ratio
        $img = $img->scaleDown(width: 800);

        // Start with high quality (85) and decrease until file size is under 500KB
        $maxFileSize = 500 * 1024; // 500KB in bytes
        $quality = 85;
        $compressed = false;

        // Try different quality levels until we get under the target size or reach minimum quality
        while ($quality >= 30) {
            // Save with current quality
            $img->toJpeg(quality: $quality)->save($path);

            // Check the file size
            $fileSize = filesize($path);

            if ($fileSize <= $maxFileSize) {
                $compressed = true;
                break;
            }

            // Reduce quality for next attempt
            $quality -= 5;
        }

        // If still over size limit after reducing quality, try further resizing
        if (!$compressed) {
            // Re-read the image and resize to smaller dimensions
            $img = Image::read($imageData)->scaleDown(width: 600);

            // Try again with reduced size and quality
            $quality = 75;
            while ($quality >= 20) {
                $img->toJpeg(quality: $quality)->save($path);
                $fileSize = filesize($path);

                if ($fileSize <= $maxFileSize) {
                    $compressed = true;
                    break;
                }

                $quality -= 5;
            }
        }

        // If still over size limit, just save with minimum quality
        if (!$compressed) {
            $img->toJpeg(quality: 20)->save($path);
        }

        return $directory . '/' . $filename;
    }
}