<?php
declare(strict_types=1);
ini_set('display_errors','0');
set_exception_handler(function(Throwable $error): void {
    // Never include submitted values, SQL, credentials or health information in logs/responses.
    error_log('Fitness application error: '.get_class($error));
    if (PHP_SAPI === 'cli') { fwrite(STDERR,"Applicatiefout. Controleer configuratie, database en vereiste extensies.\n"); exit(1); }
    http_response_code($error instanceof InvalidArgumentException ? 422 : 500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="nl"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Controleer invoer</title><h1>'.($error instanceof InvalidArgumentException ? 'Controleer je invoer' : 'Even niet beschikbaar').'</h1><p>'.htmlspecialchars($error instanceof InvalidArgumentException ? $error->getMessage() : 'Opslaan is niet gelukt. Probeer het later opnieuw of controleer de installatie.',ENT_QUOTES,'UTF-8').'</p><p>Ga terug in je browser om je invoer te controleren.</p></html>';
});
$settings = require __DIR__.'/../config/config.example.php';
if (is_file(__DIR__.'/../config/config.php')) $settings = array_replace($settings,require __DIR__.'/../config/config.php');
foreach ($settings as $key=>$value) {
    $env = getenv($key);
    if ($env !== false) $settings[$key] = is_bool($value) ? filter_var($env,FILTER_VALIDATE_BOOLEAN) : $env;
}
function config(string $key) { global $settings; return $settings[$key] ?? null; }
date_default_timezone_set(config('TIMEZONE'));
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';
require_once __DIR__.'/auth.php';
if (PHP_SAPI !== 'cli') {
    header('Cache-Control: no-store, private');
    header('Referrer-Policy: no-referrer');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-Robots-Tag: noindex, nofollow');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    if (config('REQUIRE_HTTPS') && !$https) { http_response_code(400); exit('Gebruik HTTPS. Controleer eventueel de HTTPS-instelling van de webserver.'); }
    if ($https) header('Strict-Transport-Security: max-age=31536000');
    if (!defined('MOBILE_API')) {
    ini_set('session.use_strict_mode','1'); ini_set('session.use_only_cookies','1');
    session_name('fitness_session');
    session_set_cookie_params(['lifetime'=>0,'path'=>url(''),'secure'=>$https,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
    if (isset($_SESSION['last_activity']) && time()-(int)$_SESSION['last_activity'] > (int)config('SESSION_IDLE_SECONDS')) { $_SESSION=[]; session_regenerate_id(true); }
    $_SESSION['last_activity']=time();
    }
}
