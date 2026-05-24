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
    public function index(Request $request)
    {
        $query = Employee::with('user');

        // Calculate summary statistics
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('is_active', true)->count();
        $inactiveEmployees = Employee::where('is_active', false)->count();

        // Group employees by position or type if possible
        // For this implementation, we'll categorize based on common Indonesian position patterns
        $guruEmployees = Employee::where(function($query) {
            $query->where('position', 'LIKE', '%Guru%')
                  ->orWhere('position', 'LIKE', '%Pendidik%')
                  ->orWhere('position', 'LIKE', '%Dosen%');
        })->count();

        $staffEmployees = $totalEmployees - $guruEmployees; // Remaining employees are considered staff

        // Filter by status (for E2E and UI consistency)
        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->is_active);
        }

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

        return view('admin.employees.index', compact(
            'employees',
            'sortField',
            'sortDirection',
            'totalEmployees',
            'activeEmployees',
            'inactiveEmployees',
            'guruEmployees',
            'staffEmployees'
        ));
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
            'nip' => 'required|string|max:255|unique:employees,nip', // Add NIP validation
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
            'nip' => $request->nip, // Add NIP to creation
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
            'nip' => 'required|string|max:255|unique:employees,nip,' . $employee->id, // Add NIP validation with unique rule excluding current employee
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

        $employee->update($request->only(['nip', 'full_name', 'position', 'is_active'])); // Include NIP in update

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

    public function salarySettings()
    {
        // Get month and year from request, default to current month/year
        $month = (int) request('month', now()->month);
        $year = (int) request('year', now()->year);

        // Get employees who were active during this month or before
        $employees = Employee::where(function($query) use ($month, $year) {
            // Currently active employees (not soft deleted)
            $query->whereNull('deleted_at')
                  // OR employees who were soft deleted after this month (were active during this month)
                  ->orWhere(function($q) use ($month, $year) {
                      $q->whereNotNull('deleted_at')
                        ->where('deleted_at', '>=', \Carbon\Carbon::create($year, $month, 1)->startOfMonth());
                  });
        })->orderBy('full_name')->get();

        // Load salary relationship with month/year filter
        $employees->load(['salary' => function($query) use ($month, $year) {
            $query->where('month', $month)->where('year', $year);
        }]);

        return view('admin.employees.salary-settings', compact('employees', 'month', 'year'));
    }

    public function updateSalarySettings(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2030',
            'salaries' => 'array',
            'salaries.*.base_salary' => 'nullable|numeric|min:0',
            'salaries.*.meal_allowance' => 'nullable|numeric|min:0',
            'salaries.*.transport_allowance' => 'nullable|numeric|min:0',
            'salaries.*.performance_allowance' => 'nullable|numeric|min:0',
        ]);

        $month = (int) $request->month;
        $year = (int) $request->year;
        $updatedCount = 0;

        if ($request->has('salaries')) {
            foreach ($request->salaries as $employeeId => $salaryData) {
                $employee = Employee::find($employeeId);

                if ($employee) {
                    // Ensure all values are numeric and non-negative
                    $salaryData = array_map(function($value) {
                        return $value === null || $value === '' ? 0 : max(0, (float)$value);
                    }, $salaryData);

                    // Update or create salary record for the specific month/year
                    \App\Models\Salary::updateOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'month' => $month,
                            'year' => $year,
                        ],
                        [
                            'base_salary' => $salaryData['base_salary'] ?? 0,
                            'meal_allowance' => $salaryData['meal_allowance'] ?? 0,
                            'transport_allowance' => $salaryData['transport_allowance'] ?? 0,
                            'performance_allowance' => $salaryData['performance_allowance'] ?? 0,
                        ]
                    );

                    $updatedCount++;
                }
            }
        }

        // Handle bulk updates if they exist
        if ($request->has('bulk_base_salary') ||
            $request->has('bulk_meal_allowance') ||
            $request->has('bulk_transport_allowance') ||
            $request->has('bulk_performance_allowance')) {

            $bulkUpdates = [
                'base_salary' => $request->input('bulk_base_salary'),
                'meal_allowance' => $request->input('bulk_meal_allowance'),
                'transport_allowance' => $request->input('bulk_transport_allowance'),
                'performance_allowance' => $request->input('bulk_performance_allowance'),
            ];

            // Filter out null/empty values
            $filteredBulkUpdates = array_filter($bulkUpdates, function($value) {
                return $value !== null && $value !== '';
            });

            if (!empty($filteredBulkUpdates)) {
                $employees = Employee::where(function($query) use ($month, $year) {
                    // Currently active employees (not soft deleted)
                    $query->whereNull('deleted_at')
                          // OR employees who were soft deleted after this month (were active during this month)
                          ->orWhere(function($q) use ($month, $year) {
                              $q->whereNotNull('deleted_at')
                                ->where('deleted_at', '>=', \Carbon\Carbon::create($year, $month, 1)->startOfMonth());
                          });
                })->get();

                foreach ($employees as $employee) {
                    \App\Models\Salary::updateOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'month' => $month,
                            'year' => $year,
                        ],
                        [
                            'base_salary' => $filteredBulkUpdates['base_salary'] ?? (isset($filteredBulkUpdates['base_salary']) ? 0 : null),
                            'meal_allowance' => $filteredBulkUpdates['meal_allowance'] ?? (isset($filteredBulkUpdates['meal_allowance']) ? 0 : null),
                            'transport_allowance' => $filteredBulkUpdates['transport_allowance'] ?? (isset($filteredBulkUpdates['transport_allowance']) ? 0 : null),
                            'performance_allowance' => $filteredBulkUpdates['performance_allowance'] ?? (isset($filteredBulkUpdates['performance_allowance']) ? 0 : null),
                        ]
                    );
                }
                $updatedCount += $employees->count();
            }
        }

        return redirect()->route('admin.employees.salary-settings', ['month' => $month, 'year' => $year])
            ->with('success', "Pengaturan gaji berhasil diperbarui untuk {$updatedCount} pegawai pada bulan " . \Carbon\Carbon::create()->month($month)->translatedFormat('F') . " {$year}.");
    }
}
