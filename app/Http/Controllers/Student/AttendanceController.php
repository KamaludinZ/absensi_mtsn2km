<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Schedule;
use App\Models\Rombel;
use App\Models\Grade;
use App\Models\LocationSetting;
use App\Models\AttendanceSetting;
use Carbon\Carbon;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    public function index()
    {
        // Get attendance settings
        $requireSelfie = AttendanceSetting::where('setting_key', 'require_selfie_photo')->first();
        $requireCheckIn = AttendanceSetting::where('setting_key', 'require_check_in')->first();
        $requireCheckOut = AttendanceSetting::where('setting_key', 'require_check_out')->first();

        $attendanceSettings = [
            'require_selfie_photo' => $requireSelfie ? filter_var($requireSelfie->setting_value, FILTER_VALIDATE_BOOLEAN) : true,
            'require_check_in' => $requireCheckIn ? filter_var($requireCheckIn->setting_value, FILTER_VALIDATE_BOOLEAN) : true,
            'require_check_out' => $requireCheckOut ? filter_var($requireCheckOut->setting_value, FILTER_VALIDATE_BOOLEAN) : true,
        ];

        // Get location display settings
        $displayWithCoordinates = LocationSetting::where('setting_key', 'display_with_coordinates')->first();
        $displayWithMap = LocationSetting::where('setting_key', 'display_with_map')->first();

        $locationSettings = [
            'display_with_coordinates' => $displayWithCoordinates ? filter_var($displayWithCoordinates->setting_value, FILTER_VALIDATE_BOOLEAN) : true,
            'display_with_map' => $displayWithMap ? filter_var($displayWithMap->setting_value, FILTER_VALIDATE_BOOLEAN) : true,
        ];

        return view('student.attendance', compact('attendanceSettings', 'locationSettings'));
    }

    public function getGrades()
    {
        $grades = Grade::where('is_active', true)
            ->orderBy('name')
            ->select('id', 'name', 'description')
            ->get();
        
        return response()->json([
            'grades' => $grades
        ]);
    }

    public function getRombels($grade)
    {
        $rombels = Rombel::where('grade_id', $grade)
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);
        
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

        $students = Student::where('rombel_id', $rombelId)
            ->where('is_active', true)
            ->with('rombel') // Load rombel relationship
            ->select('id', 'full_name', 'rombel_id')
            ->orderBy('full_name', 'asc')
            ->get();
        
        // Format the data to include rombel name
        $formattedStudents = $students->map(function ($student) {
            return [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'rombel_name' => $student->rombel ? $student->rombel->name : ''
            ];
        });
        
        // Log for debugging purposes
        Log::info('getStudents called', [
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
                'rombel' => $student->rombel ? $student->rombel->name : 'N/A',
                'grade_info' => $student->rombel && $student->rombel->grade ? 
                    ($student->rombel->grade->name . ' - ' . $student->rombel->grade->description) : 'N/A',
                'has_clocked_in' => $attendance && $attendance->clock_in !== null,
                'has_clocked_out' => $attendance && $attendance->clock_out !== null,
            ],
        ]);
    }

    public function clockIn(Request $request)
    {
        try {
            // Check attendance settings
            $requireSelfie = AttendanceSetting::where('setting_key', 'require_selfie_photo')->first();
            $requireSelfiePhoto = $requireSelfie ? filter_var($requireSelfie->setting_value, FILTER_VALIDATE_BOOLEAN) : true;

            // Build validation rules dynamically based on settings
            $validationRules = [
                'student_id' => 'required|exists:students,id',
                'location' => 'array|nullable',
                'location.latitude' => 'required_with:location|numeric|between:-90,90',
                'location.longitude' => 'required_with:location|numeric|between:-180,180',
                'location.accuracy' => 'required_with:location|numeric|min:0',
            ];

            // Add selfie validation only if required
            if ($requireSelfiePhoto) {
                $validationRules['selfie'] = [
                    'required',
                    'string',
                    'regex:/^data:image\/(?:jpeg|png|jpg);base64,[A-Za-z0-9+\/=]+$/',
                    'max:2000000' // 2MB max file size
                ];
            } else {
                $validationRules['selfie'] = 'nullable|string|regex:/^data:image\/(?:jpeg|png|jpg);base64,[A-Za-z0-9+\/=]+$/|max:2000000';
            }

            $request->validate($validationRules);

            $student = Student::findOrFail($request->student_id);

            // Check location requirements
            $locationSetting = \App\Models\LocationSetting::where('setting_key', 'location_required')->first();
            $locationRequired = $locationSetting ? filter_var($locationSetting->setting_value, FILTER_VALIDATE_BOOLEAN) : false;

            if ($locationRequired && (!$request->has('location') || empty($request->location))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lokasi diperlukan untuk absensi. Harap izinkan akses lokasi.',
                ]);
            }

            // Check geofence if location is provided
            if ($request->has('location') && !empty($request->location)) {
                $isValidLocation = $this->validateGeofence(
                    $request->location['latitude'],
                    $request->location['longitude'],
                    $request->location['accuracy']
                );

                if (!$isValidLocation) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Lokasi Anda berada di luar area geofence yang ditentukan.',
                    ]);
                }
            }

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

            // Process selfie only if provided
            $selfiePath = null;
            if ($request->has('selfie') && !empty($request->selfie)) {
                $selfiePath = $this->processSelfie($request->selfie, 'student', $student->id, 'in');
            }

            // Get location data if provided
            $latitude = null;
            $longitude = null;
            $accuracy = null;
            
            if ($request->has('location') && !empty($request->location)) {
                $location = $request->location;
                $latitude = isset($location['latitude']) ? floatval($location['latitude']) : null;
                $longitude = isset($location['longitude']) ? floatval($location['longitude']) : null;
                $accuracy = isset($location['accuracy']) ? floatval($location['accuracy']) : null;
            }

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
                    'clock_in_latitude' => $latitude,
                    'clock_in_longitude' => $longitude,
                    'clock_in_accuracy' => $accuracy,
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
                    'clock_in_latitude' => $latitude,
                    'clock_in_longitude' => $longitude,
                    'clock_in_accuracy' => $accuracy,
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
            Log::error('Student clock-in error: ' . $e->getMessage(), [
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
            // Check attendance settings
            $requireSelfie = AttendanceSetting::where('setting_key', 'require_selfie_photo')->first();
            $requireSelfiePhoto = $requireSelfie ? filter_var($requireSelfie->setting_value, FILTER_VALIDATE_BOOLEAN) : true;

            $requireCheckInSetting = AttendanceSetting::where('setting_key', 'require_check_in')->first();
            $requireCheckIn = $requireCheckInSetting ? filter_var($requireCheckInSetting->setting_value, FILTER_VALIDATE_BOOLEAN) : true;

            // Build validation rules dynamically based on settings
            $validationRules = [
                'student_id' => 'required|exists:students,id',
                'location' => 'array|nullable',
                'location.latitude' => 'required_with:location|numeric|between:-90,90',
                'location.longitude' => 'required_with:location|numeric|between:-180,180',
                'location.accuracy' => 'required_with:location|numeric|min:0',
            ];

            // Add selfie validation only if required
            if ($requireSelfiePhoto) {
                $validationRules['selfie'] = [
                    'required',
                    'string',
                    'regex:/^data:image\/(?:jpeg|png|jpg);base64,[A-Za-z0-9+\/=]+$/',
                    'max:2000000' // 2MB max file size
                ];
            } else {
                $validationRules['selfie'] = 'nullable|string|regex:/^data:image\/(?:jpeg|png|jpg);base64,[A-Za-z0-9+\/=]+$/|max:2000000';
            }

            $request->validate($validationRules);

            $student = Student::findOrFail($request->student_id);

            // Check location requirements
            $locationSetting = \App\Models\LocationSetting::where('setting_key', 'location_required')->first();
            $locationRequired = $locationSetting ? filter_var($locationSetting->setting_value, FILTER_VALIDATE_BOOLEAN) : false;

            if ($locationRequired && (!$request->has('location') || empty($request->location))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lokasi diperlukan untuk absensi. Harap izinkan akses lokasi.',
                ]);
            }

            // Check geofence if location is provided
            if ($request->has('location') && !empty($request->location)) {
                $isValidLocation = $this->validateGeofence(
                    $request->location['latitude'],
                    $request->location['longitude'],
                    $request->location['accuracy']
                );

                if (!$isValidLocation) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Lokasi Anda berada di luar area geofence yang ditentukan.',
                    ]);
                }
            }

            // Check attendance
            $today = Carbon::today();
            $attendance = Attendance::where('attendable_type', Student::class)
                ->where('attendable_id', $student->id)
                ->whereDate('date', $today)
                ->first();

            // ENFORCEMENT OF 'require_check_in'
            if ($requireCheckIn && (!$attendance || !$attendance->clock_in)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum melakukan absen masuk hari ini. Absen masuk diwajibkan.',
                ]);
            }

            // If clock-in is NOT required, and there's no attendance record yet, create one for clock-out
            // This handles the case where a user only clocks out.
            if (!$requireCheckIn && !$attendance) {
                 $attendance = Attendance::create([
                    'attendable_type' => Student::class,
                    'attendable_id' => $student->id,
                    'date' => $today,
                    // clock_in remains null
                ]);
            }

            if ($attendance->clock_out) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan absen keluar hari ini.',
                ]);
            }

            // Process selfie only if provided
            $selfiePath = null;
            if ($request->has('selfie') && !empty($request->selfie)) {
                $selfiePath = $this->processSelfie($request->selfie, 'student', $student->id, 'out');
            }

            // Get location data if provided
            $latitude = null;
            $longitude = null;
            $accuracy = null;
            
            if ($request->has('location') && !empty($request->location)) {
                $location = $request->location;
                $latitude = isset($location['latitude']) ? floatval($location['latitude']) : null;
                $longitude = isset($location['longitude']) ? floatval($location['longitude']) : null;
                $accuracy = isset($location['accuracy']) ? floatval($location['accuracy']) : null;
            }

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
                'clock_out_latitude' => $latitude,
                'clock_out_longitude' => $longitude,
                'clock_out_accuracy' => $accuracy,
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
            Log::error('Student clock-out error: ' . $e->getMessage(), [
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
        if (!preg_match('/^data:image\/(?:jpeg|png|jpg);base64,[A-Za-z0-9+\/=]+$/',$base64Image)) {
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

        // Detect environment and use appropriate storage path
        // Production: public/storage/selfies (without symlink, inside web root)
        // Development: storage/app/public/selfies (with symlink)
        $isProduction = config('app.env') === 'production';

        if ($isProduction) {
            // Production: Save to public/storage/selfies (inside web root, no symlink needed)
            // Create folder if not exists
            if (!file_exists(public_path('storage'))) {
                @mkdir(public_path('storage'), 0755, true);
                @mkdir(public_path('storage/selfies/employee'), 0755, true);
                @mkdir(public_path('storage/selfies/student'), 0755, true);
            }

            $path = public_path('storage/' . $directory . '/' . $filename);

            // Create directory if not exists
            $directoryPath = public_path('storage/' . $directory);
            if (!file_exists($directoryPath)) {
                mkdir($directoryPath, 0755, true);
            }
        } else {
            // Local development: Use storage with symlink
            Storage::disk('public')->makeDirectory($directory);
            $path = storage_path('app/public/' . $directory . '/' . $filename);
        }

        // Sanitize filename to prevent directory traversal
        $path = str_replace(["../", "./", "..\\", ".\\"], '', $path);

        // Verify the final path is within the allowed directory
        $allowedPath = $isProduction ? public_path('storage') : storage_path('app/public/');
        if (strpos(realpath(dirname($path)) ?: dirname($path), $allowedPath) !== 0) {
            throw new \InvalidArgumentException('Invalid file path');
        }

        // Resize image to max width of 800px while maintaining aspect ratio
        $img->scaleDown(width: 800);

        // Start with high quality (85) and decrease until file size is under 500KB
        $maxFileSize = 500 * 1024; // 500KB in bytes
        $quality = 85;
        $compressed = false;

        // Try different quality levels until we get under the target size or reach minimum quality
        while ($quality >= 30) {
            // Save with current quality
            $img->toJpeg($quality)->save($path);

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
            // Calculate new dimensions that should get us under the size limit
            $img->scaleDown(width: 600);

            // Try again with reduced size and quality
            $quality = 75;
            while ($quality >= 20) {
                $img->toJpeg($quality)->save($path);
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
            $img->toJpeg(20)->save($path);
        }

        return $directory . '/' . $filename;
    }
    
    /**
     * Validate if the provided location is within the geofence
     */
    private function validateGeofence($latitude, $longitude, $accuracy)
    {
        // Get geofence settings
        $geofenceCenterLat = \App\Models\LocationSetting::where('setting_key', 'geofence_center_lat')->first();
        $geofenceCenterLng = \App\Models\LocationSetting::where('setting_key', 'geofence_center_lng')->first();
        $geofenceRadius = \App\Models\LocationSetting::where('setting_key', 'geofence_radius')->first();

        // If no geofence settings exist, allow location
        if (!$geofenceCenterLat || !$geofenceCenterLng || !$geofenceRadius) {
            \Log::debug('No geofence settings found, allowing location');
            return true;
        }

        $centerLat = floatval($geofenceCenterLat->setting_value);
        $centerLng = floatval($geofenceCenterLng->setting_value);
        $radius = floatval($geofenceRadius->setting_value);

        // Calculate distance between current location and geofence center
        $distance = $this->calculateDistance($latitude, $longitude, $centerLat, $centerLng);

        // Enhanced validation with more flexible accuracy handling:
        // 1. If user is within the strict radius, always allow
        $withinStrictRadius = $distance <= $radius;
        
        if ($withinStrictRadius) {
            \Log::debug('Location within strict radius, allowing', [
                'user_lat' => $latitude,
                'user_lng' => $longitude,
                'center_lat' => $centerLat,
                'center_lng' => $centerLng,
                'radius' => $radius,
                'distance' => $distance
            ]);
            return true;
        }

        // For users outside strict radius, apply more lenient accuracy handling
        // Ensure accuracy is reasonable but be more forgiving (max 200 meters instead of 100)
        $safeAccuracy = min(floatval($accuracy), 200.0);

        // Calculate max allowed distance with more generous accuracy buffer
        // Allow up to 75% of radius as accuracy buffer for better tolerance
        $maxAllowedDistance = $radius + min($safeAccuracy, $radius * 0.75);

        // Log for debugging purposes
        \Log::debug('Geofence validation - Extended check', [
            'user_lat' => $latitude,
            'user_lng' => $longitude,
            'center_lat' => $centerLat,
            'center_lng' => $centerLng,
            'radius' => $radius,
            'accuracy' => $accuracy,
            'safe_accuracy' => $safeAccuracy,
            'distance' => $distance,
            'max_allowed_distance' => $maxAllowedDistance,
            'within_strict_radius' => $withinStrictRadius
        ]);

        $result = $distance <= $maxAllowedDistance;
        
        if (!$result) {
            \Log::warning('Location outside geofence', [
                'user_lat' => $latitude,
                'user_lng' => $longitude,
                'center_lat' => $centerLat,
                'center_lng' => $centerLng,
                'radius' => $radius,
                'distance' => $distance,
                'max_allowed_distance' => $maxAllowedDistance
            ]);
        }
        
        return $result;
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371e3; // Earth's radius in meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }
}