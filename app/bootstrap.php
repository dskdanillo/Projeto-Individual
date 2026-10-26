<?php
declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

define('APP_PATH', __DIR__);

const CATEGORIAS_CUSTO = ['Combustível', 'Pedágio', 'Alimentação', 'Manutenção', 'Estacionamento', 'Outros'];

require APP_PATH . '/lib/helpers.php';
require APP_PATH . '/lib/db.php';
require APP_PATH . '/lib/auth.php';
require APP_PATH . '/lib/layout.php';
