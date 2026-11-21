<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Re-open first-time admin setup (/admin/setup) once, e.g. to hand the site over to its owner. Needs server
// access (that is the "super-admin" authorization) and ADMIN_SETUP_TOKEN in .env. Never changes existing
// administrators or passwords; setup closes again as soon as the new account is created.
Artisan::command('admin:setup-reset {--force : Skip the confirmation question}', function () {
    if (! \App\Support\AdminSetup::tokenConfigured()) {
        $this->error('ADMIN_SETUP_TOKEN is not set (or shorter than '.config('admin.setup_token_min_length').' characters) in .env — setup cannot be opened.');

        return 1;
    }
    if (! $this->option('force') && ! $this->confirm('Allow one new administrator to be created at /admin/setup?')) {
        $this->line('Cancelled — nothing changed.');

        return 0;
    }

    \App\Support\AdminSetup::reopen();
    $this->info('Admin setup is open for one new account at '.route('admin.setup').'. Existing admins and passwords are unchanged.');

    return 0;
})->purpose('Re-open first-time admin account setup once (requires ADMIN_SETUP_TOKEN)');
