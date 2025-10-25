<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Student;

class SelfieController extends Controller
{
    /**
     * Serve selfie image with security check
     *
     * @param Request $request
     * @param string $type - employee or student
     * @param string $filename
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $type, $filename)
    {
        // Validate type
        if (!in_array($type, ['employee', 'student'])) {
            abort(404);
        }

        // Sanitize filename to prevent directory traversal
        $filename = basename($filename);
        $directory = 'selfies/' . $type;

        $filePath = public_path($directory . '/' . $filename);

        // Check if file exists
        if (!file_exists($filePath)) {
            abort(404, 'File not found');
        }

        // Security check: Verify user has permission to view this selfie
        if (!$this->canViewSelfie($type, $filename)) {
            abort(403, 'Unauthorized access');
        }

        // Get file content
        $file = file_get_contents($filePath);
        $type = mime_content_type($filePath);

        // Return the file with appropriate headers
        return Response::make($file, 200, [
            'Content-Type' => $type,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    /**
     * Check if current user can view the selfie
     *
     * @param string $type
     * @param string $filename
     * @return bool
     */
    private function canViewSelfie($type, $filename)
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Admin and monitoring can view all selfies
        if (in_array($user->role->name, ['admin', 'monitoring'])) {
            return true;
        }

        // Employee can view their own selfies
        if ($type === 'employee' && $user->role->name === 'pegawai') {
            $employee = $user->employee;

            if (!$employee) {
                return false;
            }

            // Check if this selfie belongs to the employee
            // Filename format: employee_ID_in/out_timestamp.jpg
            $parts = explode('_', $filename);

            if (count($parts) >= 2) {
                $employeeIdFromFilename = $parts[1];

                // Allow if the employee ID matches
                if ($employeeIdFromFilename == $employee->id) {
                    return true;
                }
            }

            // Also check in attendance records
            $selfiePathIn = 'selfies/employee/' . $filename;
            $selfiePathOut = 'selfies/employee/' . $filename;

            $hasAccess = Attendance::where('attendable_type', Employee::class)
                ->where('attendable_id', $employee->id)
                ->where(function($query) use ($selfiePathIn, $selfiePathOut) {
                    $query->where('selfie_path_in', $selfiePathIn)
                          ->orWhere('selfie_path_out', $selfiePathOut);
                })
                ->exists();

            return $hasAccess;
        }

        // Students cannot view selfies (handled by admin/monitoring only)
        // If you want students to view their own selfies, add logic here similar to employee

        return false;
    }
}
