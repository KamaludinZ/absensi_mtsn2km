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
               } else {
                   sidebar.classList.remove('show');
                   overlay.classList.remove('show');
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
           }
       }">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <div x-show="!collapsed" class="sidebar-logo">
            <div class="logo-icon">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <div class="logo-text">
                <h1 class="logo-title">MTsN 2 Kota Malang</h1>
                <p class="logo-subtitle">Sistem Absensi</p>
            </div>
        </div>
        <div x-show="collapsed" class="sidebar-logo-mini">
            <span class="logo-mini-text">M2</span>
        </div>
        
        <!-- Desktop collapse button -->
        <template x-if="!isMobile">
            <button @click="toggleCollapse()" class="collapse-btn" :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'" aria-label="Toggle sidebar">
                <svg :class="{'rotate-180': collapsed}" class="w-5 h-5 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
        </template>
        
        <!-- Mobile close button -->
        <template x-if="isMobile">
            <button @click="toggleMobileSidebar()" class="mobile-close-btn md:hidden text-gray-300 hover:text-white p-2 rounded-lg hover:bg-blue-700 transition-colors">
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
            <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </div>
                <span class="nav-text">Dashboard</span>
            </a>

            <!-- Data Pegawai Group -->
            <div class="nav-group" x-data="{ open: false }">
                <button @click="open = !open" class="nav-group-header" :aria-expanded="open" aria-controls="pegawai-submenu">
                    <div class="nav-group-title">
                        <div class="nav-icon">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <span class="nav-text">Data Pegawai</span>
                        <span class="badge">{{ App\Models\Employee::where('is_active', true)->count() }}</span>
                    </div>
                    <svg :class="{'rotate-180': open}" class="nav-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 transform translate-y-0"
                     x-transition:leave-end="opacity-0 transform -translate-y-2"
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
                    <a href="{{ route('admin.employees.index') }}" class="nav-sub-item {{ request()->routeIs('admin.employees.*') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        <span class="nav-text">Kelola Data Pegawai</span>
                    </a>
                    <a href="{{ route('admin.reports.index') }}" class="nav-sub-item {{ request()->routeIs('admin.reports.*') && !request()->routeIs('admin.reports.students') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="nav-text">Report Absensi</span>
                    </a>
                    <a href="{{ route('admin.permissions.index') }}" class="nav-sub-item {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span class="nav-text">Perizinan</span>
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
            <div class="nav-group" x-data="{ open: false }">
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
                        <span class="badge">{{ App\Models\Student::where('is_active', true)->count() }}</span>
                    </div>
                    <svg :class="{'rotate-180': open}" class="nav-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 transform translate-y-0"
                     x-transition:leave-end="opacity-0 transform -translate-y-2"
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
            <div class="nav-group" x-data="{ open: false }">
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
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 transform translate-y-0"
                     x-transition:leave-end="opacity-0 transform -translate-y-2"
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span class="nav-text">Laporan Hari Kerja</span>
                    </a>
                </div>
            </div>

        @elseif(auth()->user()->isEmployee())
            <!-- Employee Menu -->
            <a href="{{ route('employee.dashboard') }}" class="nav-item {{ request()->routeIs('employee.dashboard') ? 'active' : '' }}">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </div>
                <span class="nav-text">Dashboard</span>
            </a>

            <a href="{{ route('employee.attendance.index') }}" class="nav-item {{ request()->routeIs('employee.attendance.index') ? 'active' : '' }}">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="nav-text">Absen Pegawai</span>
            </a>
            <a href="{{ route('employee.attendance.report') }}" class="nav-item {{ request()->routeIs('employee.attendance.report') ? 'active' : '' }}">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <span class="nav-text">Report Absensi</span>
            </a>
            <a href="{{ route('employee.permission.index') }}" class="nav-item {{ request()->routeIs('employee.permission.*') ? 'active' : '' }}">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <span class="nav-text">Perizinan</span>
            </a>

        @elseif(auth()->user()->isMonitoring())
            <!-- Monitoring Menu -->
            <a href="{{ route('monitoring.dashboard') }}" class="nav-item {{ request()->routeIs('monitoring.dashboard') ? 'active' : '' }}">
                <div class="nav-icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </div>
                <span class="nav-text">Dashboard</span>
            </a>

            <div class="nav-group" x-data="{ open: false }">
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
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 transform translate-y-0"
                     x-transition:leave-end="opacity-0 transform -translate-y-2"
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
                </div>
            </div>
        @endif
    </nav>
</aside>

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="overlay" onclick="toggleSidebar()"></div>

<style>
/* Modern Sidebar Styles */
.sidebar-container {
    @apply fixed top-0 left-0 h-screen w-64 bg-gradient-to-b from-blue-900 via-blue-800 to-blue-900 text-white shadow-2xl z-40 flex flex-col transition-all duration-300;
}

@media (max-width: 768px) {
    .sidebar-container {
        transform: translateX(-100%);
        z-index: 50;
    }
    .sidebar-container.show {
        transform: translateX(0);
    }
}

/* Sidebar Header */
.sidebar-header {
    @apply flex items-center justify-between p-4 border-b border-blue-700 border-opacity-30 backdrop-blur-sm;
}

.sidebar-logo {
    @apply flex items-center space-x-3;
}

.logo-icon {
    @apply w-12 h-12 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-xl flex items-center justify-center shadow-lg;
}

.logo-text {
    @apply flex flex-col;
}

.logo-title {
    @apply text-lg font-bold leading-tight bg-gradient-to-r from-white to-blue-200 bg-clip-text text-transparent;
}

.logo-subtitle {
    @apply text-xs text-blue-200 font-medium;
}

.sidebar-logo-mini {
    @apply w-12 h-12 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-xl flex items-center justify-center shadow-lg mx-auto;
}

.logo-mini-text {
    @apply text-xl font-bold;
}

.collapse-btn {
    @apply p-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-50 transition-all duration-200 flex-shrink-0;
}

.mobile-close-btn {
    @apply p-2 rounded-lg hover:bg-blue-700 hover:bg-opacity-50 transition-all duration-200 flex-shrink-0;
}

/* Sidebar Navigation */
.sidebar-nav {
    @apply flex-1 overflow-y-auto py-4 px-2 space-y-1;
    scrollbar-width: thin;
    scrollbar-color: rgba(59, 130, 246, 0.5) transparent;
}

.sidebar-nav::-webkit-scrollbar {
    width: 6px;
}

.sidebar-nav::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-nav::-webkit-scrollbar-thumb {
    @apply bg-blue-600 bg-opacity-50 rounded-full;
}

.sidebar-nav::-webkit-scrollbar-thumb:hover {
    @apply bg-blue-500;
}

/* Navigation Items */
.nav-item {
    @apply flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 relative overflow-hidden group;
}

.nav-item:hover {
    @apply bg-blue-700 bg-opacity-50 transform translate-x-1;
}

.nav-item.active {
    @apply bg-gradient-to-r from-blue-600 to-indigo-600 shadow-lg;
}

.nav-item.active::before {
    content: '';
    @apply absolute left-0 top-0 bottom-0 w-1 bg-white rounded-r;
}

.nav-icon {
    @apply flex-shrink-0;
}

.nav-text {
    @apply text-sm font-medium transition-all duration-200;
}

.sidebar-collapsed .nav-text {
    @apply hidden;
}

/* Navigation Groups */
.nav-group {
    @apply space-y-1;
}

.nav-group-header {
    @apply w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 hover:bg-blue-700 hover:bg-opacity-50 group;
    cursor: pointer;
}

.nav-group-title {
    @apply flex items-center space-x-3 flex-1;
}

.nav-arrow {
    @apply w-4 h-4 transition-transform duration-200 flex-shrink-0;
}

.sidebar-collapsed .nav-arrow {
    @apply hidden;
}

.badge {
    @apply px-2 py-0.5 bg-blue-500 bg-opacity-50 rounded-full text-xs font-semibold;
}

.sidebar-collapsed .badge {
    @apply hidden;
}

/* Sub Items */
.nav-sub-items {
    @apply pl-4 space-y-1 mt-1;
}

.nav-sub-item {
    @apply flex items-center space-x-2 px-4 py-2.5 rounded-lg transition-all duration-200 text-sm hover:bg-blue-700 hover:bg-opacity-30 relative group;
}

.nav-sub-item:hover {
    @apply transform translate-x-1;
}

.nav-sub-item.active {
    @apply bg-blue-600 bg-opacity-40 text-white font-medium;
}

.nav-sub-item.active::before {
    content: '';
    @apply absolute left-0 top-1/2 transform -translate-y-1/2 w-0.5 h-4 bg-white rounded;
}

/* Collapsed State */
.sidebar-collapsed .sidebar-container {
    @apply w-20;
}

.sidebar-collapsed .nav-sub-items {
    @apply hidden;
}

.sidebar-collapsed .nav-group-header {
    @apply justify-center;
}

.sidebar-collapsed .sidebar-logo {
    @apply hidden;
}

/* Mobile Overlay */
.sidebar-overlay {
    @apply fixed inset-0 bg-black bg-opacity-50 z-30 hidden backdrop-blur-sm;
}

@media (max-width: 768px) {
    .sidebar-overlay.show {
        @apply block;
    }
}

/* Animations */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.nav-item, .nav-sub-item {
    animation: slideIn 0.3s ease-out;
}

/* Smooth transition for all sidebar elements */
.sidebar-container * {
    @apply transition-colors duration-200;
}
</style>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
}

// Improved mobile sidebar handling
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    
    // Close sidebar when clicking on overlay
    overlay.addEventListener('click', toggleSidebar);
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth < 768 && 
            sidebar.classList.contains('show') && 
            !sidebar.contains(event.target) && 
            !event.target.closest('button[onclick="toggleSidebar()"]')) {
            toggleSidebar();
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
});
</script>