# Script untuk memperbaiki syntax error di create.blade.php
# Masalah: Blade directive di dalam JavaScript template literal

$filePath = "C:\absensi_mtsn2km\resources\views\admin\attendances\create.blade.php"

# Read file content
$content = Get-Content -Path $filePath -Raw

# Old text yang mau diganti
$oldText = @"
    // Store the original HTML for employee dropdown
    const employeeDropdownHTML = attendableSelectContainer.innerHTML;

    // Create student dropdown options (we'll populate these via JS)
    const studentDropdownHTML = ``
        <label for="attendable_id" class="block text-sm font-medium text-gray-700 mb-2">Nama <span class="text-red-500">*</span></label>
        <select id="attendable_id" name="attendable_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
            <option value="">Pilih Siswa</option>
            @foreach(`$students as `$student)
                <option value="{{ `$student->id }}">{{ `$student->full_name }} ({{ `$student->nisn }})</option>
            @endforeach
        </select>
    ``;

    attendableTypeSelect.addEventListener('change', function() {
        if (this.value === 'App\\Models\\Employee') {
            attendableSelectContainer.innerHTML = employeeDropdownHTML;
        } else if (this.value === 'App\\Models\\Student') {
            attendableSelectContainer.innerHTML = studentDropdownHTML;
        }

        // Update schedule info when attendable type changes
        updateScheduleInfo();
    });
"@

# New text sebagai pengganti
$newText = @"
    // Store the original HTML for employee dropdown
    const employeeDropdownHTML = attendableSelectContainer.innerHTML;

    // Prepare students data as JSON
    const studentsData = @json(`$students);

    // Create student dropdown HTML dynamically
    let studentOptionsHTML = '<option value="">Pilih Siswa</option>';
    studentsData.forEach(function(student) {
        studentOptionsHTML += '<option value="' + student.id + '">' + student.full_name + ' (' + student.nisn + ')</option>';
    });

    const studentDropdownHTML = '<label for="attendable_id" class="block text-sm font-medium text-gray-700 mb-2">Nama <span class="text-red-500">*</span></label>' +
        '<select id="attendable_id" name="attendable_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>' +
        studentOptionsHTML +
        '</select>';

    attendableTypeSelect.addEventListener('change', function() {
        if (this.value === 'App\\Models\\Employee') {
            attendableSelectContainer.innerHTML = employeeDropdownHTML;
        } else if (this.value === 'App\\Models\\Student') {
            attendableSelectContainer.innerHTML = studentDropdownHTML;
        }

        // Update schedule info when attendable type changes
        updateScheduleInfo();
    });
"@

# Replace text
$newContent = $content -replace [regex]::Escape($oldText), $newText

# Write back to file
Set-Content -Path $filePath -Value $newContent -NoNewline

Write-Host "File berhasil diperbaiki!"
Write-Host "Sekarang upload file ini ke production dan jalankan: php artisan view:clear"
