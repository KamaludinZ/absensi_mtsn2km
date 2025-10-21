<header class="bg-white shadow-sm">
    <div class="flex items-center justify-between px-6 py-4">
        <div class="flex items-center">
            <!-- Mobile menu button -->
            <button type="button" class="md:hidden mr-3 text-gray-500 hover:text-gray-700 focus:outline-none" onclick="toggleSidebar()">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <!-- Desktop collapse/expand button -->
            <button type="button" class="hidden md:block mr-3 text-gray-500 hover:text-gray-700 focus:outline-none" onclick="toggleSidebar()">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <h2 class="text-2xl font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h2>
        </div>

        <div class="flex items-center space-x-6">
            <div class="text-right hidden md:block">
                <p class="text-sm font-medium text-gray-600" id="current-time">--:--:--</p>
                <p class="text-xs text-gray-500" id="current-date">-- -- -----</p>
            </div>
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="flex items-center space-x-2 text-gray-700 hover:text-gray-900">
                    <span>{{ auth()->user()->name }}</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                    <div class="px-4 py-2 text-sm text-gray-700 border-b">
                        <p class="font-semibold">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500">{{ ucfirst(auth()->user()->role->name) }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>