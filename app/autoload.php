<?php
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $base = __DIR__ . '/';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) return;
    $rel = str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    $file = $base . $rel;
    if (is_file($file)) require $file;
});
