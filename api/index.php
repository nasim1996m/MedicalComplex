<?php

// Vercel entry point: every request is routed here (see vercel.json) and handed to Laravel.

// The deployment is read-only except /tmp. Default every path Laravel writes to into /tmp
// (and log to stderr) here, so the app works even if vercel.json's "env" is not applied.
// Values set in the Vercel dashboard still take precedence.
$defaults = [
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'APP_CONFIG_CACHE' => '/tmp/config.php',
    'APP_EVENTS_CACHE' => '/tmp/events.php',
    'APP_PACKAGES_CACHE' => '/tmp/packages.php',
    'APP_ROUTES_CACHE' => '/tmp/routes.php',
    'APP_SERVICES_CACHE' => '/tmp/services.php',
    'VIEW_COMPILED_PATH' => '/tmp/views',
    'LOG_CHANNEL' => 'stderr',
    'SESSION_DRIVER' => 'cookie',
    'SESSION_SECURE_COOKIE' => 'true',
    'CACHE_STORE' => 'array',
    'QUEUE_CONNECTION' => 'sync',
];
foreach ($defaults as $key => $value) {
    if (getenv($key) === false) {
        putenv("$key=$value");
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
}
foreach (['/tmp/views', '/tmp/storage/framework/cache/data', '/tmp/storage/logs'] as $dir) {
    is_dir($dir) || @mkdir($dir, 0777, true);
}
putenv('LARAVEL_STORAGE_PATH=/tmp/storage');
$_ENV['LARAVEL_STORAGE_PATH'] = $_SERVER['LARAVEL_STORAGE_PATH'] = '/tmp/storage';

// Without these Laravel can only show a generic 500; name what is missing instead (values are never shown).
$missing = array_values(array_filter(['APP_KEY', 'DB_CONNECTION', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'], fn ($k) => getenv($k) === false || getenv($k) === ''));
if ($missing) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Configuration required</title>'
        . '<body style="font-family:sans-serif;max-width:640px;margin:10vh auto;line-height:1.7" dir="rtl">'
        . '<h2>الموقع يحتاج إعداد متغيرات البيئة في Vercel</h2>'
        . '<p>أضف المتغيرات التالية من Settings ← Environment Variables ثم أعد النشر (Redeploy):</p><ul>';
    foreach ($missing as $k) {
        echo '<li><code>' . htmlspecialchars($k) . '</code></li>';
    }
    echo '</ul></body>';
    return;
}

// Laravel derives its base path from SCRIPT_NAME; leaving it as /api/index.php would make it
// strip "/api" from every URL and break the /api/v1/* routes, so present it as the web root.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../public/index.php';

require __DIR__ . '/../public/index.php';
