<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\User;
use App\Models\Role;
use App\Traits\Paginable;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    use Paginable;
    public function index()
    {
        $query = Employee::with('user');
        
        // Sorting logic
        $sortField = request('sort', 'full_name');
        $sortDirection = request('direction', 'asc');

        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        // Apply sorting based on field
        switch ($sortField) {
            case 'position':
            case 'is_active':
                $query->orderBy($sortField, $sortDirection);
                break;

            case 'username':
                // For username (relationship field), we need to sort the collection
                $employees = $query->get()->sortBy(function($employee) {
                    return strtolower($employee->user->username ?? '');
                }, SORT_REGULAR, $sortDirection === 'desc');
                
                // Manually paginate with customizable pagination
                $perPage = $this->getPerPagePreference();
                $currentPage = request()->get('page', 1);
                
                if ($perPage === 'all') {
                    // Show all records without pagination
                    $paginatedEmployees = new \Illuminate\Pagination\LengthAwarePaginator(
                        $employees,
                        $employees->count(),
                        $employees->count(),
                        $currentPage,
                        ['path' => request()->url(), 'query' => request()->query()]
                    );
                    $employees = collect($employees);
                } else {
                    $paginatedEmployees = new \Illuminate\Pagination\LengthAwarePaginator(
                        $employees->forPage($currentPage, $perPage),
                        $employees->count(),
                        $perPage,
                        $currentPage,
                        ['path' => request()->url(), 'query' => request()->query()]
                    );
                    $employees = $paginatedEmployees;
                }
                break;

            case 'full_name':
            default:
                $query->orderBy('full_name', $sortDirection);
                $perPage = $this->getPerPagePreference();
                if ($perPage === 'all') {
                    $employees = collect($query->get());
                } else {
                    $employees = $query->paginate($perPage);
                }
                break;
        }
        
        if (!isset($employees)) {
            $perPage = $this->getPerPagePreference();
            if ($perPage === 'all') {
                $employees = collect($query->get());
            } else {
                $employees = $query->paginate($perPage);
            }
        }
        
        return view('admin.employees.index', compact('employees', 'sortField', 'sortDirection'));
    }

    public function create()
    {
        return view('admin.employees.create');
    }

    public function show(Employee $employee)
    {
        $employee->load('user');

        // Get attendance statistics
        $attendances = \App\Models\Attendance::where('attendable_type', Employee::class)
            ->where('attendable_id', $employee->id)
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->orderBy('date', 'desc')
            ->get();

        $totalPresent = $attendances->where('clock_in', '!=', null)->count();
        $totalLate = $attendances->where('status_in', 'Terlambat')->count();
        $totalEarlyLeave = $attendances->where('status_out', 'Pulang Awal')->count();

        return view('admin.employees.show', compact('employee', 'attendances', 'totalPresent', 'totalLate', 'totalEarlyLeave'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'is_active' => 'required|boolean',
            'create_user' => 'nullable|boolean',
            'username' => 'required_if:create_user,1|nullable|string|unique:users,username',
            'password' => 'required_if:create_user,1|nullable|string|min:6',
        ]);

        $userId = null;

        if ($request->create_user) {
            $pegawaiRole = Role::where('name', 'pegawai')->first();

            $user = User::create([
                'name' => $request->full_name,
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'role_id' => $pegawaiRole->id,
            ]);

            $userId = $user->id;
        }

        Employee::create([
            'user_id' => $userId,
            'full_name' => $request->full_name,
            'position' => $request->position,
            'is_active' => $request->is_active,
        ]);

        return redirect()->route('admin.employees.index')
            ->with('success', 'Data pegawai berhasil ditambahkan.');
    }

    public function edit(Employee $employee)
    {
        return view('admin.employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        // Prepare validation rules
        $rules = [
            'full_name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'is_active' => 'required|boolean',
        ];
        
        // Add username validation if username is provided
        if ($request->filled('username')) {
            $rules['username'] = $employee->user 
                ? 'required|string|unique:users,username,' . $employee->user->id 
                : 'required|string|unique:users,username';
        }
        
        // Add password validation if password is provided
        if ($request->filled('password')) {
            $rules['password'] = 'required|string|min:6';
        }

        $request->validate($rules);

        $employee->update($request->only(['full_name', 'position', 'is_active']));

        // Handle user account updates
        if ($request->filled('username') || $request->filled('password')) {
            if (!$employee->user) {
                // Create user account if it doesn't exist
                if ($request->filled('username') && $request->filled('password')) {
                    $pegawaiRole = Role::where('name', 'pegawai')->first();
                    $user = User::create([
                        'name' => $request->full_name,
                        'username' => $request->username,
                        'password' => Hash::make($request->password),
                        'role_id' => $pegawaiRole->id,
                    ]);
                    $employee->update(['user_id' => $user->id]);
                }
            } else {
                // Update existing user account
                $updateData = [];
                if ($request->filled('username')) {
                    $updateData['username'] = $request->username;
                }
                if ($request->filled('full_name')) {
                    $updateData['name'] = $request->full_name;
                }
                
                $employee->user->update($updateData);
                
                // Update password if provided
                if ($request->filled('password')) {
                    $employee->user->update(['password' => Hash::make($request->password)]);
                }
            }
        }

        return redirect()->route('admin.employees.index')
            ->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function destroy(Employee $employee)
    {
        if ($employee->user) {
            $employee->user->delete();
        }

        $employee->delete();

        return redirect()->route('admin.employees.index')
            ->with('success', 'Data pegawai berhasil dihapus.');
    }
    
    /**
     * Show import form
     */
    public function importForm()
    {
        return view('admin.employees.import');
    }

    /**
     * Import employees from Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:2048',
        ], [
            'file.required' => 'File harus diupload',
            'file.max' => 'Ukuran file maksimal 2MB',
        ]);

        try {
            $file = $request->file('file');

            // Validasi ekstensi file secara manual
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'File harus berformat Excel (.xlsx, .xls) atau CSV (.csv)');
            }

            $rows = [];

            // Read the Excel file
            $fileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file->getPathname());
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($fileType);
            $spreadsheet = $reader->load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Skip header row and process data
            $createdCount = 0;
            $skippedCount = 0;
            
            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Skip header row

                if (empty($row[0]) && empty($row[1])) continue; // Skip empty rows
                
                // Extract data: Full Name, Position, Phone, Email, Is Active, Username, Password
                $full_name = trim($row[0] ?? '');
                $position = trim($row[1] ?? '');
                $phone = trim($row[2] ?? '');
                $email = trim($row[3] ?? '');
                $is_active = filter_var($row[4] ?? true, FILTER_VALIDATE_BOOLEAN);
                $username = trim($row[5] ?? '');
                $password = trim($row[6] ?? '');
                
                if (empty($full_name)) {
                    $skippedCount++;
                    continue;
                }

                // Create employee
                $employee = Employee::create([
                    'full_name' => $full_name,
                    'position' => $position,
                    'phone' => $phone,
                    'email' => $email,
                    'is_active' => $is_active,
                ]);
                
                // If username and password provided, create user account
                if (!empty($username) && !empty($password)) {
                    $pegawaiRole = Role::where('name', 'pegawai')->first();

                    if (!$pegawaiRole) {
                        continue; // Skip if role doesn't exist
                    }

                    // Check if username already exists
                    if (!User::where('username', $username)->exists()) {
                        $user = User::create([
                            'name' => $full_name,
                            'username' => $username,
                            'password' => Hash::make($password),
                            'role_id' => $pegawaiRole->id,
                        ]);

                        // Link user to employee
                        $employee->update(['user_id' => $user->id]);
                    }
                }
                
                $createdCount++;
            }

            return redirect()->route('admin.employees.index')
                ->with('success', "Berhasil mengimpor {$createdCount} pegawai. {$skippedCount} data dilewati.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengimpor data: ' . $e->getMessage());
        }
    }

    /**
     * Download Excel template for employee import
     */
    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set header
        $sheet->setCellValue('A1', 'Nama Lengkap');
        $sheet->setCellValue('B1', 'Jabatan');
        $sheet->setCellValue('C1', 'Telepon');
        $sheet->setCellValue('D1', 'Email');
        $sheet->setCellValue('E1', 'Status Aktif (1=Aktif, 0=Tidak Aktif)');
        $sheet->setCellValue('F1', 'Username');
        $sheet->setCellValue('G1', 'Password');

        // Style header
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(25);
        $sheet->getColumnDimension('E')->setWidth(30);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(15);

        // Add sample data
        $sheet->setCellValue('A2', 'Ahmad Fauzi');
        $sheet->setCellValue('B2', 'Guru Matematika');
        $sheet->setCellValue('C2', '081234567890');
        $sheet->setCellValue('D2', 'ahmad.fauzi@example.com');
        $sheet->setCellValue('E2', '1');
        $sheet->setCellValue('F2', 'ahmad.fauzi');
        $sheet->setCellValue('G2', 'password123');

        $sheet->setCellValue('A3', 'Siti Nurhaliza');
        $sheet->setCellValue('B3', 'Guru Bahasa Indonesia');
        $sheet->setCellValue('C3', '082345678901');
        $sheet->setCellValue('D3', 'siti.nurhaliza@example.com');
        $sheet->setCellValue('E3', '1');
        $sheet->setCellValue('F3', 'siti.nurhaliza');
        $sheet->setCellValue('G3', 'password123');

        $sheet->setCellValue('A4', 'Budi Santoso');
        $sheet->setCellValue('B4', 'Staf TU');
        $sheet->setCellValue('C4', '083456789012');
        $sheet->setCellValue('D4', 'budi.santoso@example.com');
        $sheet->setCellValue('E4', '1');
        $sheet->setCellValue('F4', 'budi.santoso');
        $sheet->setCellValue('G4', 'password123');

        // Create Excel file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Template_Import_Pegawai.xlsx"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}
