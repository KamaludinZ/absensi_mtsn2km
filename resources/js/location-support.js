// Improved cross-browser location access function
async function getLocation(options = {}) {
    const defaultOptions = {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 60000
    };
    
    // Merge user options with defaults
    const geolocationOptions = { ...defaultOptions, ...options };
    
    // Check if geolocation is supported
    if (!navigator.geolocation) {
        throw new Error('Browser Anda tidak mendukung geolokasi.');
    }
    
    try {
        // First, try to get location using the standard API
        return await new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(
                position => resolve(position),
                error => reject(error),
                geolocationOptions
            );
        });
    } catch (error) {
        // If the standard API fails, try alternative methods
        
        // Fallback 1: Try with different accuracy settings
        try {
            const fallbackOptions = {
                ...geolocationOptions,
                enableHighAccuracy: false
            };
            
            return await new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(
                    position => resolve(position),
                    error => reject(error),
                    fallbackOptions
                );
            });
        } catch (fallbackError) {
            // If all geolocation methods fail, throw the original error
            throw error;
        }
    }
}

// Get location with fallbacks for different browsers
async function getLocationWithFallback() {
    try {
        // Try the regular browser geolocation
        return await getLocation();
    } catch (error) {
        // If that fails, try additional fallback methods
        switch (error.code) {
            case error.PERMISSION_DENIED:
                // If user denied permission, we can't really get the location
                throw new Error('Izin lokasi ditolak. Mohon izinkan akses lokasi di pengaturan browser Anda.');
            case error.POSITION_UNAVAILABLE:
                throw new Error('Informasi lokasi tidak tersedia.');
            case error.TIMEOUT:
                // If timeout, try with longer timeout
                try {
                    return await getLocation({ timeout: 20000 });
                } catch {
                    throw new Error('Waktu permintaan lokasi habis.');
                }
            default:
                throw new Error('Terjadi kesalahan saat mendapatkan lokasi: ' + error.message);
        }
    }
}

// Additional helper function to request location permissions in a more consistent way
function requestLocationPermission() {
    // Some browsers require explicit permission before calling geolocation
    if (navigator.permissions) {
        return navigator.permissions.query({ name: 'geolocation' }).then(permission => {
            if (permission.state === 'granted') {
                // Permission already granted
                return getLocation();
            } else if (permission.state === 'prompt') {
                // Permission not yet granted, request it
                return getLocation();
            } else {
                // Permission denied
                throw new Error('Izin lokasi ditolak. Mohon izinkan akses lokasi di pengaturan browser Anda.');
            }
        });
    } else {
        // Fallback for browsers that don't support the permissions API
        return getLocation();
    }
}