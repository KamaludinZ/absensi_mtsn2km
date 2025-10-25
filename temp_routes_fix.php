Route::get('/api/location-settings/geofence-center-lat', [\\App\\Http\\Controllers\\API\\LocationSettingController::class, 'getGeofenceCenterLat']);
Route::get('/api/location-settings/geofence-center-lng', [\\App\\Http\\Controllers\\API\\LocationSettingController::class, 'getGeofenceCenterLng']);
Route::get('/api/location-settings/geofence-radius', [\\App\\Http\\Controllers\\API\\LocationSettingController::class, 'getGeofenceRadius']);
Route::get('/api/location-settings/location-required', [\\App\\Http\\Controllers\\API\\LocationSettingController::class, 'getLocationRequired']);