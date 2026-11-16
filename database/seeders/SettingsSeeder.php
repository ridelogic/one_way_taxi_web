<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use App\Support\Settings;
use Illuminate\Database\Seeder;

/**
 * Seeds every setting from config/settings.php with its default value (existing values are kept).
 * The phone, email and address defaults are placeholders that must be replaced by the business owner.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('settings') as $group => $definition) {
            foreach ($definition['fields'] as $key => $field) {
                SiteSetting::firstOrCreate(['key' => $key], ['value' => $field['default'] ?? null, 'group' => $group]);
            }
        }

        app(Settings::class)->flush();
    }
}
