<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database. Every seeder is idempotent and safe to re-run.
     * Catalog and content seeders insert clearly marked DEMONSTRATION data.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            SettingsSeeder::class,
            MediaSeeder::class,
            CatalogSeeder::class,
            ContentSeeder::class,
            HomepageLayoutSeeder::class,
        ]);
    }
}
