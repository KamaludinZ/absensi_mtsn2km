<header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
    <div class="flex items-center justify-between px-4 sm:px-6 py-3">
        <div class="flex items-center min-w-0 flex-1">
            <!-- Mobile menu button -->
            <button type="button"
                    class="md:hidden mr-2 sm:mr-3 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg p-2 transition-colors"
                    x-data
                    @click="$dispatch('toggle-mobile-sidebar')"
                    aria-label="Toggle menu">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <!-- Desktop collapse/expand button -->
            <button type="button"
                    class="hidden md:block mr-3 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg p-2 transition-colors"
                    x-data
                    @click="$dispatch('toggle-collapse')"
                    aria-label="Toggle sidebar">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <div class="min-w-0">
                <h2 class="text-lg sm:text-xl md:text-2xl font-semibold text-gray-800 truncate">@yield('page-title', 'Dashboard')</h2>
                <p class="text-xs sm:text-sm text-gray-500 hidden sm:block truncate">@yield('page-description', '')</p>
            </div>
        </div>

        <div class="flex items-center space-x-2 sm:space-x-4">
            <!-- Time and date -->
            <div class="text-right hidden lg:block">
                <p class="text-sm font-medium text-gray-700" id="current-time">--:--:--</p>
                <p class="text-xs text-gray-500" id="current-date">-- -- -----</p>
            </div>

            <!-- User profile dropdown -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open"
                        class="flex items-center space-x-1 sm:space-x-2 text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-full p-1 transition-colors"
                        aria-label="User menu"
                        :aria-expanded="open">
                    <div class="relative">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center text-white font-semibold text-sm sm:text-base">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="absolute bottom-0 right-0 w-2.5 h-2.5 sm:w-3 sm:h-3 bg-green-500 rounded-full border-2 border-white"></div>
                    </div>
                    <span class="hidden sm:inline text-sm font-medium truncate max-w-[120px]">{{ Str::limit(auth()->user()->name, 12) }}</span>
                    <svg class="hidden sm:block w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="open"
                     @click.away="open = false"
                     @keydown.escape.window="open = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="transform opacity-0 scale-95"
                     x-transition:enter-end="transform opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="transform opacity-100 scale-100"
                     x-transition:leave-end="transform opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-56 sm:w-64 bg-white rounded-xl shadow-lg py-2 z-50 border border-gray-200"
                     style="display: none;">
                    <div class="px-4 py-3 border-b border-gray-100">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center text-white font-semibold text-lg mr-3">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">{{ auth()->user()->name }}</p>
                                <p class="text-sm text-gray-600">{{ ucfirst(auth()->user()->role->name) }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="py-1">
                        @if(auth()->user()->role->name === 'admin')
                        <a href="{{ route('admin.users.edit', auth()->user()) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Pengaturan Profil
                            </div>
                        </a>

                        <a href="{{ route('admin.security.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Pengaturan Keamanan
                            </div>
                        </a>
                        @endif
                    </div>
                    
                    <div class="border-t border-gray-100 mt-2 pt-2">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-800 transition-colors">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Keluar
                                </div>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>