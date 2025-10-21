<aside class="w-64 bg-blue-800 text-white min-h-screen fixed md:relative z-40 transform transition-all duration-300 ease-in-out sidebar" 
       id="sidebar" 
       x-data="{ 
           open: false,
           activeDropdown: null,
           collapsed: false,
           toggleDropdown(dropdownName) {
               this.activeDropdown = this.activeDropdown === dropdownName ? null : dropdownName;
           },
           toggleCollapse() {
               this.collapsed = !this.collapsed;
               document.body.classList.toggle('sidebar-collapsed', this.collapsed);
           }
       }">
    <div class="p-4 flex items-center justify-between">
        <div :class="{'hidden': collapsed}" class="sidebar-full">
            <h1 class="text-xl font-bold">MTsN 2 Kota Malang</h1>
            <p class="text-sm text-blue-200">Sistem Absensi</p>
        </div>
        <div class="sidebar-icon-only">
            <div class="text-center">
                <span class="text-xl font-bold">M2</span>
            </div>
        </div>
        <button @click="toggleCollapse()" class="text-white hover:text-blue-300 focus:outline-none sidebar-full">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>
        <button @click="toggleCollapse()" class="text-white hover:text-blue-300 focus:outline-none sidebar-icon-only hidden">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>

    <nav class="mt-8">
        @if(auth()->user()->isAdmin())
            <!-- Admin Dashboard -->
            <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('admin.dashboard') ? 'bg-blue-700' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z" />
                </svg>
                <span class="sidebar-text">Dashboard</span>
            </a>
            
            <!-- Data Pegawai Dropdown -->
            <div x-data="{ open: false }" class="mt-2">
                <button @click="open = !open; $dispatch('dropdown-toggled', { name: 'pegawai' })" class="w-full flex items-center justify-between px-4 py-2 hover:bg-blue-700">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                        </svg>
                        <span class="sidebar-text">DATA PEGAWAI</span>
                    </div>
                    <svg :class="{'rotate-180': open, 'sidebar-text': true}" class="w-5 h-5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 max-h-0" x-transition:enter-end="opacity-100 max-h-96" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 max-h-96" x-transition:leave-end="opacity-0 max-h-0" style="display: none;" class="overflow-hidden">
                    <a href="{{ route('admin.schedules.custom.create') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('admin.schedules.custom.create') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        <span class="sidebar-text">Tambah Data Shift</span>
                        <span class="sidebar-icon-only hidden">+</span>
                    </a>
                    <a href="{{ route('admin.schedules.custom.pegawai') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('admin.schedules.custom.pegawai') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-2.572 1.065c-.94 1.543.826 3.31 2.37 2.37.996-.608 2.296.719 1.688 1.716a11.586 11.586 0 01-5.694 5.694c-1.543.94-3.91-.592-3.35-2.572a1.724 1.724 0 00-2.572-1.065c-1.543.94-3.91-.592-3.35-2.572s-2.572-3.35-1.065-3.35a1.724 1.724 0 00-1.066-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.572c.94-1.543-.826-3.31-2.37-2.37a1.724 1.724 0 00-2.573-1.066c-.94-1.543.826-3.31 2.37-2.37.996-.608 2.296.719 1.688 1.716a11.586 11.586 0 015.694-5.694c1.543-.94 3.91.592 3.35 2.572z" />
                        </svg>
                        <span class="sidebar-text">Pengaturan Absen Pegawai</span>
                        <span class="sidebar-icon-only hidden">⚙️</span>
                    </a>
                    <a href="{{ route('admin.employee-shifts.index') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('admin.employee-shifts.*') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="sidebar-text">Pengaturan Shift</span>
                        <span class="sidebar-icon-only hidden">📅</span>
                    </a>
                    <a href="{{ route('admin.employees.index') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('admin.employees.*') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        <span class="sidebar-text">Tambah Data Pegawai</span>
                        <span class="sidebar-icon-only hidden">👥</span>
                    </a>
                    <a href="{{ route('admin.reports.index') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('admin.reports.*') && !request()->routeIs('admin.reports.students') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="sidebar-text">Report Absen Pegawai</span>
                        <span class="sidebar-icon-only hidden">📋</span>
                    </a>
                    <a href="{{ route('admin.permissions.index') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('admin.permissions.*') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span class="sidebar-text">Perizinan Pegawai</span>
                        <span class="sidebar-icon-only hidden">📝</span>
                    </a>
                    <a href="{{ route('admin.exports.employees') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('admin.exports.employees') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span class="sidebar-text">Export Absensi Pegawai</span>
                        <span class="sidebar-icon-only hidden">📤</span>
                    </a>
                </div>
            </div>

            <!-- Data Siswa Dropdown -->
            <div x-data="{ open: false }" class="mt-2">
                <button @click="open = !open; $dispatch('dropdown-toggled', { name: 'siswa' })" class="w-full flex items-center justify-between px-4 py-2 hover:bg-blue-700">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span class="sidebar-text">DATA SISWA</span>
                    </div>
                    <svg :class="{'rotate-180': open, 'sidebar-text': true}" class="w-5 h-5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 max-h-0" x-transition:enter-end="opacity-100 max-h-96" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 max-h-96" x-transition:leave-end="opacity-0 max-h-0" style="display: none;" class="overflow-hidden">
                    <a href="{{ route('admin.grades.index') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('admin.grades.*') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <span class="sidebar-text">Penambahan Tingkat Kelas</span>
                        <span class="sidebar-icon-only hidden">📚</span>
                    </a>
                    <a href="{{ route('admin.rombels.index') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('admin.rombels.*') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4m10 6l6-6m-6-6l6 6" />
                        </svg>
                        <span class="sidebar-text">Penambahan Rombel</span>
                        <span class="sidebar-icon-only hidden">🏫</span>
                    </a>
                    <a href="{{ route('admin.students.index') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('admin.students.*') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        <span class="sidebar-text">Tambah Data Siswa</span>
                        <span class="sidebar-icon-only hidden">🎓</span>
                    </a>
                    <a href="{{ route('admin.schedules.custom.siswa') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('admin.schedules.custom.siswa') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-2.572 1.065c-.94 1.543.826 3.31 2.37 2.37.996-.608 2.296.719 1.688 1.716a11.586 11.586 0 01-5.694 5.694c-1.543.94-3.91-.592-3.35-2.572a1.724 1.724 0 00-2.572-1.065c-1.543.94-3.91-.592-3.35-2.572s-2.572-3.35-1.065-3.35a1.724 1.724 0 00-1.066-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.572c.94-1.543-.826-3.31-2.37-2.37a1.724 1.724 0 00-2.573-1.066c-.94-1.543.826-3.31 2.37-2.37.996-.608 2.296.719 1.688 1.716a11.586 11.586 0 015.694-5.694c1.543-.94 3.91.592 3.35 2.572z" />
                        </svg>
                        <span class="sidebar-text">Pengaturan Absen Siswa</span>
                        <span class="sidebar-icon-only hidden">⚙️</span>
                    </a>
                    <a href="{{ route('admin.reports.students') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('admin.reports.students') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="sidebar-text">Report Absen Siswa</span>
                        <span class="sidebar-icon-only hidden">📋</span>
                    </a>
                    <a href="{{ route('admin.permissions.students') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('admin.permissions.students') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span class="sidebar-text">Perizinan Siswa</span>
                        <span class="sidebar-icon-only hidden">📝</span>
                    </a>
                    <a href="{{ route('admin.exports.students') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('admin.exports.students') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span class="sidebar-text">Export Absensi Siswa</span>
                        <span class="sidebar-icon-only hidden">📤</span>
                    </a>
                </div>
            </div>

            <!-- Pengaturan Dropdown -->
            <div x-data="{ open: false }" class="mt-2">
                <button @click="open = !open; $dispatch('dropdown-toggled', { name: 'pengaturan' })" class="w-full flex items-center justify-between px-4 py-2 hover:bg-blue-700">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-2.572 1.065c-.94 1.543.826 3.31 2.37 2.37.996-.608 2.296.719 1.688 1.716a11.586 11.586 0 01-5.694 5.694c-1.543.94-3.91-.592-3.35-2.572a1.724 1.724 0 00-2.572-1.065c-1.543.94-3.91-.592-3.35-2.572s-2.572-3.35-1.065-3.35a1.724 1.724 0 00-1.066-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.572c.94-1.543-.826-3.31-2.37-2.37a1.724 1.724 0 00-2.573-1.066c-.94-1.543.826-3.31 2.37-2.37.996-.608 2.296.719 1.688 1.716a11.586 11.586 0 015.694-5.694c1.543-.94 3.91.592 3.35 2.572z" />
                        </svg>
                        <span class="sidebar-text">PENGATURAN</span>
                    </div>
                    <svg :class="{'rotate-180': open, 'sidebar-text': true}" class="w-5 h-5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 max-h-0" x-transition:enter-end="opacity-100 max-h-96" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 max-h-96" x-transition:leave-end="opacity-0 max-h-0" style="display: none;" class="overflow-hidden">
                    <a href="{{ route('admin.users.index') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('admin.users.*') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="sidebar-text">Tambah User Khusus</span>
                        <span class="sidebar-icon-only hidden">👤</span>
                    </a>
                    <a href="{{ route('admin.backup.index') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('admin.backup.*') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12.55a11 11 0 0114.08 0M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="sidebar-text">Backup & Restore</span>
                        <span class="sidebar-icon-only hidden">💾</span>
                    </a>
                    <a href="{{ route('admin.update.index') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('admin.update.*') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span class="sidebar-text">Update Aplikasi</span>
                        <span class="sidebar-icon-only hidden">🔄</span>
                    </a>
                    <a href="{{ route('admin.academic-years.index') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('admin.academic-years.*') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="sidebar-text">Tahun Pelajaran</span>
                        <span class="sidebar-icon-only hidden">📅</span>
                    </a>
                    <a href="{{ route('admin.holidays.index') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('admin.holidays.index') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                        <span class="sidebar-text">Hari Libur</span>
                        <span class="sidebar-icon-only hidden">🗓️</span>
                    </a>
                    <a href="{{ route('admin.holidays.import-form') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('admin.holidays.import-form') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                        </svg>
                        <span class="sidebar-text">Import Hari Libur</span>
                        <span class="sidebar-icon-only hidden">📥</span>
                    </a>
                    <a href="{{ route('admin.holidays.active-days') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('admin.holidays.active-days') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-4 4m0 0l-4-4m4 4V3" />
                        </svg>
                        <span class="sidebar-text">Hari Aktif</span>
                        <span class="sidebar-icon-only hidden">✅</span>
                    </a>
                    <a href="{{ route('admin.holidays.working-days-report') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('admin.holidays.working-days-report') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span class="sidebar-text">Laporan Hari Kerja</span>
                        <span class="sidebar-icon-only hidden">📊</span>
                    </a>
                </div>
            </div>
        @elseif(auth()->user()->isEmployee())
            <!-- Employee Dashboard -->
            <a href="{{ route('employee.dashboard') }}" class="block px-4 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('employee.dashboard') ? 'bg-blue-700' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z" />
                </svg>
                <span class="sidebar-text">Dashboard</span>
            </a>
            
            <!-- Employee Menu Items -->
            <a href="{{ route('employee.attendance.index') }}" class="block px-4 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('employee.attendance.*') ? 'bg-blue-700' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="sidebar-text">Absen Pegawai</span>
            </a>
            <a href="{{ route('employee.attendance.report') }}" class="block px-4 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('employee.attendance.report') ? 'bg-blue-700' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="sidebar-text">Report Absensi</span>
            </a>
            <a href="{{ route('employee.permission.index') }}" class="block px-4 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('employee.permission.*') ? 'bg-blue-700' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span class="sidebar-text">Perizinan Pegawai</span>
            </a>
        @elseif(auth()->user()->isMonitoring())
            <!-- Monitoring Dashboard -->
            <a href="{{ route('monitoring.dashboard') }}" class="block px-4 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('monitoring.dashboard') ? 'bg-blue-700' : '' }}">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z" />
                </svg>
                <span class="sidebar-text">Dashboard</span>
            </a>
            
            <!-- Monitoring Dropdown -->
            <div x-data="{ open: false }">
                <button @click="open = !open; $dispatch('dropdown-toggled', { name: 'monitoring' })" class="w-full flex items-center justify-between px-4 py-2 hover:bg-blue-700">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <span class="sidebar-text">MONITORING</span>
                    </div>
                    <svg :class="{'rotate-180': open, 'sidebar-text': true}" class="w-5 h-5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 max-h-0" x-transition:enter-end="opacity-100 max-h-96" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 max-h-96" x-transition:leave-end="opacity-0 max-h-0" style="display: none;" class="overflow-hidden">
                    <a href="{{ route('monitoring.employees') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('monitoring.employees') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="sidebar-text">Absen Pegawai</span>
                        <span class="sidebar-icon-only hidden">⏱️</span>
                    </a>
                    <a href="{{ route('monitoring.permissions.employees') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('monitoring.permissions.employees') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span class="sidebar-text">Perizinan Pegawai</span>
                        <span class="sidebar-icon-only hidden">📝</span>
                    </a>
                    <a href="{{ route('monitoring.students') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('monitoring.students') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="sidebar-text">Absen Siswa</span>
                        <span class="sidebar-icon-only hidden">⏱️</span>
                    </a>
                    <a href="{{ route('monitoring.permissions.students') }}" class="block px-8 py-2 hover:bg-blue-700 flex items-center {{ request()->routeIs('monitoring.permissions.students') ? 'bg-blue-700' : '' }}">
                        <svg class="w-4 h-4 mr-2 sidebar-icon-only" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span class="sidebar-text">Perizinan Siswa</span>
                        <span class="sidebar-icon-only hidden">📝</span>
                    </a>
                </div>
            </div>
        @endif
    </nav>
</aside>

<!-- Mobile overlay -->
<div class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden" id="overlay" onclick="toggleSidebar()"></div>

<script>
// Listen for dropdown toggle events
document.addEventListener('dropdown-toggled', function(event) {
    // This can be used to track which dropdown is open if needed
    console.log('Dropdown toggled:', event.detail.name);
});
</script>