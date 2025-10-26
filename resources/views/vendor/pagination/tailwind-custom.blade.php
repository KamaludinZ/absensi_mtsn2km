@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-col md:flex-row items-center justify-between mt-6 space-y-4 md:space-y-0 pagination-mobile-compact">
        <!-- Pagination Info and Selector -->
        <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-4 w-full md:w-auto">
            <!-- Per Page Selector -->
            <div class="flex items-center space-x-2">
                <label for="per_page" class="text-sm text-gray-700">Tampilkan:</label>
                <select id="per_page" name="per_page" 
                        class="border border-gray-300 rounded px-2 py-1 text-sm focus:ring-blue-500 focus:border-blue-500"
                        onchange="updatePerPage(this)">
                    <option value="10" {{ request('per_page') == '10' ? 'selected' : '' }}>10</option>
                    <option value="20" {{ !request('per_page') || request('per_page') == '20' ? 'selected' : '' }}>20</option>
                    <option value="30" {{ request('per_page') == '30' ? 'selected' : '' }}>30</option>
                    <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100</option>
                    <option value="all" {{ request('per_page') == 'all' ? 'selected' : '' }}>Semua</option>
                </select>
                <span class="text-sm text-gray-700 hidden sm:inline">entri</span>
            </div>

            <!-- Pagination Info -->
            <div class="text-sm text-gray-700">
                Menampilkan 
                <span class="font-medium">{{ $paginator->firstItem() }}</span> 
                sampai 
                <span class="font-medium">{{ $paginator->lastItem() }}</span> 
                dari 
                <span class="font-medium">{{ $paginator->total() }}</span> entri
            </div>
        </div>

        <!-- Pagination Links using Tailwind -->
        <div class="flex items-center space-x-1">
            {{-- Previous Page Link --}}
            <div class="hidden md:flex">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-300 cursor-default rounded-md leading-5">
                    &laquo; Sebelumnya
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" 
                   class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md leading-5 hover:text-gray-500 focus:z-10 focus:outline-none focus:ring-blue-500 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150">
                    &laquo; Sebelumnya
                </a>
            @endif
            </div>

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span class="relative inline-flex items-center px-2 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hidden md:flex">
                        {{ $element }}
                    </span>
                @endif

                {{-- Array Of Links - Mobile optimized --}}
                @if (is_array($element))
                    @php
                        // Calculate visible range for mobile (sliding window of 6 pages)
                        $currentPage = $paginator->currentPage();
                        $lastPage = $paginator->lastPage();
                        
                        // Define max pages to show
                        $maxPagesToShow = 6;
                        
                        if ($lastPage <= $maxPagesToShow) {
                            // If total pages is less than or equal to max, show all
                            $start = 1;
                            $end = $lastPage;
                        } else {
                            // Calculate start and end for sliding window
                            $start = max(1, $currentPage - floor($maxPagesToShow / 2));
                            $end = min($lastPage, $start + $maxPagesToShow - 1);
                            
                            // Adjust if window goes beyond last page
                            if ($end - $start + 1 < $maxPagesToShow) {
                                $start = max(1, $end - $maxPagesToShow + 1);
                            }
                        }
                    @endphp
                    
                    {{-- First page link with ellipsis if needed --}}
                    @if ($start > 1)
                        <a href="{{ $paginator->url(1) }}" 
                           class="relative inline-flex items-center px-2 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:text-gray-500 focus:z-10 focus:outline-none focus:ring-blue-500 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 rounded-md">
                            1
                        </a>
                        @if ($start > 2)
                            <span class="relative inline-flex items-center px-1 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md">
                                <span>&hellip;</span>
                            </span>
                        @endif
                    @endif
                    
                    {{-- Page links in range --}}
                    @for ($i = $start; $i <= $end; $i++)
                        @if ($i == $paginator->currentPage())
                            <span class="relative inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-blue-600 border border-blue-600 cursor-default leading-5 rounded-md min-w-[24px] text-center">
                                {{ $i }}
                            </span>
                        @else
                            <a href="{{ $paginator->url($i) }}" 
                               class="relative inline-flex items-center px-2 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:text-gray-500 focus:z-10 focus:outline-none focus:ring-blue-500 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 rounded-md min-w-[24px] text-center">
                                {{ $i }}
                            </a>
                        @endif
                    @endfor
                    
                    {{-- Last page link with ellipsis if needed --}}
                    @if ($end < $lastPage)
                        @if ($end < $lastPage - 1)
                            <span class="relative inline-flex items-center px-1 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md">
                                <span>&hellip;</span>
                            </span>
                        @endif
                        <a href="{{ $paginator->url($lastPage) }}" 
                           class="relative inline-flex items-center px-2 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 leading-5 hover:text-gray-500 focus:z-10 focus:outline-none focus:ring-blue-500 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150 rounded-md">
                            {{ $lastPage }}
                        </a>
                    @endif
                @endif
            @endforeach

            {{-- Next Page Link --}}
            <div class="hidden md:flex">
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" 
                   class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md leading-5 hover:text-gray-500 focus:z-10 focus:outline-none focus:ring-blue-500 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150">
                    Selanjutnya &raquo;
                </a>
            @else
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-300 cursor-default rounded-md leading-5">
                    Selanjutnya &raquo;
                </span>
            @endif
            </div>
            
            {{-- Mobile-friendly Previous/Next buttons for small screens --}}
            <div class="flex md:hidden space-x-1">
                @if (!$paginator->onFirstPage())
                    <a href="{{ $paginator->previousPageUrl() }}" 
                       class="relative inline-flex items-center px-2 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md leading-5 hover:text-gray-500">
                        &lsaquo;
                    </a>
                @endif
                
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" 
                       class="relative inline-flex items-center px-2 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md leading-5 hover:text-gray-500">
                        &rsaquo;
                    </a>
                @endif
            </div>
        </div>
    </nav>

    <!-- JavaScript for per_page selector -->
    <script>
        function updatePerPage(selectElement) {
            const perPage = selectElement.value;
            const url = new URL(window.location);
            const params = new URLSearchParams(url.search);
            
            // Update or remove per_page parameter
            if (perPage && perPage !== '20') { // 20 is default
                params.set('per_page', perPage);
            } else {
                params.delete('per_page');
            }
            
            // Reset to first page when changing per_page
            params.delete('page');
            
            // Update URL and reload
            url.search = params.toString();
            window.location.href = url.toString();
        }
    </script>
@endif