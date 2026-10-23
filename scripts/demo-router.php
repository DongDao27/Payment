<?php

// Local-only router with a dedicated database, cookie, key and file storage.
$root = dirname(__DIR__);
require_once $root.'/backend/vendor/autoload.php';
Dotenv\Dotenv::createImmutable($root.'/backend', '.env.demo')->load();
if (($_ENV['APP_ENV'] ?? '') !== 'local' || ($_ENV['DB_DATABASE'] ?? '') !== 'thu_thanh_demo') {
    http_response_code(503);

    return;
}
$_ENV['LARAVEL_STORAGE_PATH'] = $_SERVER['LARAVEL_STORAGE_PATH'] = $root.'/.runtime/demo-storage';
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (str_starts_with($path, '/storage/')) {
    $public = realpath($root.'/.runtime/demo-storage/app/public');
    $file = realpath($public.'/'.substr($path, 9));
    if (! $file || ! str_starts_with($file, $public.DIRECTORY_SEPARATOR) || ! is_file($file) || ! in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true) || ! in_array(mime_content_type($file), ['image/jpeg', 'image/png', 'image/webp'], true)) {
        http_response_code(404);

        return;
    }
    header('Content-Type: '.mime_content_type($file));
    header('X-Content-Type-Options: nosniff');
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        readfile($file);
    }

return;
}
require $root.'/backend/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php';
