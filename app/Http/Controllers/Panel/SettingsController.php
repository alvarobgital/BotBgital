<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return response()->json($settings);
    }

    public function update(Request $request)
    {
        // Handle standard settings sent via JSON or form-data
        $settings = $request->input('settings', []);
        if (is_string($settings)) {
            $settings = json_decode($settings, true) ?? [];
        }

        foreach ($settings as $key => $value) {
            Setting::setValue($key, $value);
        }

        // Handle Logo upload
        if ($request->hasFile('company_logo')) {
            $request->validate([
                'company_logo' => 'image|mimes:jpeg,png,jpg,svg,webp,gif|max:5120',
            ]);

            $path = $request->file('company_logo')->store('logos', 'public');
            Setting::setValue('company_logo', '/storage/' . $path);
        }

        return response()->json(Setting::all()->pluck('value', 'key'));
    }

    public function toggleBot()
    {
        $current = Setting::getValue('bot_enabled', 'true');
        $newValue = $current === 'true' ? 'false' : 'true';
        Setting::setValue('bot_enabled', $newValue);

        return response()->json(['bot_enabled' => $newValue]);
    }
}
