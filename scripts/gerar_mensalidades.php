<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

date_default_timezone_set('America/Sao_Paulo');
require dirname(__DIR__) . '/app/lib/db.php';
require dirname(__DIR__) . '/app/lib/mensalidades.php';

$hoje = date('Y-m-d');
$quantidade = gerar_mensalidades(db(), date('Y-m'), null, $hoje);
fwrite(STDOUT, "$quantidade mensalidade(s) gerada(s) para " . date('m/Y') . ".\n");
