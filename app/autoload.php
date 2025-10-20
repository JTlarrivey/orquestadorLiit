<?php

declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

/**
 * Carga variables desde un archivo .env (sin dependencias).
 * - Soporta líneas con: KEY=value, KEY="valor con espacios" y KEY='...'
 * - Expande ${OTRA_VAR} si ya está definida en env o fue cargada antes.
 * - No pisa variables ya presentes en el entorno (override=false).
 */
if (!function_exists('load_dotenv')) {
    function load_dotenv(string $file, bool $override = false): void
    {
        if (!is_file($file)) return;

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);

            // comentarios (# ...) o líneas vacías
            if ($line === '' || $line[0] === '#' || str_starts_with($line, '//')) continue;

            // permitir "export KEY=VAL"
            if (str_starts_with($line, 'export ')) $line = trim(substr($line, 7));

            // parseo KEY=VAL
            if (!preg_match('/^\s*([A-Z0-9_]+)\s*=\s*(.*)\s*$/i', $line, $m)) continue;
            $key = $m[1];
            $val = $m[2];

            // quitar comillas si las hay
            if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                (str_starts_with($val, "'") && str_ends_with($val, "'"))
            ) {
                $val = substr($val, 1, -1);
            }

            // expandir ${VAR}
            $val = preg_replace_callback('/\$\{([A-Z0-9_]+)\}/i', function ($mm) {
                $k = $mm[1];
                $fromEnv = getenv($k);
                if ($fromEnv !== false) return $fromEnv;
                if (isset($_ENV[$k])) return $_ENV[$k];
                if (isset($_SERVER[$k])) return $_SERVER[$k];
                return '';
            }, $val);

            // no pisar si ya existe y override=false
            $already = getenv($key);
            if ($already !== false && $already !== '' && !$override) {
                $_ENV[$key]    = $already;
                $_SERVER[$key] = $already;
                continue;
            }

            putenv("$key=$val");
            $_ENV[$key]    = $val;
            $_SERVER[$key] = $val;
        }
    }
}

// Cargar .env desde la raíz del proyecto (antes de usar getenv())
load_dotenv(APP_ROOT . '/.env', false);

// ---------------------------------------------------------
// Autoload de clases PSR-4 para el namespace App\...
// ---------------------------------------------------------
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDirs = [
        APP_ROOT . '/app/', // para clases internas del orquestador
        APP_ROOT . '/src/', // para Data/Shaper, Services, etc.
    ];

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) return;

    $relative = substr($class, strlen($prefix));
    $relativePath = str_replace('\\', '/', $relative) . '.php';

    foreach ($baseDirs as $dir) {
        $file = $dir . $relativePath;
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }

    // Log opcional para debug (puede comentarse en producción)
    // error_log("Clase no encontrada: {$class}");
});

// ---------------------------------------------------------
// Helper cfg(): toma una key de PROD y una de LOCAL
// ---------------------------------------------------------
if (!function_exists('cfg')) {
    function cfg(string $prodKey, string $localKey, ?string $default = null): ?string
    {
        $env  = getenv('APP_ENV') ?: 'prod';
        $keys = ($env === 'prod') ? [$prodKey, $localKey] : [$localKey, $prodKey];
        foreach ($keys as $k) {
            $v = getenv($k);
            if ($v !== false && $v !== '') return $v;
        }
        return $default;
    }
}
