<?php

// PHP built-in server router used exclusively by frontend-browser-fixture.php.
if (getenv('APP_ENV') !== 'testing' || ! getenv('LARAVEL_STORAGE_PATH')) {
    http_response_code(503);

    return;
}
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (str_starts_with($path, '/storage/')) {
    $root = realpath(getenv('LARAVEL_STORAGE_PATH').'/app/public');
    $file = realpath($root.'/'.substr($path, strlen('/storage/')));
    if (! $file || ! str_starts_with($file, $root.DIRECTORY_SEPARATOR) || ! is_file($file) || ! in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
        http_response_code(404);

        return;
    }
    $mime = mime_content_type($file);
    if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        http_response_code(404);

        return;
    }
    header('Content-Type: '.$mime);
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: '.filesize($file));
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        readfile($file);
    }

    return;
}
require dirname(__DIR__).'/backend/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php';
