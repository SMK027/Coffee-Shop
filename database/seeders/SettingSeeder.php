<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Setting::DEFAULTS as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        $allowedIps = Setting::where('key', Setting::KEY_SUPERVISOR_MANAGEMENT_ALLOWED_IPS)->first();
        if ($allowedIps && ! str_contains($allowedIps->value, '172.18.0.1')) {
            $allowedIps->update([
                'value' => trim($allowedIps->value) . "\n172.18.0.1",
            ]);
        }
    }
}
