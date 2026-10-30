<?php
require __DIR__ . '/../../app/bootstrap.php';
$u = require_login('cliente');
$cid = (int)$u['cliente_id'];
$pdo = db();

try {
    $filtro = ler_filtro_periodo($_GET, 'mes');
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    exit(h($e->getMessage()));
}

$st = $pdo->prepare('SELECT r.id, r.origem, r.destino, r.km, r.ida_volta, r.valor_km, r.valor_receber, r.data_rota,
                            COALESCE(SUM(c.valor * CASE WHEN r.ida_volta = 1 THEN 2 ELSE 1 END), 0) AS custo
                       FROM rotas r
                       LEFT JOIN custos_rota c ON c.rota_id = r.id
                      WHERE r.cliente_id = ? AND r.data_rota BETWEEN ? AND ?
                      GROUP BY r.id
                      ORDER BY r.data_rota, r.id');
$st->execute([$cid, $filtro['de'], $filtro['ate']]);
$rotas = $st->fetchAll();

$custosPorRota = [];
if ($rotas) {
    $ids = array_column($rotas, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("SELECT c.rota_id, c.categoria,
                                SUM(c.valor * CASE WHEN r.ida_volta = 1 THEN 2 ELSE 1 END) AS total
                           FROM custos_rota c
                           JOIN rotas r ON r.id = c.rota_id
                          WHERE r.cliente_id = ? AND r.id IN ($placeholders)
                          GROUP BY c.rota_id, c.categoria");
    $st->execute(array_merge([$cid], $ids));
    foreach ($st->fetchAll() as $custo) {
        $custosPorRota[(int)$custo['rota_id']][$custo['categoria']] = (float)$custo['total'];
    }
}

$categorias = CATEGORIAS_CUSTO;
$inicioArquivo = str_replace('-', '', $filtro['de']);
$fimArquivo = str_replace('-', '', $filtro['ate']);
$nomeArquivo = 'relatorio_rotas_' . $inicioArquivo . '_' . $fimArquivo . '.csv';

$csvTexto = static function (string $valor): string {
    return preg_match('/^[\s]*[=+\-@]/u', $valor) ? "'" . $valor : $valor;
};
$csv = fopen('php://output', 'wb');
if ($csv === false) {
    throw new RuntimeException('Não foi possível iniciar o arquivo de relatório.');
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');
echo "\xEF\xBB\xBF";

$cabecalho = ['Data', 'Origem', 'Destino', 'Distância total (km)', 'Valor por km (R$)', 'Receita (R$)'];
foreach ($categorias as $categoria) {
    $cabecalho[] = 'Custo - ' . $categoria . ' (R$)';
}
array_push($cabecalho, 'Total de custos (R$)', 'Lucro (R$)');
fputcsv($csv, $cabecalho, ';', '"', '');

$totais = array_fill_keys($categorias, 0.0);
$kmTotal = 0.0;
$receitaTotal = 0.0;
$custoTotal = 0.0;
foreach ($rotas as $rota) {
    $multiplicador = (int)$rota['ida_volta'] === 1 ? 2 : 1;
    $custosDaRota = $custosPorRota[(int)$rota['id']] ?? [];
    $distanciaTotal = (float)$rota['km'] * $multiplicador;
    $kmTotal += $distanciaTotal;
    $receitaTotal += (float)$rota['valor_receber'];
    $linha = [
        date_br($rota['data_rota']),
        $csvTexto((string)$rota['origem']),
        $csvTexto((string)$rota['destino']),
        number_format($distanciaTotal, 2, ',', ''),
        number_format((float)$rota['valor_km'], 2, ',', ''),
        number_format((float)$rota['valor_receber'], 2, ',', ''),
    ];
    foreach ($categorias as $categoria) {
        $valorCategoria = (float)($custosDaRota[$categoria] ?? 0);
        $totais[$categoria] += $valorCategoria;
        $linha[] = number_format($valorCategoria, 2, ',', '');
    }
    $totalCusto = (float)$rota['custo'];
    $custoTotal += $totalCusto;
    array_push($linha, number_format($totalCusto, 2, ',', ''), number_format((float)$rota['valor_receber'] - $totalCusto, 2, ',', ''));
    fputcsv($csv, $linha, ';', '"', '');
}

$linhaTotal = ['TOTAL DO PERÍODO', '', '', number_format($kmTotal, 2, ',', ''), '', number_format($receitaTotal, 2, ',', '')];
foreach ($categorias as $categoria) {
    $linhaTotal[] = number_format($totais[$categoria], 2, ',', '');
}
array_push($linhaTotal, number_format($custoTotal, 2, ',', ''), number_format($receitaTotal - $custoTotal, 2, ',', ''));
fputcsv($csv, $linhaTotal, ';', '"', '');

fclose($csv);
