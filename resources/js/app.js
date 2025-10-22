import './bootstrap';
import './sidebar';

// Import Alpine.js with optimizations
import Alpine from 'alpinejs';

// Configure Alpine for better performance
Alpine.store('app', {
    loading: false,
    setLoading(value) {
        this.loading = value;
    }
});

// Make Alpine globally available
window.Alpine = Alpine;

// Start Alpine with deferred rendering for better initial load
Alpine.start();
