<?php
// Copy to config.php (outside public/). Environment variables override these defaults.
return [
    'DB_HOST' => '127.0.0.1', 'DB_PORT' => '3306',
    'DB_NAME' => 'fitness', 'DB_USER' => 'fitness', 'DB_PASSWORD' => '',
    'ADMIN_USERNAME' => 'martijn', 'ADMIN_PASSWORD_HASH' => '',
    'HEVY_API_KEY' => '',
    'HEVY_IMPORT_FROM_DATE' => '2026-08-31',
    'DASHBOARD_FROM_DATE' => '2026-08-31',
    'APP_URL' => 'https://fitness.example.nl',
    'TIMEZONE' => 'Europe/Amsterdam',
    'REQUIRE_HTTPS' => true, // false ONLY for local development
    'SESSION_IDLE_SECONDS' => 1800,
];
