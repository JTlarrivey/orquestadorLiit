<?php

declare(strict_types=1);

/**
 * PSR-4 autoloader mínimo sin Composer.
 * Mapea namespaces a carpetas.
 */
spl_autoload_register(function (string $class): void {
    $prefixes = [
        'App\\' => __DIR__ . '/',   // tus clases viven en app/
        // Si tenés otro namespace/carpeta, agregalo acá:
        // 'Core\\' => __DIR__ . '/../core/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($class, $prefix, $len) !== 0) continue;

        $relative = str_replace('\\', '/', substr($class, $len)) . '.php';
        $file = $baseDir . $relative;
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});
