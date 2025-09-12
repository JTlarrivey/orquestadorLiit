<?php
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/autoload.php';

use App\Router\Router;

(new Router())->dispatch();
