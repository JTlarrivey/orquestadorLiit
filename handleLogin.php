<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/../converseLarry/config/config.php';
require_once __DIR__ . '/../converseLarry/functions.php';

use App\Model\UserContext;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = $_POST['email']    ?? '';
    $role     = $_POST['role']     ?? '';
    $password = $_POST['password'] ?? '';

    $user = verificarLoginConBackend($email, $role, $password);

    if ($user) {
        // ✅ Guardar contexto como objeto
        $_SESSION['user_context'] = new UserContext(
            $user['user_id'],
            $user['name'] ?? 'Sin nombre',
            $user['role'],
            $user['permissions'],
            $user['jerarquia'] ?? null
        );


        // ✅ Opcional: también guardás la versión array por compatibilidad
        $_SESSION['user'] = [
            'id'          => $user['user_id'],
            'name'        => $user['name'] ?? 'Sin nombre',
            'role'        => $user['role'],
            'permissions' => $user['permissions'],
            'jerarquia'   => $user['jerarquia'] ?? null
        ];


        $_SESSION['layout'] = [
            'template' => $user['layout_pref'] ?? 'default',
        ];

        session_write_close();

        // ✅ Redirigir al frontend
        header('Location: /converseLarry/index.php');
        exit;
    } else {
        header('Location: /converseLarry/index.php?error=1');
        exit;
    }
}

header('Location: /converseLarry/index.php');
exit;
