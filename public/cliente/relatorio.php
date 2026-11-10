<?php
require __DIR__ . '/../../app/bootstrap.php';
$u = require_login('cliente');
$cid = (int)$u['cliente_id'];
$pdo = db();
$idioma = (string)($_GET['idioma'] ?? 'pt');
if (!in_array($idioma, ['pt', 'en', 'es'], true)) {
    $idioma = 'pt';
}
$traducoesPdf = [
    'en' => [
        'Gestão de Custo' => 'Cost Management',
        'Relatório de rotas e financeiro' => 'Routes and financial report',
        'Cliente' => 'Client',
        'Período' => 'Period',
        'até' => 'to',
        'Todo o histórico' => 'All time',
        'Gerado em' => 'Generated on',
        'Faturamento das rotas' => 'Route revenue',
        'Gastos lançados' => 'Recorded expenses',
        'Lucro líquido' => 'Net profit',
        'Rotas realizadas' => 'Routes completed',
        'Gastos por categoria' => 'Expenses by category',
        'Outros' => 'Other',
        'Data' => 'Date',
        'Rota (origem → destino)' => 'Route (origin → destination)',
        'Distância' => 'Distance',
        'Faturamento' => 'Revenue',
        'Gastos' => 'Expenses',
        'Lucro' => 'Profit',
        'Nenhuma rota cadastrada neste período.' => 'No routes registered for this period.',
        'Faturamento, gastos e lucro consideram somente as rotas deste período.' => 'Revenue, expenses, and profit include only routes from this period.',
        'Página' => 'Page',
        'Combustível' => 'Fuel',
        'Pedágio' => 'Toll',
        'Alimentação' => 'Meals',
        'Manutenção' => 'Maintenance',
        'Estacionamento' => 'Parking',
    ],
    'es' => [
        'Gestão de Custo' => 'Gestión de Costos',
        'Relatório de rotas e financeiro' => 'Informe de rutas y finanzas',
        'Cliente' => 'Cliente',
        'Período' => 'Período',
        'até' => 'hasta',
        'Todo o histórico' => 'Todo el historial',
        'Gerado em' => 'Generado el',
        'Faturamento das rotas' => 'Ingresos de las rutas',
        'Gastos lançados' => 'Gastos registrados',
        'Lucro líquido' => 'Beneficio neto',
        'Rotas realizadas' => 'Rutas realizadas',
        'Gastos por categoria' => 'Gastos por categoría',
        'Outros' => 'Otros',
        'Data' => 'Fecha',
        'Rota (origem → destino)' => 'Ruta (origen → destino)',
        'Distância' => 'Distancia',
        'Faturamento' => 'Ingresos',
        'Gastos' => 'Gastos',
        'Lucro' => 'Beneficio',
        'Nenhuma rota cadastrada neste período.' => 'No hay rutas registradas en este período.',
        'Faturamento, gastos e lucro consideram somente as rotas deste período.' => 'Los ingresos, gastos y beneficios incluyen solo las rutas de este período.',
        'Página' => 'Página',
        'Combustível' => 'Combustible',
        'Pedágio' => 'Peaje',
        'Alimentação' => 'Alimentación',
        'Manutenção' => 'Mantenimiento',
        'Estacionamento' => 'Estacionamiento',
    ],
];
$t = static fn(string $valor): string => $traducoesPdf[$idioma][$valor] ?? $valor;

try {
    $filtro = ler_filtro_periodo($_GET, 'mes');
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    exit(h($e->getMessage()));
}

$where = 'r.cliente_id = ?';
$params = [$cid];
if ($filtro['de'] !== null) {
    $where .= ' AND r.data_rota BETWEEN ? AND ?';
    $params[] = $filtro['de'];
    $params[] = $filtro['ate'];
}

$st = $pdo->prepare("SELECT r.id, r.origem, r.destino, r.km, r.ida_volta, r.valor_receber, r.data_rota,
                            COALESCE(SUM(c.valor * CASE WHEN r.ida_volta = 1 AND c.categoria = 'Combustível' THEN 2 ELSE 1 END), 0) AS custo
                       FROM rotas r
                       LEFT JOIN custos_rota c ON c.rota_id = r.id
                      WHERE $where
                      GROUP BY r.id
                      ORDER BY r.data_rota, r.id");
$st->execute($params);
$rotas = $st->fetchAll();

$st = $pdo->prepare("SELECT c.categoria, SUM(c.valor * CASE WHEN r.ida_volta = 1 AND c.categoria = 'Combustível' THEN 2 ELSE 1 END) AS total
                       FROM custos_rota c
                       JOIN rotas r ON r.id = c.rota_id
                      WHERE $where
                      GROUP BY c.categoria
                      ORDER BY total DESC, c.categoria");
$st->execute($params);
$categoriasBanco = $st->fetchAll();
$totaisPorCategoria = array_fill_keys(CATEGORIAS_CUSTO, 0.0);
foreach ($categoriasBanco as $categoria) {
    if (array_key_exists($categoria['categoria'], $totaisPorCategoria)) {
        $totaisPorCategoria[$categoria['categoria']] = (float)$categoria['total'];
    }
}
$categorias = [];
foreach ($totaisPorCategoria as $nome => $total) {
    $categorias[] = ['categoria' => $nome, 'total' => $total];
}
usort($categorias, static fn(array $a, array $b): int => $b['total'] <=> $a['total']);

$totais = ['km' => 0.0, 'receita' => 0.0, 'custo' => 0.0];
foreach ($rotas as $rota) {
    $multiplicador = (int)$rota['ida_volta'] === 1 ? 2 : 1;
    $totais['km'] += (float)$rota['km'] * $multiplicador;
    $totais['receita'] += (float)$rota['valor_receber'];
    $totais['custo'] += (float)$rota['custo'];
}
$totais['lucro'] = $totais['receita'] - $totais['custo'];

$largura = 842;
$altura = 595;
$conteudo = '';
$escape = static function (string $valor): string {
    $valor = (string)preg_replace('/[\x00-\x1F\x7F]/', ' ', $valor);
    $textoPdf = iconv('UTF-8', 'Windows-1252//TRANSLIT', $valor);
    if ($textoPdf === false) {
        throw new RuntimeException('Não foi possível converter o texto do relatório para PDF.');
    }
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $textoPdf);
};
$texto = static function (string $valor, float $x, float $topo, int $tamanho = 9, bool $negrito = false, array $cor = [0.12, 0.17, 0.27]) use (&$conteudo, $escape, $altura): void {
    $fonte = $negrito ? 'F2' : 'F1';
    $y = $altura - $topo - $tamanho;
    $conteudo .= sprintf("%.3f %.3f %.3f rg BT /%s %d Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n", $cor[0], $cor[1], $cor[2], $fonte, $tamanho, $x, $y, $escape($valor));
};
$retangulo = static function (float $x, float $topo, float $w, float $h, array $cor) use (&$conteudo, $altura): void {
    $y = $altura - $topo - $h;
    $conteudo .= sprintf("q %.3f %.3f %.3f rg %.2f %.2f %.2f %.2f re f Q\n", $cor[0], $cor[1], $cor[2], $x, $y, $w, $h);
};
$limitar = static function (?string $valor, int $limite): string {
    $valor = trim((string)$valor);
    if (mb_strlen($valor, 'UTF-8') > $limite) {
        return mb_substr($valor, 0, $limite - 1, 'UTF-8') . '...';
    }
    return $valor !== '' ? $valor : '-';
};
$periodo = $filtro['de'] === null
    ? $t('Todo o histórico')
    : date_br($filtro['de']) . ' ' . $t('até') . ' ' . date_br($filtro['ate']);
$totalRotas = count($rotas);
$porPagina = 14;
$paginas = max(1, (int)ceil($totalRotas / $porPagina));
$objetos = [
    1 => '<< /Type /Catalog /Pages 2 0 R >>',
    3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
    4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
];
$idsPaginas = [];

for ($pagina = 0; $pagina < $paginas; $pagina++) {
    $idPagina = 5 + $pagina;
    $idConteudo = 5 + $paginas + $pagina;
    $idsPaginas[] = $idPagina . ' 0 R';
    $conteudo = '';

    $retangulo(0, 0, $largura, 92, [0.07, 0.12, 0.23]);
    $retangulo(32, 91, 778, 3, [0.83, 0.67, 0.37]);
    $texto('DSK  |  ' . mb_strtoupper($t('Gestão de Custo'), 'UTF-8'), 32, 16, 9, true, [1, 1, 1]);
    $texto($t('Relatório de rotas e financeiro'), 32, 36, 20, true, [1, 1, 1]);
    $texto($t('Cliente') . ': ' . $limitar($u['nome'], 55) . '    •    ' . $t('Período') . ': ' . $periodo, 32, 68, 9, false, [0.81, 0.86, 0.94]);
    $texto($t('Gerado em') . ' ' . date('d/m/Y H:i'), 682, 20, 8, false, [0.90, 0.92, 0.96]);

    $cartoes = [
        [$t('Faturamento das rotas'), brl($totais['receita']), [0.89, 0.96, 0.92], [0.12, 0.42, 0.29]],
        [$t('Gastos lançados'), brl($totais['custo']), [0.98, 0.91, 0.91], [0.58, 0.22, 0.22]],
        [$t('Lucro líquido'), brl($totais['lucro']), $totais['lucro'] >= 0 ? [0.89, 0.96, 0.92] : [0.98, 0.91, 0.91], $totais['lucro'] >= 0 ? [0.12, 0.42, 0.29] : [0.58, 0.22, 0.22]],
        [$t('Rotas realizadas'), $totalRotas . '  •  ' . number_format($totais['km'], 1, ',', '.') . ' km', [0.93, 0.95, 0.98], [0.12, 0.17, 0.27]],
    ];
    foreach ($cartoes as $indice => [$rotulo, $valor, $fundo, $corTexto]) {
        $x = 32 + $indice * 197;
        $retangulo($x, 108, 185, 56, $fundo);
        $texto($rotulo, $x + 10, 118, 8, false, $corTexto);
        $texto($valor, $x + 10, 138, $indice === 3 ? 10 : 13, true, $corTexto);
    }

    $texto($t('Gastos por categoria'), 32, 175, 9, true);
    $somaCategorias = array_sum(array_map(static fn(array $categoria): float => (float)$categoria['total'], $categorias));
    for ($i = 0; $i < 6; $i++) {
        $linha = intdiv($i, 3);
        $coluna = $i % 3;
        $x = 32 + $coluna * 263;
        $y = 190 + $linha * 23;
        $categoria = $categorias[$i] ?? null;
        $nome = $categoria ? $t($limitar((string)$categoria['categoria'], 20)) : $t('Outros');
        $valor = $categoria ? (float)$categoria['total'] : 0.0;
        $percentual = $somaCategorias > 0 ? number_format($valor / $somaCategorias * 100, 1, ',', '.') . '%' : '0,0%';
        $fundoCategoria = [0.95, 0.96, 0.98];
        $retangulo($x, $y, 250, 19, $fundoCategoria);
        $texto($nome . ': ' . brl($valor) . ' (' . $percentual . ')', $x + 7, $y + 5, 7, false, [0.20, 0.25, 0.35]);
    }

    $topoTabela = 245;
    $retangulo(32, $topoTabela, 778, 24, [0.10, 0.16, 0.29]);
    foreach ([
        [$t('Data'), 32],
        [$t('Rota (origem → destino)'), 97],
        [$t('Distância'), 447],
        [$t('Faturamento'), 520],
        [$t('Gastos'), 625],
        [$t('Lucro'), 727],
    ] as [$titulo, $x]) {
        $texto($titulo, $x + 7, $topoTabela + 7, 8, true, [1, 1, 1]);
    }

    $linhas = array_slice($rotas, $pagina * $porPagina, $porPagina);
    if (!$linhas) {
        $texto($t('Nenhuma rota cadastrada neste período.'), 42, $topoTabela + 40, 9, false, [0.39, 0.43, 0.51]);
    }
    foreach ($linhas as $indice => $rota) {
        $topoLinha = $topoTabela + 24 + $indice * 19;
        if ($indice % 2 === 0) {
            $retangulo(32, $topoLinha, 778, 19, [0.96, 0.97, 0.98]);
        }
        $retangulo(32, $topoLinha + 18.5, 778, 0.5, [0.86, 0.88, 0.91]);
        $lucroRota = (float)$rota['valor_receber'] - (float)$rota['custo'];
        $texto(date_br($rota['data_rota']), 39, $topoLinha + 5, 7);
        $rotaTexto = $limitar((string)$rota['origem'], 24) . ' → ' . $limitar((string)$rota['destino'], 24);
        $texto($limitar($rotaTexto, 54), 104, $topoLinha + 5, 7);
        $kmRota = (float)$rota['km'] * ((int)$rota['ida_volta'] === 1 ? 2 : 1);
        $texto(number_format($kmRota, 1, ',', '.') . ' km', 454, $topoLinha + 5, 7);
        $texto(brl($rota['valor_receber']), 527, $topoLinha + 5, 7);
        $texto(brl($rota['custo']), 632, $topoLinha + 5, 7);
        $corLucro = $lucroRota >= 0 ? [0.12, 0.42, 0.29] : [0.58, 0.22, 0.22];
        $texto(brl($lucroRota), 734, $topoLinha + 5, 7, true, $corLucro);
    }

    $texto($t('Faturamento, gastos e lucro consideram somente as rotas deste período.'), 32, 564, 7, false, [0.42, 0.46, 0.54]);
    $texto($t('Página') . ' ' . ($pagina + 1) . ' ' . ($idioma === 'en' ? 'of' : ($idioma === 'es' ? 'de' : 'de')) . ' ' . $paginas, 746, 564, 7, false, [0.42, 0.46, 0.54]);

    $objetos[$idPagina] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . $largura . ' ' . $altura . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $idConteudo . ' 0 R >>';
    $objetos[$idConteudo] = '<< /Length ' . strlen($conteudo) . " >>\nstream\n" . $conteudo . "endstream";
}

$objetos[2] = '<< /Type /Pages /Kids [' . implode(' ', $idsPaginas) . '] /Count ' . $paginas . ' >>';
ksort($objetos);
$pdf = "%PDF-1.4\n";
$deslocamentos = [0];
foreach ($objetos as $id => $objeto) {
    $deslocamentos[$id] = strlen($pdf);
    $pdf .= $id . " 0 obj\n" . $objeto . "\nendobj\n";
}
$inicioXref = strlen($pdf);
$pdf .= "xref\n0 " . (count($objetos) + 1) . "\n0000000000 65535 f \n";
for ($id = 1; $id <= count($objetos); $id++) {
    $pdf .= sprintf("%010d 00000 n \n", $deslocamentos[$id]);
}
$pdf .= "trailer\n<< /Size " . (count($objetos) + 1) . " /Root 1 0 R >>\nstartxref\n" . $inicioXref . "\n%%EOF";

$inicioArquivo = $filtro['de'] ?? 'todos';
$fimArquivo = $filtro['ate'] ?? 'todos';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="relatorio-rotas-' . str_replace('-', '', $inicioArquivo) . '-' . str_replace('-', '', $fimArquivo) . '.pdf"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
