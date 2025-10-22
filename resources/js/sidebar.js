// Optimized sidebar functionality
(function() {
    'use strict';

    let resizeTimer;
    const RESIZE_DEBOUNCE_MS = 150;

    // Cache DOM elements
    const getElements = () => ({
        sidebar: document.getElementById('sidebar'),
        overlay: document.getElementById('overlay'),
        body: document.body
    });

    // Toggle sidebar visibility (mobile)
    const toggleMobileSidebar = () => {
        const { sidebar, overlay } = getElements();
        if (sidebar && overlay) {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
            // Prevent body scroll when sidebar is open on mobile
            document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
        }
    };

    // Toggle sidebar collapse (desktop)
    const toggleCollapse = () => {
        if (window.innerWidth < 768) return;

        const { sidebar, body } = getElements();
        if (!sidebar) return;

        const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
        sidebar.classList.toggle('sidebar-collapsed', !isCollapsed);
        body.classList.toggle('sidebar-collapsed', !isCollapsed);
        localStorage.setItem('sidebarCollapsed', !isCollapsed);
    };

    // Handle resize with debounce
    const handleResize = () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            const { sidebar, overlay, body } = getElements();
            const isDesktop = window.innerWidth >= 768;

            if (isDesktop) {
                // Desktop: close mobile sidebar, restore collapse state
                if (sidebar) sidebar.classList.remove('show');
                if (overlay) overlay.classList.remove('show');
                document.body.style.overflow = '';

                // Restore saved collapsed state
                const savedState = localStorage.getItem('sidebarCollapsed') === 'true';
                if (sidebar) sidebar.classList.toggle('sidebar-collapsed', savedState);
                body.classList.toggle('sidebar-collapsed', savedState);
            } else {
                // Mobile: remove desktop collapsed state
                if (sidebar) sidebar.classList.remove('sidebar-collapsed');
                body.classList.remove('sidebar-collapsed');
            }
        }, RESIZE_DEBOUNCE_MS);
    };

    // Update clock in navbar
    const updateClock = () => {
        const timeElement = document.getElementById('current-time');
        const dateElement = document.getElementById('current-date');

        if (!timeElement && !dateElement) return;

        const now = new Date();

        if (timeElement) {
            const time = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            timeElement.textContent = time;
        }

        if (dateElement) {
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const date = now.toLocaleDateString('id-ID', options);
            dateElement.textContent = date;
        }
    };

    // Initialize on DOM ready
    const init = () => {
        // Listen for custom events
        document.addEventListener('toggle-mobile-sidebar', toggleMobileSidebar);
        document.addEventListener('toggle-collapse', toggleCollapse);

        // Listen for resize
        window.addEventListener('resize', handleResize, { passive: true });

        // Initialize clock
        updateClock();
        setInterval(updateClock, 1000);

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href !== '#') {
                    const target = document.querySelector(href);
                    if (target) {
                        e.preventDefault();
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            });
        });

        // Form loading states
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    const originalContent = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<svg class="loading-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Processing...';
                    submitBtn.disabled = true;

                    // Re-enable on error
                    setTimeout(() => {
                        if (submitBtn.disabled) {
                            submitBtn.innerHTML = originalContent;
                            submitBtn.disabled = false;
                        }
                    }, 30000);
                }
            });
        });
    };

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();