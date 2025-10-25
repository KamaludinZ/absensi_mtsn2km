<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LocationSetting;

class LocationSettingsController extends Controller
{
    public function index()
    {
        $geofenceCenterLat = LocationSetting::where('setting_key', 'geofence_center_lat')->first();
        $geofenceCenterLng = LocationSetting::where('setting_key', 'geofence_center_lng')->first();
        $geofenceRadius = LocationSetting::where('setting_key', 'geofence_radius')->first();
        $locationRequired = LocationSetting::where('setting_key', 'location_required')->first();

        $settings = [
            'geofence_center_lat' => $geofenceCenterLat ? $geofenceCenterLat->setting_value : '-6.200000',
            'geofence_center_lng' => $geofenceCenterLng ? $geofenceCenterLng->setting_value : '106.816666',
            'geofence_radius' => $geofenceRadius ? $geofenceRadius->setting_value : '50',
            'location_required' => $locationRequired ? filter_var($locationRequired->setting_value, FILTER_VALIDATE_BOOLEAN) : true,
        ];

        return view('admin.location-settings.index', compact('settings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'geofence_center_lat' => 'required|numeric|between:-90,90',
            'geofence_center_lng' => 'required|numeric|between:-180,180',
            'geofence_radius' => 'required|numeric|min:1',
            'location_required' => 'required|boolean',
        ]);

        // Update or create geofence settings
        LocationSetting::updateOrCreate(
            ['setting_key' => 'geofence_center_lat'],
            [
                'setting_value' => $request->geofence_center_lat,
                'description' => 'Latitude coordinate of geofence center'
            ]
        );

        LocationSetting::updateOrCreate(
            ['setting_key' => 'geofence_center_lng'],
            [
                'setting_value' => $request->geofence_center_lng,
                'description' => 'Longitude coordinate of geofence center'
            ]
        );

        LocationSetting::updateOrCreate(
            ['setting_key' => 'geofence_radius'],
            [
                'setting_value' => $request->geofence_radius,
                'description' => 'Geofence radius in meters'
            ]
        );

        LocationSetting::updateOrCreate(
            ['setting_key' => 'location_required'],
            [
                'setting_value' => $request->location_required ? 'true' : 'false',
                'description' => 'Whether location is required for attendance'
            ]
        );

        return redirect()->route('admin.location-settings.index')->with('success', 'Setting lokasi berhasil diperbarui.');
    }
    
    public function getGeofenceCenterLat()
    {
        $setting = LocationSetting::where('setting_key', 'geofence_center_lat')->first();
        return response()->json([
            'value' => $setting ? $setting->setting_value : '-6.200000'
        ]);
    }
    
    public function getGeofenceCenterLng()
    {
        $setting = LocationSetting::where('setting_key', 'geofence_center_lng')->first();
        return response()->json([
            'value' => $setting ? $setting->setting_value : '106.816666'
        ]);
    }
    
    public function getGeofenceRadius()
    {
        $setting = LocationSetting::where('setting_key', 'geofence_radius')->first();
        return response()->json([
            'value' => $setting ? $setting->setting_value : '50'
        ]);
    }
    
    public function getLocationRequired()
    {
        $setting = LocationSetting::where('setting_key', 'location_required')->first();
        return response()->json([
            'value' => $setting ? $setting->setting_value : 'false'
        ]);
    }
}