<?php

return [

    /*
    |--------------------------------------------------------------------------
    | First-time admin account setup
    |--------------------------------------------------------------------------
    |
    | The website owner creates their own administrator account at /admin/setup.
    | Setup is only possible when ADMIN_SETUP_TOKEN is set (a long random value the
    | developer gives the owner privately), and only until the first administrator
    | exists. After that it stays closed unless someone with server access runs
    | `php artisan admin:setup-reset`. Leave the token empty to disable setup.
    |
    */

    'setup_token' => env('ADMIN_SETUP_TOKEN'),

    // Shortest setup token accepted (shorter values keep setup disabled).
    'setup_token_min_length' => 24,

];
