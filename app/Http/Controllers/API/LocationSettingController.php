<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LocationSetting;
use Illuminate\Http\Request;

class LocationSettingController extends Controller
{
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