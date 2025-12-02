<!-- Modern Responsive Sidebar -->
<aside class="sidebar-container"
       id="sidebar"
       x-data="{
           open: false,
           activeDropdown: null,
           collapsed: false,
           isMobile: window.innerWidth < 768,
           toggleDropdown(dropdownName) {
               this.activeDropdown = this.activeDropdown === dropdownName ? null : dropdownName;
           },
           toggleCollapse() {
               if (!this.isMobile) {
                   this.collapsed = !this.collapsed;
                   document.body.classList.toggle('sidebar-collapsed', this.collapsed);
                   localStorage.setItem('sidebarCollapsed', this.collapsed);
               }
           },
           toggleMobileSidebar() {
               this.open = !this.open;
               const sidebar = document.getElementById('sidebar');
               const overlay = document.getElementById('overlay');
               
               if (this.open) {
                   sidebar.classList.add('show');
                   overlay.classList.add('show');
                   // Prevent body scroll when sidebar is open
                   document.body.style.overflow = 'hidden';
               } else {
                   sidebar.classList.remove('show');
                   overlay.classList.remove('show');
                   // Restore body scroll when sidebar is closed
                   document.body.style.overflow = '';
               }
           },
           init() {
               // Restore collapsed state from localStorage
               const savedState = localStorage.getItem('sidebarCollapsed');
               if (savedState === 'true') {
                   this.collapsed = true;
                   document.body.classList.add('sidebar-collapsed');
               }
               
               // Handle resize events
               window.addEventListener('resize', () => {
                   this.isMobile = window.innerWidth < 768;
                   if (this.isMobile) {
                       // On mobile, ensure sidebar is not collapsed
                       this.collapsed = false;
                       document.body.classList.remove('sidebar-collapsed');
                   }
                   // Close mobile sidebar when resizing from mobile to desktop
                   if (!this.isMobile && this.open) {
                       this.open = false;
                       const sidebar = document.getElementById('sidebar');
                       const overlay = document.getElementById('overlay');
                       sidebar.classList.remove('show');
                       overlay.classList.remove('show');
                   }
               });
               
               // Listen for mobile sidebar toggle event from navbar using Alpine's event system
               document.addEventListener('toggle-mobile-sidebar', () => {
                   this.toggleMobileSidebar();
               });
           }
       }">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <div x-show="!collapsed" class="sidebar-logo">
            <div class="logo-container">
                <div class="logo-icon">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <div class="logo-text">
                    <h1 class="logo-title">MTsN 2 Kota Malang</h1>
                    <p class="logo-subtitle">Sistem Absensi</p>
                </div>
            </div>
        </div>
        <div x-show="collapsed" class="sidebar-logo-mini">
            <span class="logo-mini-text">M2</span>
        </div>
        
        <!-- Desktop collapse button -->
        <template x-if="!isMobile">
            <button @click="toggleCollapse()" 
                    class="collapse-btn" 
                    :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'" 
                    aria-label="Toggle sidebar"
                    title="Toggle Sidebar">
                <svg :class="{'rotate-180': collapsed}" 
                     class="w-5 h-5 transition-transform duration-300" 
                     fill="none" 
                     stroke="currentColor" 
                     viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            </button>
        </template>
        
        <!-- Mobile close button -->
        <template x-if="isMobile">
            <button @click="toggleMobileSidebar()" 
                    class="mobile-close-btn md:hidden text-gray-300 hover:text-white p-2 rounded-lg hover:bg-blue-700 transition-colors"
                    title="Close Sidebar">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </template>
    </div>

    <!-- Sidebar Navigation -->
    <nav class="sidebar-nav">
        @if(auth()->user()->isAdmin())
            <!-- Dashboard -->
            <a href="{{ route('admin.dashboard') }}" 
               class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
               :class="{'pl-8': collapsed}">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </div>
                <span class="nav-text">Dashboard</span>
            </a>

            <!-- Data Pegawai Group -->
            <div class="nav-group" x-data="{ open: @if(request()->routeIs('admin.schedules.custom.*') || request()->routeIs('admin.employee-shifts.*') || request()->routeIs('admin.employees.*') || request()->routeIs('admin.reports.meal-allowance') || request()->routeIs('admin.reports.transport-allowance') || request()->routeIs('admin.reports.performance-allowance') || request()->routeIs('admin.reports.salary') || request()->routeIs('admin.permissions.pegawai') || request()->routeIs('admin.attendance-corrections.*') || request()->routeIs('admin.exports.employees')) true @else false @endif }">
                <button @click="open = !open" class="nav-group-header" :aria-expanded="open" aria-controls="pegawai-submenu">
                    <div class="nav-group-title">
                        <div class="nav-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <span class="nav-text">Data Pegawai</span>
                        <span class="badge" x-show="!collapsed">{{ App\Models\Employee::where('is_active', true)->count() }}</span>
                    </div>
                    <svg :class="{'rotate-180': open}" class="nav-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 max-h-0 scale-y-95"
                     x-transition:enter-end="opacity-100 max-h-[1000px] scale-y-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 max-h-[1000px] scale-y-100"
                     x-transition:leave-end="opacity-0 max-h-0 scale-y-95"
                     class="nav-sub-items"
                     id="pegawai-submenu"
                     role="region"
                     aria-labelledby="pegawai-group"
                     style="display: none;">
                    <a href="{{ route('admin.schedules.custom.create') }}" class="nav-sub-item {{ request()->routeIs('admin.schedules.custom.create') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        <span class="nav-text">Tambah Data Shift</span>
                    </a>
                    <a href="{{ route('admin.schedules.custom.pegawai') }}" class="nav-sub-item {{ request()->routeIs('admin.schedules.custom.pegawai') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        <span class="nav-text">Pengaturan Absen Pegawai</span>
                    </a>
                    <a href="{{ route('admin.employee-shifts.index') }}" class="nav-sub-item {{ request()->routeIs('admin.employee-shifts.*') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="nav-text">Pengaturan Shift</span>
                    </a>
                    <a href="{{ route('admin.employees.index') }}" class="nav-sub-item {{ request()->routeIs('admin.employees.index') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        <span class="nav-text">Kelola Data Pegawai</span>
                    </a>
                    <a href="{{ route('admin.employees.salary-settings') }}" class="nav-sub-item {{ request()->routeIs('admin.employees.salary-settings') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="nav-text">Pengaturan Gaji</span>
                    </a>
                    <a href="{{ route('admin.reports.index') }}" class="nav-sub-item {{ request()->routeIs('admin.reports.index') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="nav-text">Report Absensi</span>
                    </a>
                    <a href="{{ route('admin.reports.meal-allowance') }}" class="nav-sub-item {{ request()->routeIs('admin.reports.meal-allowance') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                        </svg>
                        <span class="nav-text">Laporan Uang Makan</span>
                    </a>
                    <a href="{{ route('admin.reports.transport-allowance') }}" class="nav-sub-item {{ request()->routeIs('admin.reports.transport-allowance') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        <span class="nav-text">Laporan Uang Transport</span>
                    </a>
                    <a href="{{ route('admin.reports.performance-allowance') }}" class="nav-sub-item {{ request()->routeIs('admin.reports.performance-allowance') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span class="nav-text">Laporan Tunjangan Kinerja</span>
                    </a>
                    <a href="{{ route('admin.reports.salary') }}" class="nav-sub-item {{ request()->routeIs('admin.reports.salary') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H4a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <span class="nav-text">Laporan Gaji</span>
                    </a>
                    <a href="{{ route('admin.permissions.index') }}" class="nav-sub-item {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span class="nav-text">Perizinan</span>
                    </a>
                    <a href="{{ route('admin.attendance-corrections.index') }}" class="nav-sub-item {{ request()->routeIs('admin.attendance-corrections.*') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span class="nav-text">Perbaikan Absensi</span>
                    </a>
                    <a href="{{ route('admin.exports.employees') }}" class="nav-sub-item {{ request()->routeIs('admin.exports.employees') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span class="nav-text">Export Absensi</span>
                    </a>
                </div>
            </div>

            <!-- Data Siswa Group -->
            <div class="nav-group" x-data="{ open: @if(request()->routeIs('admin.grades.*') || request()->routeIs('admin.rombels.*') || request()->routeIs('admin.students.*') || request()->routeIs('admin.schedules.custom.siswa') || request()->routeIs('admin.reports.students') || request()->routeIs('admin.permissions.students') || request()->routeIs('admin.exports.students')) true @else false @endif }">
                <button @click="open = !open" class="nav-group-header" :aria-expanded="open" aria-controls="siswa-submenu">
                    <div class="nav-group-title">
                        <div class="nav-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M12 14l9-5-9-5-9 5 9 5z"/>
                                <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
                            </svg>
                        </div>
                        <span class="nav-text">Data Siswa</span>
                        <span class="badge" x-show="!collapsed">{{ App\Models\Student::where('is_active', true)->count() }}</span>
                    </div>
                    <svg :class="{'rotate-180': open}" class="nav-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 max-h-0 scale-y-95"
                     x-transition:enter-end="opacity-100 max-h-[1000px] scale-y-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 max-h-[1000px] scale-y-100"
                     x-transition:leave-end="opacity-0 max-h-0 scale-y-95"
                     class="nav-sub-items"
                     id="siswa-submenu"
                     role="region"
                     aria-labelledby="siswa-group"
                     style="display: none;">
                    <a href="{{ route('admin.grades.index') }}" class="nav-sub-item {{ request()->routeIs('admin.grades.*') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <span class="nav-text">Tingkat Kelas</span>
                    </a>
                    <a href="{{ route('admin.rombels.index') }}" class="nav-sub-item {{ request()->routeIs('admin.rombels.*') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span class="nav-text">Rombongan Belajar</span>
                    </a>
                    <a href="{{ route('admin.students.index') }}" class="nav-sub-item {{ request()->routeIs('admin.students.*') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        <span class="nav-text">Kelola Data Siswa</span>
                    </a>
                    <a href="{{ route('admin.schedules.custom.siswa') }}" class="nav-sub-item {{ request()->routeIs('admin.schedules.custom.siswa') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        <span class="nav-text">Pengaturan Absen Siswa</span>
                    </a>
                    <a href="{{ route('admin.reports.students') }}" class="nav-sub-item {{ request()->routeIs('admin.reports.students') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="nav-text">Report Absensi</span>
                    </a>
                    <a href="{{ route('admin.permissions.students') }}" class="nav-sub-item {{ request()->routeIs('admin.permissions.students') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span class="nav-text">Perizinan</span>
                    </a>
                    <a href="{{ route('admin.exports.students') }}" class="nav-sub-item {{ request()->routeIs('admin.exports.students') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span class="nav-text">Export Absensi</span>
                    </a>
                </div>
            </div>

            <!-- Pengaturan Group -->
            <div class="nav-group" x-data="{ open: @if(request()->routeIs('admin.users.*') || request()->routeIs('admin.security.*') || request()->routeIs('admin.backup.*') || request()->routeIs('admin.update.*') || request()->routeIs('admin.academic-years.*') || request()->routeIs('admin.holidays.*') || request()->routeIs('admin.location-settings.*') || request()->routeIs('admin.attendance-settings.*') || request()->routeIs('admin.profile.*')) true @else false @endif }">
                <button @click="open = !open" class="nav-group-header" :aria-expanded="open" aria-controls="pengaturan-submenu">
                    <div class="nav-group-title">
                        <div class="nav-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <span class="nav-text">Pengaturan</span>
                    </div>
                    <svg :class="{'rotate-180': open}" class="nav-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 max-h-0 scale-y-95"
                     x-transition:enter-end="opacity-100 max-h-[1000px] scale-y-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 max-h-[1000px] scale-y-100"
                     x-transition:leave-end="opacity-0 max-h-0 scale-y-95"
                     class="nav-sub-items"
                     id="pengaturan-submenu"
                     role="region"
                     aria-labelledby="pengaturan-group"
                     style="display: none;">
                    <a href="{{ route('admin.users.index') }}" class="nav-sub-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="nav-text">User Khusus</span>
                    </a>
                    <a href="{{ route('admin.security.index') }}" class="nav-sub-item {{ request()->routeIs('admin.security.*') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span class="nav-text">Keamanan Sistem</span>
                    </a>
                    <a href="{{ route('admin.backup.index') }}" class="nav-sub-item {{ request()->routeIs('admin.backup.*') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <span class="nav-text">Backup & Restore</span>
                    </a>
                    <a href="{{ route('admin.update.index') }}" class="nav-sub-item {{ request()->routeIs('admin.update.*') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span class="nav-text">Update Aplikasi</span>
                    </a>
                    <a href="{{ route('admin.academic-years.index') }}" class="nav-sub-item {{ request()->routeIs('admin.academic-years.*') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="nav-text">Tahun Pelajaran</span>
                    </a>
                    <a href="{{ route('admin.holidays.index') }}" class="nav-sub-item {{ request()->routeIs('admin.holidays.index') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                        <span class="nav-text">Hari Libur</span>
                    </a>
                    <a href="{{ route('admin.holidays.import-form') }}" class="nav-sub-item {{ request()->routeIs('admin.holidays.import-form') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                        </svg>
                        <span class="nav-text">Import Hari Libur</span>
                    </a>
                    <a href="{{ route('admin.holidays.active-days') }}" class="nav-sub-item {{ request()->routeIs('admin.holidays.active-days') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="nav-text">Hari Aktif</span>
                    </a>
                    <a href="{{ route('admin.holidays.working-days-report') }}" class="nav-sub-item {{ request()->routeIs('admin.holidays.working-days-report') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span class="nav-text">Laporan Hari Kerja</span>
                    </a>
                    <a href="{{ route('admin.location-settings.index') }}" class="nav-sub-item {{ request()->routeIs('admin.location-settings.*') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="nav-text">Pengaturan Lokasi</span>
                    </a>
                    <a href="{{ route('admin.attendance-settings.index') }}" class="nav-sub-item {{ request()->routeIs('admin.attendance-settings.*') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        <span class="nav-text">Pengaturan Absensi</span>
                    </a>
                    <a href="{{ route('admin.profile.change-password') }}" class="nav-sub-item {{ request()->routeIs('admin.profile.*') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                        <span class="nav-text">Ubah Password</span>
                    </a>
                </div>
            </div>

        @elseif(auth()->user()->isEmployee())
            <!-- Employee Menu -->
            <a href="{{ route('employee.dashboard') }}" 
               class="nav-item {{ request()->routeIs('employee.dashboard') ? 'active' : '' }}"
               :class="{'pl-8': collapsed}">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </div>
                <span class="nav-text">Dashboard</span>
            </a>

            <a href="{{ route('employee.attendance.index') }}" 
               class="nav-item {{ request()->routeIs('employee.attendance.index') ? 'active' : '' }}"
               :class="{'pl-8': collapsed}">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="nav-text">Absen Pegawai</span>
            </a>
            <a href="{{ route('employee.attendance.report') }}" 
               class="nav-item {{ request()->routeIs('employee.attendance.report') ? 'active' : '' }}"
               :class="{'pl-8': collapsed}">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <span class="nav-text">Report Absensi</span>
            </a>
            @php
                $showSalaryMenuSetting = \App\Models\AttendanceSetting::where('setting_key', 'show_salary_menu_in_employee')->first();
                $showSalaryMenu = $showSalaryMenuSetting ? filter_var($showSalaryMenuSetting->setting_value, FILTER_VALIDATE_BOOLEAN) : true;
            @endphp
            @if($showSalaryMenu)
            <a href="{{ route('employee.salary.report') }}"
               class="nav-item {{ request()->routeIs('employee.salary.report') ? 'active' : '' }}"
               :class="{'pl-8': collapsed}">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H4a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
                <span class="nav-text">Laporan Gaji</span>
            </a>
            @endif
            <a href="{{ route('employee.permission.index') }}"
               class="nav-item {{ request()->routeIs('employee.permission.*') ? 'active' : '' }}"
               :class="{'pl-8': collapsed}">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <span class="nav-text">Perizinan</span>
            </a>
            <a href="{{ route('employee.attendance-corrections.index') }}"
               class="nav-item {{ request()->routeIs('employee.attendance-corrections.*') ? 'active' : '' }}"
               :class="{'pl-8': collapsed}">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
                <span class="nav-text">Perbaikan Absensi</span>
            </a>

            <a href="{{ route('employee.profile.change-password') }}"
               class="nav-item {{ request()->routeIs('employee.profile.*') ? 'active' : '' }}"
               :class="{'pl-8': collapsed}">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <span class="nav-text">Ubah Password</span>
            </a>

        @elseif(auth()->user()->isMonitoring())
            <!-- Monitoring Menu -->
            <a href="{{ route('monitoring.dashboard') }}" 
               class="nav-item {{ request()->routeIs('monitoring.dashboard') ? 'active' : '' }}"
               :class="{'pl-8': collapsed}">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </div>
                <span class="nav-text">Dashboard</span>
            </a>

            <div class="nav-group" x-data="{ open: @if(request()->routeIs('monitoring.employees') || request()->routeIs('monitoring.permissions.employees') || request()->routeIs('monitoring.students') || request()->routeIs('monitoring.permissions.students') || request()->routeIs('monitoring.reports.*')) true @else false @endif }">
                <button @click="open = !open" class="nav-group-header" :aria-expanded="open" aria-controls="monitoring-submenu">
                    <div class="nav-group-title">
                        <div class="nav-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                        <span class="nav-text">Monitoring</span>
                    </div>
                    <svg :class="{'rotate-180': open}" class="nav-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 max-h-0 scale-y-95"
                     x-transition:enter-end="opacity-100 max-h-[1000px] scale-y-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 max-h-[1000px] scale-y-100"
                     x-transition:leave-end="opacity-0 max-h-0 scale-y-95"
                     class="nav-sub-items"
                     id="monitoring-submenu"
                     role="region"
                     aria-labelledby="monitoring-group"
                     style="display: none;">
                    <a href="{{ route('monitoring.employees') }}" class="nav-sub-item {{ request()->routeIs('monitoring.employees') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="nav-text">Absen Pegawai</span>
                    </a>
                    <a href="{{ route('monitoring.permissions.employees') }}" class="nav-sub-item {{ request()->routeIs('monitoring.permissions.employees') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span class="nav-text">Perizinan Pegawai</span>
                    </a>
                    <a href="{{ route('monitoring.students') }}" class="nav-sub-item {{ request()->routeIs('monitoring.students') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="nav-text">Absen Siswa</span>
                    </a>
                    <a href="{{ route('monitoring.permissions.students') }}" class="nav-sub-item {{ request()->routeIs('monitoring.permissions.students') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span class="nav-text">Perizinan Siswa</span>
                    </a>

                    <!-- Monitoring Reports -->
                    <a href="{{ route('monitoring.reports.salary') }}" class="nav-sub-item {{ request()->routeIs('monitoring.reports.salary') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                        </svg>
                        <span class="nav-text">Monitoring Laporan Gaji</span>
                    </a>
                </div>
            </div>

            <a href="{{ route('monitoring.profile.change-password') }}" class="nav-item {{ request()->routeIs('monitoring.profile.*') ? 'active' : '' }}">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <span class="nav-text">Ubah Password</span>
            </a>
        @endif
    </nav>
</aside>

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="overlay" x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="toggleMobileSidebar()"></div>

<style>
/* Direct CSS for Sidebar - No @apply directives */
.sidebar-container {
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    width: 16rem;
    background: linear-gradient(to bottom, rgb(30, 58, 138), rgb(29, 78, 216), rgb(67, 56, 202));
    color: white;
    z-index: 40;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    display: flex;
    flex-direction: column;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    border-bottom: 1px solid rgba(29, 78, 216, 0.3);
    backdrop-filter: blur(4px);
    min-height: 4.5rem;
    flex-shrink: 0;
}

.sidebar-logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
    min-width: 0;
}

.logo-container {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
}

.logo-icon {
    width: 2.5rem;
    height: 2.5rem;
    background: linear-gradient(to bottom right, rgb(96, 165, 250), rgb(129, 140, 248));
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    flex-shrink: 0;
}

.logo-icon svg {
    width: 1.5rem;
    height: 1.5rem;
}

.logo-text {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.logo-title {
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.25;
    color: white;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.logo-subtitle {
    font-size: 0.75rem;
    color: rgb(191, 219, 254);
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-top: 0.125rem;
}

.sidebar-logo-mini {
    width: 2.5rem;
    height: 2.5rem;
    background: linear-gradient(to bottom right, rgb(96, 165, 250), rgb(129, 140, 248));
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    margin: 0 auto;
}

.logo-mini-text {
    font-size: 1.125rem;
    font-weight: 700;
}

.collapse-btn,
.mobile-close-btn {
    padding: 0.5rem;
    border-radius: 0.5rem;
    transition: all 0.2s;
    flex-shrink: 0;
}

.collapse-btn:hover,
.mobile-close-btn:hover {
    background-color: rgba(29, 78, 216, 0.5);
}

.sidebar-nav {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 1rem 0.75rem;
    scrollbar-width: thin;
    scrollbar-color: rgba(59, 130, 246, 0.5) transparent;
}

.sidebar-nav::-webkit-scrollbar {
    width: 5px;
}

.sidebar-nav::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-nav::-webkit-scrollbar-thumb {
    background-color: rgba(37, 99, 235, 0.5);
    border-radius: 9999px;
}

.sidebar-nav::-webkit-scrollbar-thumb:hover {
    background-color: rgb(59, 130, 246);
}

.nav-item,
.nav-group-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.625rem 0.75rem;
    border-radius: 0.5rem;
    transition: all 0.2s;
    position: relative;
    color: rgb(229, 231, 235);
    text-decoration: none;
}

.nav-group-header {
    justify-content: space-between;
    cursor: pointer;
    width: 100%;
}

.nav-item:hover,
.nav-group-header:hover {
    background-color: rgba(29, 78, 216, 0.4);
    color: white;
    transform: translateX(2px);
}

.nav-item.active {
    background: linear-gradient(to right, rgb(37, 99, 235), rgb(79, 70, 229));
    color: white;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.nav-item.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 0.25rem;
    height: 1.5rem;
    background-color: white;
    border-radius: 0 9999px 9999px 0;
}

.nav-icon {
    width: 1.25rem;
    height: 1.25rem;
    flex-shrink: 0;
}

.nav-text {
    font-size: 0.875rem;
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.nav-group {
    margin-bottom: 0.25rem;
}

.nav-group-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
    min-width: 0;
}

.nav-arrow {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
    transition: transform 0.3s;
}

.badge {
    padding: 0.125rem 0.5rem;
    background-color: rgba(59, 130, 246, 0.8);
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-left: auto;
    flex-shrink: 0;
}

.nav-sub-items {
    padding-left: 2rem;
    margin-top: 0.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    overflow: hidden;
}

.nav-sub-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    transition: all 0.2s;
    font-size: 0.875rem;
    color: rgb(209, 213, 219);
    position: relative;
    text-decoration: none;
}

.nav-sub-item:hover {
    background-color: rgba(29, 78, 216, 0.3);
    color: white;
    transform: translateX(2px);
}

.nav-sub-item.active {
    background-color: rgba(37, 99, 235, 0.6);
    color: white;
    font-weight: 500;
}

.nav-sub-item.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 0.125rem;
    height: 1rem;
    background-color: white;
    border-radius: 9999px;
}

.nav-sub-item svg {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
}

.sidebar-overlay {
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.6);
    z-index: 30;
    backdrop-filter: blur(4px);
    transition: opacity 0.3s;
    display: none;
}

.sidebar-overlay.show {
    display: block;
}

/* Collapsed State */
.sidebar-collapsed .sidebar-container {
    width: 5rem;
}

.sidebar-collapsed .sidebar-logo,
.sidebar-collapsed .logo-text,
.sidebar-collapsed .nav-text,
.sidebar-collapsed .badge,
.sidebar-collapsed .nav-arrow,
.sidebar-collapsed .nav-sub-items {
    display: none !important;
}

.sidebar-collapsed .nav-item,
.sidebar-collapsed .nav-group-header {
    justify-content: center;
    padding-left: 0;
    padding-right: 0;
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
}

.sidebar-collapsed .nav-icon {
    margin: 0 auto;
}

/* Mobile State */
@media (max-width: 767px) {
    .sidebar-container {
        transform: translateX(-100%);
        z-index: 50;
        width: 100%;
        max-width: 18rem;
    }

    .sidebar-container.show {
        transform: translateX(0);
    }

    .sidebar-overlay {
        display: block;
        opacity: 0;
        pointer-events: none;
    }

    .sidebar-overlay.show {
        opacity: 1;
        pointer-events: auto;
    }

    .nav-item,
    .nav-group-header,
    .nav-sub-item {
        min-height: 44px;
        padding-top: 0.75rem;
        padding-bottom: 0.75rem;
    }

    .nav-text {
        font-size: 0.9375rem;
    }
}

/* Desktop Only */
@media (min-width: 768px) {
    .sidebar-overlay {
        display: none !important;
    }
}

/* Accessibility */
.nav-item:focus,
.nav-group-header:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgb(96, 165, 250);
}
</style>

<script>
// Improved mobile sidebar handling
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    
    // Close sidebar when clicking on overlay
    overlay.addEventListener('click', function() {
        // Dispatch event to Alpine to handle state properly
        window.dispatchEvent(new CustomEvent('toggle-mobile-sidebar'));
    });
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth < 768 && 
            sidebar.classList.contains('show') && 
            !sidebar.contains(event.target) && 
            !event.target.closest('[x-on\\:click*="toggleMobileSidebar"]')) {
            // Dispatch event to Alpine to handle state properly
            window.dispatchEvent(new CustomEvent('toggle-mobile-sidebar'));
        }
    });
    
    // Handle resize events to ensure proper mobile/desktop behavior
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            // On desktop view, close mobile sidebar if it's open
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        } else {
            // On mobile view, ensure sidebar is collapsed (not showing)
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        }
    });
    
    // Keyboard accessibility
    document.addEventListener('keydown', function(e) {
        // Close sidebar with Escape key
        if (e.key === 'Escape') {
            if (sidebar.classList.contains('show')) {
                // Dispatch event to Alpine to handle state properly
                window.dispatchEvent(new CustomEvent('toggle-mobile-sidebar'));
            }
        }
    });
});
</script>