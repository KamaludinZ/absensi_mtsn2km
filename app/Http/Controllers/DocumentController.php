<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrection;
use App\Models\Employee;
use App\Models\Permission;
use Illuminate\Support\Facades\Storage;

/**
 * Serves supporting documents (e.g. doctor's notes) behind a login and an
 * ownership check. Direct access to /storage/permissions and
 * /storage/attendance_corrections is blocked in .htaccess.
 */
class DocumentController extends Controller
{
    public function permission(Permission $permission)
    {
        $this->authorizeOwner($permission->permissible_type, $permission->permissible_id);

        return $this->serve($permission->supporting_document, 'permissions/');
    }

    public function attendanceCorrection(AttendanceCorrection $attendanceCorrection)
    {
        $this->authorizeOwner($attendanceCorrection->correctable_type, $attendanceCorrection->correctable_id);

        return $this->serve($attendanceCorrection->supporting_document, 'attendance_corrections/');
    }

    private function authorizeOwner(?string $ownerType, $ownerId): void
    {
        $user = auth()->user();
        $role = $user->role->name ?? null;

        if (in_array($role, ['admin', 'monitoring'], true)) {
            return;
        }

        $employee = $user->employee;
        $isOwner = $role === 'pegawai'
            && $employee
            && $ownerType === Employee::class
            && (int) $ownerId === (int) $employee->id;

        abort_unless($isOwner, 403, 'Anda tidak memiliki akses ke dokumen ini.');
    }

    private function serve(?string $path, string $expectedFolder)
    {
        abort_if(empty($path) || !str_starts_with($path, $expectedFolder) || str_contains($path, '..'), 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404, 'Dokumen tidak ditemukan.');

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
        ];
        abort_unless(isset($mimeTypes[$extension]), 404);

        return response($disk->get($path), 200, [
            'Content-Type' => $mimeTypes[$extension],
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            'Cache-Control' => 'private, max-age=600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
