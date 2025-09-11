<?php
// orquestadorLiit/app/autoload.php
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base   = __DIR__ . '/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $rel = str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    $file = $base . $rel;
    if (file_exists($file)) require $file;
});
